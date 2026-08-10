<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
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
            // One visit can cover several of the child's availed services.
            'linked_client_service_ids' => ['nullable', 'array'],
            'linked_client_service_ids.*' => ['integer', 'exists:client_services,id'],
            'service_id' => ['nullable', 'integer', 'exists:service_offerings,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            // Minutes. Phase 18 replaced the free-text "30 minutes" form.
            'duration' => ['required', 'integer', 'min:5', 'max:480'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Reject a session that overlaps one already booked for the same
     * therapist or the same child.
     *
     * Runs after the field rules so the times are known to be parseable, and
     * bails if any input it depends on already failed.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['client_id', 'linked_client_service_ids'])) {
                    return;
                }

                $this->failOnUnbookableServices($validator);
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['client_id', 'therapist_id', 'date', 'start_time', 'duration'])) {
                    return;
                }

                $start = Carbon::parse("{$this->date} {$this->start_time}");
                $end = (clone $start)->addMinutes((int) $this->duration);

                $this->failOnConflict(
                    $validator,
                    'therapist_id',
                    ScheduleSession::query()->where('therapist_id', $this->effectiveTherapistId()),
                    $start,
                    $end,
                    'This therapist already has a session booked from :from to :to.',
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
        ];
    }

    /**
     * Every availed service picked has to belong to the child being booked,
     * and — for a therapist — has to be one of their own that is still
     * awaiting a session. That last part is what stops the same service being
     * scheduled twice: once booked it leaves the picker, and this rejects it
     * if it's posted anyway. Services already linked to the session being
     * edited are exempt, since that session is what booked them.
     */
    private function failOnUnbookableServices(Validator $validator): void
    {
        $ids = $this->collect('linked_client_service_ids')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique();

        if ($ids->isEmpty()) {
            return;
        }

        $user = $this->user();
        $alreadyLinked = $this->linkedClientServiceIds();

        $bookable = ClientService::query()
            ->whereIn('id', $ids)
            ->where('client_id', $this->integer('client_id'))
            ->when($user?->isAdmin() !== true, function (Builder $query) use ($user, $alreadyLinked): void {
                $bookableIds = ClientService::query()->select('id')->awaitingSchedule();

                if ($alreadyLinked !== []) {
                    $bookableIds->orWhereIn('id', $alreadyLinked);
                }

                $query->where('therapist_id', $user?->id)->whereIn('id', $bookableIds);
            })
            ->pluck('id');

        if ($ids->diff($bookable)->isNotEmpty()) {
            $validator->errors()->add(
                'linked_client_service_ids',
                'One of the selected services is not available to schedule for this client.',
            );
        }
    }

    /**
     * The availed services the session being edited already covers. Empty
     * when storing.
     *
     * @return array<int, int>
     */
    protected function linkedClientServiceIds(): array
    {
        return [];
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
     * The session being edited, so an update doesn't collide with itself.
     * Null when storing.
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
            ->whereNotIn('status', ['cancelled', 'no_show'])
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
     * Therapists may only schedule for clients on their own caseload who
     * still have an availed service of theirs left to book. Admins are
     * unrestricted. This mirrors the scoping applied to the form's client
     * dropdown, so a posted `client_id` can't reach outside it.
     *
     * Rescheduling is exempt from the second half — the client of the session
     * being edited stays valid even though that session is what made their
     * remaining service no longer awaiting a booking.
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

            if ($this->ignoredSessionId() !== null) {
                return;
            }

            $hasServiceLeft = ClientService::query()
                ->where('client_id', $value)
                ->where('therapist_id', $user->id)
                ->awaitingSchedule()
                ->exists();

            if (! $hasServiceLeft) {
                $fail('This client has no remaining service of yours left to schedule.');
            }
        };
    }
}
