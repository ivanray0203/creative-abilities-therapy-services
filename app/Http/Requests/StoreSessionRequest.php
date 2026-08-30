<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Services\ServiceContractLedger;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Shared admin/therapist "Add Session" form (reference: cats-frontend/src/forms/SessionsForm.tsx).
 *
 * `therapist_id` is only required from admins — a therapist submitting this
 * form is always scheduling for themselves, enforced in the controller
 * rather than here since it depends on the acting user.
 *
 * Phase 20 turned the availed-service picker into an hours claim: each
 * selected service carries the share of the visit it accounts for, and that
 * share has to fit inside the contract admin issued for it.
 */
class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true || $this->user()?->isTherapist() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id', $this->clientOnOwnCaseload()],
            'therapist_id' => [$this->user()?->isAdmin() === true ? 'required' : 'nullable', 'integer', 'exists:users,id'],
            /*
             * One visit can cover several of the child's availed services,
             * and each one draws from its own contract, so the picker posts
             * an hours figure alongside the id. Omit every `hours` and the
             * visit is split evenly — see `allocations()`.
             */
            'linked_client_services' => ['nullable', 'array'],
            'linked_client_services.*.client_service_id' => ['required', 'integer', 'exists:client_services,id'],
            'linked_client_services.*.hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'service_id' => ['nullable', 'integer', 'exists:service_offerings,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            // The booked finish time. `duration` is no longer posted — the
            // controller derives those minutes from this window and stores
            // them, so everything reading `duration` keeps working.
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Reject a session that overlaps one already booked for the same
     * therapist or the same child, or that claims hours no contract has.
     *
     * Runs after the field rules so the times are known to be parseable, and
     * each check bails if any input it depends on already failed.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['client_id', 'linked_client_services'])) {
                    return;
                }

                $this->failOnServicesOffCaseload($validator);
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['client_id', 'therapist_id', 'date', 'start_time', 'end_time'])) {
                    return;
                }

                $start = Carbon::parse("{$this->date} {$this->start_time}");
                $end = Carbon::parse("{$this->date} {$this->end_time}");

                if (! $this->hasWorkableLength($validator, $start, $end)) {
                    return;
                }

                $this->failOnConflict(
                    $validator,
                    'therapist_id',
                    ScheduleSession::query()->where('therapist_id', $this->effectiveTherapistId()),
                    $start,
                    $end,
                    // A therapist is always booking their own diary, so the
                    // clash is theirs rather than some third party's.
                    $this->user()?->isAdmin() === true
                        ? 'This therapist already has a session booked from :from to :to.'
                        : 'You already have a session booked from :from to :to.',
                );

                $this->failOnConflict(
                    $validator,
                    'client_id',
                    ScheduleSession::query()->where('client_id', $this->integer('client_id')),
                    $start,
                    $end,
                    'This client already has a session booked from :from to :to.',
                );
            },
            function (Validator $validator): void {
                /*
                 * Only the inputs this check reads. Bailing on *any* earlier
                 * error would hide the contract reason behind the vaguer
                 * caseload one whenever both fired.
                 */
                if ($validator->errors()->hasAny(['client_id', 'date', 'start_time', 'end_time', 'linked_client_services'])) {
                    return;
                }

                $this->failOnContractProblems($validator);
            },
        ];
    }

    /**
     * The availed services this session covers and the hours each one draws,
     * with the even split filled in where the form left them blank.
     *
     * Partial answers are not honoured: if any service was left without an
     * hours figure the whole visit is split evenly, because a mix of typed
     * and inferred shares is a total nobody intended.
     *
     * @return array<int, array{client_service_id: int, hours: float}>
     */
    public function allocations(): array
    {
        $picked = $this->collect('linked_client_services')
            ->map(fn (mixed $row): array => [
                'client_service_id' => (int) (is_array($row) ? ($row['client_service_id'] ?? 0) : 0),
                // `isset` already rules out null, so a blank box is the only
                // other way a row arrives without an hours figure.
                'hours' => is_array($row) && isset($row['hours']) && $row['hours'] !== ''
                    ? round((float) $row['hours'], 2)
                    : null,
            ])
            ->filter(fn (array $row): bool => $row['client_service_id'] > 0)
            ->unique('client_service_id')
            ->values();

        if ($picked->isEmpty()) {
            return [];
        }

        $ledger = app(ServiceContractLedger::class);

        if ($picked->contains(fn (array $row): bool => $row['hours'] === null)) {
            return $ledger->defaultAllocations(
                $picked->pluck('client_service_id')->all(),
                $this->sessionMinutes(),
            );
        }

        return $picked->map(fn (array $row): array => [
            'client_service_id' => $row['client_service_id'],
            'hours' => (float) $row['hours'],
        ])->all();
    }

    /**
     * The booked length of the visit, which is what the hours split has to
     * add up to. Zero when the times have not been given yet.
     */
    public function sessionMinutes(): int
    {
        if (! is_string($this->date) || ! is_string($this->start_time) || ! is_string($this->end_time)) {
            return 0;
        }

        try {
            $start = Carbon::parse("{$this->date} {$this->start_time}");
            $end = Carbon::parse("{$this->date} {$this->end_time}");
        } catch (\Exception) {
            return 0;
        }

        return max(0, (int) $start->diffInMinutes($end));
    }

    /** The day the session falls on, which is the day its contracts must cover. */
    public function sessionDate(): Carbon
    {
        return Carbon::parse((string) $this->date);
    }

    /**
     * The old `duration` field carried `min:5|max:480`. Those bounds still
     * apply, but now against the window the two times describe — `after`
     * alone would happily accept a one-minute or nine-hour session.
     *
     * Returns false when the window is unusable, so the caller can skip the
     * conflict lookup rather than report a clash on nonsense times.
     */
    private function hasWorkableLength(Validator $validator, Carbon $start, Carbon $end): bool
    {
        $minutes = (int) $start->diffInMinutes($end);

        if ($minutes < 5) {
            $validator->errors()->add('end_time', 'A session must run for at least 5 minutes.');

            return false;
        }

        if ($minutes > 480) {
            $validator->errors()->add('end_time', 'A session cannot run longer than 8 hours.');

            return false;
        }

        return true;
    }

    /**
     * Every availed service picked has to belong to the child being booked,
     * and — for a therapist — has to be one of their own.
     *
     * Whether it may be *scheduled* is a separate question with its own
     * answer: that is the contract's job, checked in
     * `failOnContractProblems()` so a therapist is told which of the two
     * things is wrong.
     */
    private function failOnServicesOffCaseload(Validator $validator): void
    {
        $ids = collect($this->allocations())->pluck('client_service_id');

        if ($ids->isEmpty()) {
            return;
        }

        $user = $this->user();

        $owned = ClientService::query()
            ->whereIn('id', $ids)
            ->where('client_id', $this->integer('client_id'))
            ->when($user?->isAdmin() !== true, fn (Builder $query) => $query->where('therapist_id', $user?->id))
            ->pluck('id');

        if ($ids->diff($owned)->isNotEmpty()) {
            $validator->errors()->add(
                ServiceContractLedger::ERROR_KEY,
                'One of the selected services does not belong to this client.',
            );
        }
    }

    /**
     * Contract gate: the hours claimed have to add up to the visit, and each
     * one has to fit inside a contract that covers the session's date.
     *
     * This pass is advisory — it exists so the therapist sees a field error
     * rather than an exception page. `ServiceContractLedger::apply()` repeats
     * it under a row lock, which is what actually decides.
     */
    private function failOnContractProblems(Validator $validator): void
    {
        $allocations = $this->allocations();

        if ($allocations === []) {
            return;
        }

        $problems = app(ServiceContractLedger::class)->problems(
            $allocations,
            $this->sessionDate(),
            $this->sessionMinutes(),
            $this->ignoredSessionId(),
        );

        foreach ($problems as $problem) {
            $validator->errors()->add(ServiceContractLedger::ERROR_KEY, $problem);
        }
    }

    /**
     * The therapist the session will actually belong to. Therapists always
     * schedule for themselves — SessionController overrides whatever
     * `therapist_id` was posted — so the conflict check has to resolve it the
     * same way or the two roles get checked against different people.
     */
    private function effectiveTherapistId(): ?int
    {
        return $this->user()?->isAdmin() === true
            ? $this->integer('therapist_id')
            : $this->user()?->id;
    }

    /**
     * The session being edited, so an update doesn't collide with itself —
     * not for its slot, and not for the hours it has already drawn. Null when
     * storing.
     */
    protected function ignoredSessionId(): ?int
    {
        return null;
    }

    /**
     * @param  Builder<ScheduleSession>  $query
     */
    private function failOnConflict(
        Validator $validator,
        string $attribute,
        Builder $query,
        Carbon $start,
        Carbon $end,
        string $message,
    ): void {
        $clash = $query
            // A cancelled or missed session frees its slot again.
            ->whereNotIn('status', ScheduleSession::HOURS_RELEASING_STATUSES)
            ->when(
                $this->ignoredSessionId() !== null,
                fn (Builder $inner) => $inner->whereKeyNot($this->ignoredSessionId()),
            )
            // Overlap, not mere adjacency — back-to-back bookings stay legal.
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end', '>', $start)
            ->first();

        if ($clash === null) {
            return;
        }

        $validator->errors()->add($attribute, strtr($message, [
            ':from' => $clash->scheduled_start?->format('g:i A') ?? '?',
            ':to' => $clash->scheduled_end?->format('g:i A') ?? '?',
        ]));
    }

    /**
     * Therapists may only schedule for clients on their own caseload who have
     * an availed service of theirs that is authorized for the session's date.
     * Admins are unrestricted. This mirrors the scoping applied to the form's
     * client dropdown, so a posted `client_id` can't reach outside it.
     *
     * Rescheduling is exempt from the second half: the client of the session
     * being edited stays valid even if their contract has since run dry,
     * otherwise a booked session could never be moved or corrected.
     */
    private function clientOnOwnCaseload(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = $this->user();

            if ($user === null || $user->isAdmin()) {
                return;
            }

            $onCaseload = Client::query()
                ->whereKey($value)
                ->forTherapist($user->id)
                ->exists();

            if (! $onCaseload) {
                $fail('The selected client is not on your caseload.');

                return;
            }

            /*
             * When the therapist named the services themselves, the contract
             * check answers this question far better — "0 of 40 hours left"
             * rather than "nothing available". Let it.
             */
            if ($this->ignoredSessionId() !== null || $this->sessionMinutes() === 0 || $this->allocations() !== []) {
                return;
            }

            $hasServiceLeft = ClientService::query()
                ->where('client_id', $value)
                ->where('therapist_id', $user->id)
                ->bookableOn($this->sessionDate())
                ->exists();

            if (! $hasServiceLeft) {
                $fail('This client has no contracted service of yours available on that date.');
            }
        };
    }
}
