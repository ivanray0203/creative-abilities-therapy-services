<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ClientContext;
use App\Services\ServiceContractLedger;
use App\Services\SessionNotifier;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared scheduling controller, reachable from both the admin and therapist
 * role route groups (reference: cats-backend/cats/views.py ScheduleSessionViewSet
 * and cats-frontend/src/pages/admin/SessionssPage.tsx). Data is scoped by
 * the acting user's role rather than by which route group was used.
 */
class SessionController extends Controller
{
    public function __construct(private ServiceContractLedger $ledger) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $baseQuery = ScheduleSession::query()->when(! $isAdmin, function (Builder $query) use ($user): void {
            $query->where('therapist_id', $user->id);
        });

        $quick = (string) $request->query('quick', 'all');
        $search = trim((string) $request->query('search', ''));
        $therapistId = $request->query('therapist_id');
        $serviceId = $request->query('service_id');
        $status = (string) $request->query('status', 'all');

        $filtered = (clone $baseQuery)
            ->when($quick === 'today', fn (Builder $query) => $query->whereDate('scheduled_start', now()->toDateString()))
            ->when($quick === 'upcoming', fn (Builder $query) => $query->where('scheduled_start', '>=', now()))
            ->when($quick === 'past', fn (Builder $query) => $query->where('scheduled_start', '<', now()))
            ->when($quick === 'disputed', fn (Builder $query) => $query->where('status', 'disputed'))
            ->when($isAdmin && filled($therapistId), fn (Builder $query) => $query->where('therapist_id', $therapistId))
            ->when(filled($serviceId), fn (Builder $query) => $query->where('service_id', $serviceId))
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('client.originalIntake', function (Builder $inner) use ($search): void {
                    $inner->where('child_first_name', 'like', "%{$search}%")
                        ->orWhere('child_last_name', 'like', "%{$search}%");
                });
            });

        $sessions = (clone $filtered)
            ->with(['client.originalIntake', 'therapist', 'service', 'clientServices.service'])
            ->orderBy('scheduled_start')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereDate('scheduled_start', now()->toDateString())->count(),
            'upcoming' => (clone $baseQuery)->where('scheduled_start', '>=', now())->count(),
            'disputed' => (clone $baseQuery)->where('status', 'disputed')->count(),
        ];

        return Inertia::render('sessions/index', [
            'sessions' => $sessions,
            'stats' => $stats,
            'filters' => [
                'quick' => $quick,
                'search' => $search,
                'therapist_id' => $therapistId,
                'service_id' => $serviceId,
                'status' => $status,
            ],
            'isAdmin' => $isAdmin,
            'therapists' => $isAdmin ? $this->therapists() : [],
            'services' => $this->services(),
            'clients' => $this->clientOptions($user),
        ]);
    }

    public function create(Request $request): Response
    {
        $clients = $this->clientOptions($request->user());

        /*
         * The caseload page links here per client. Only honour the hint when
         * that client is genuinely selectable — otherwise the form would open
         * showing a name that isn't in its own dropdown.
         */
        $requestedClientId = $request->integer('client_id');
        $preselectedClientId = $clients->contains('id', $requestedClientId)
            ? $requestedClientId
            : null;

        return Inertia::render('sessions/create', [
            'isAdmin' => $request->user()->isAdmin(),
            'therapists' => $request->user()->isAdmin() ? $this->therapists() : [],
            'services' => $this->services(),
            'clients' => $clients,
            'preselectedClientId' => $preselectedClientId,
        ]);
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $allocations = $request->allocations();
        $therapistId = $request->user()->isAdmin() ? (int) $validated['therapist_id'] : $request->user()->id;

        /*
         * The session row and its hours draw are one act. `apply()` rechecks
         * the balance under a lock and throws if another therapist took the
         * last hours in between, so creating the session outside this
         * transaction would leave a booking behind that drew nothing.
         */
        $session = DB::transaction(function () use ($request, $validated, $allocations, $therapistId): ScheduleSession {
            $session = ScheduleSession::query()->create([
                ...$this->scheduleAttributes($validated, $allocations),
                'therapist_id' => $therapistId,
                'status' => 'scheduled',
            ]);

            $this->ledger->apply($session, $allocations, $request->sessionMinutes());

            return $session;
        });

        AuditLogger::log('Scheduled session', 'System', "Scheduled session #{$session->id}");

        SessionNotifier::scheduled($session);

        return to_route($this->routeName($request, 'sessions.index'))->with('success', 'Session scheduled successfully.');
    }

    public function edit(Request $request, ScheduleSession $session): Response
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        return Inertia::render('sessions/edit', [
            // Loaded whole rather than `clientServices:id`: the form needs
            // the pivot's `hours` to prefill the split it saved last time.
            'session' => $session->load('clientServices'),
            'isAdmin' => $request->user()->isAdmin(),
            'therapists' => $request->user()->isAdmin() ? $this->therapists() : [],
            'services' => $this->services(),
            'clients' => $this->clientOptions($request->user(), $session),
        ]);
    }

    public function update(UpdateSessionRequest $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        $validated = $request->validated();
        $allocations = $request->allocations();
        $therapistId = $request->user()->isAdmin() ? (int) $validated['therapist_id'] : $session->therapist_id;

        $previousStart = $session->scheduled_start;

        DB::transaction(function () use ($request, $session, $validated, $allocations, $therapistId): void {
            $session->update([
                ...$this->scheduleAttributes($validated, $allocations),
                'therapist_id' => $therapistId,
            ]);

            $this->ledger->apply($session, $allocations, $request->sessionMinutes());
        });

        AuditLogger::log('Updated session', 'System', "Updated session #{$session->id}");

        if ($this->wasRescheduled($session, $previousStart)) {
            SessionNotifier::rescheduled($session, $previousStart);
        }

        return to_route($this->routeName($request, 'sessions.index'))->with('success', 'Session updated successfully.');
    }

    public function destroy(Request $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        $session->delete();

        AuditLogger::log('Deleted session', 'System', "Deleted session #{$session->id}", 'warning');

        return to_route($this->routeName($request, 'sessions.index'))->with('success', 'Session deleted successfully.');
    }

    public function cancel(Request $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string'],
        ]);

        $session->update([
            'status' => 'cancelled',
            'cancel_reason' => $validated['cancel_reason'],
        ]);

        AuditLogger::log('Cancelled session', 'System', "Cancelled session #{$session->id}", 'warning');

        SessionNotifier::cancelled($session);

        return back()->with('success', 'Session cancelled.');
    }

    public function dispute(Request $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertParticipant($session, $request->user());

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string'],
        ]);

        $session->update([
            'status' => 'disputed',
            'dispute_reason' => $validated['dispute_reason'],
        ]);

        Complaint::query()->create([
            'client_id' => $session->client_id,
            'therapist_id' => $session->therapist_id,
            'session_id' => $session->id,
            'type' => 'disputes',
            'status' => 'open',
            'complained_by' => 'client',
            'subject' => 'Session dispute',
            'description' => $validated['dispute_reason'],
        ]);

        AuditLogger::log('Disputed session', 'System', "Disputed session #{$session->id}", 'error');

        return back()->with('success', 'Session disputed.');
    }

    /**
     * Client-only action (reference: cats-frontend's handleVerify) — a
     * client confirms a session that's finished and awaiting their sign-off.
     */
    public function verify(Request $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertOwnsAsClient($session, $request->user());

        $session->update(['status' => 'confirmed']);

        AuditLogger::log('Verified session', 'System', "Verified session #{$session->id}");

        return back()->with('success', 'Session verified.');
    }

    /**
     * No dedicated start/end action exists in the reference either — it's a
     * raw PATCH from the frontend. Kept as small dedicated actions here
     * rather than folding into update()/UpdateSessionRequest, which
     * validates date/time/location fields irrelevant to a status flip.
     */
    public function startSession(Request $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        $alreadyActive = ScheduleSession::query()
            ->where('therapist_id', $session->therapist_id)
            ->where('status', 'inprogress')
            ->where('id', '!=', $session->id)
            ->exists();

        if ($alreadyActive) {
            throw ValidationException::withMessages([
                'session' => 'You already have a session in progress.',
            ]);
        }

        $session->update([
            'status' => 'inprogress',
            'start_time' => now(),
        ]);

        AuditLogger::log('Started session', 'System', "Started session #{$session->id}", 'info');

        return back()->with('success', 'Session started.');
    }

    public function endSession(Request $request, ScheduleSession $session): RedirectResponse
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $endTime = now();
        $elapsedSeconds = $session->start_time ? (int) $session->start_time->diffInSeconds($endTime) : 0;

        $session->update([
            'status' => 'pending',
            'end_time' => $endTime,
            'elapsed_time' => gmdate('H:i:s', $elapsedSeconds),
            'notes' => $validated['notes'] ?? $session->notes,
        ]);

        /*
         * The contract draw is deliberately left alone. Hours come off a
         * contract at the length the session was booked for, so a visit that
         * ran ten minutes over or short does not move the balance. Admin
         * authorizes scheduled time; `elapsed_time` above still records what
         * the clock said.
         */
        AuditLogger::log('Ended session', 'System', "Ended session #{$session->id}", 'info');

        return back()->with('success', 'Session ended.');
    }

    public function byUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'role' => ['required', 'in:client,therapist'],
        ]);

        $user = $request->user();
        $subjectId = (int) $validated['user_id'];

        if ($validated['role'] === 'client') {
            $this->assertCanReadClientDiary($user, $subjectId);
            $column = 'client_id';
        } else {
            $this->assertCanReadTherapistDiary($user, $subjectId);
            $column = 'therapist_id';
        }

        return response()->json(
            ScheduleSession::query()
                ->where($column, $subjectId)
                // The complaint and invoice session pickers label each option
                // with every service the visit covers.
                ->with(['service', 'clientServices.service'])
                ->orderBy('scheduled_start')
                ->get(),
        );
    }

    public function byUserAndService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
        ]);

        $this->assertCanReadClientDiary($request->user(), (int) $validated['user_id']);

        return response()->json(
            ScheduleSession::query()
                ->where('client_id', $validated['user_id'])
                ->where('service_id', $validated['service_id'])
                ->orderBy('scheduled_start')
                ->get(),
        );
    }

    public function byClientService(Request $request, ClientService $clientService): JsonResponse
    {
        $this->assertCanReadClientDiary($request->user(), (int) $clientService->client_id);

        return response()->json(
            $clientService->sessions()
                ->orderBy('scheduled_start')
                ->get(),
        );
    }

    public function byTherapist(User $user): JsonResponse
    {
        return response()->json(
            ScheduleSession::query()->where('therapist_id', $user->id)->orderBy('scheduled_start')->get(),
        );
    }

    /**
     * Admin-only "ServiceSessions" page (reference: cats-frontend/src/pages/ServiceSessions.tsx),
     * linked from the client Documents tab's Sessions card.
     */
    public function clientServiceSessions(Request $request, Client $client, ClientService $clientService): Response
    {
        $clientService->load('service');

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $sessions = $clientService->sessions()
            ->with('therapist')
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where('location', 'like', "%{$search}%"))
            ->orderByDesc('scheduled_start')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/clients/service-sessions', [
            'client' => $client->load('originalIntake'),
            'clientService' => $clientService,
            'sessions' => $sessions,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    /*
     * The rules themselves live in ScheduleSessionPolicy (Phase 18). These
     * wrappers stay so call sites read the same, and so a denial keeps
     * returning 404 rather than the 403 `authorize()` would raise — this app
     * deliberately doesn't confirm that someone else's record exists.
     */

    /**
     * These lookup endpoints take an id straight from the query string, so
     * the role guard alone would let any therapist read another's caseload —
     * and any parent read another family's diary. Ownership has to be
     * checked against the caller, not just their role.
     */
    private function assertCanReadClientDiary(User $user, int $clientId): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTherapist()) {
            abort_unless(
                Client::query()->whereKey($clientId)->forTherapist($user->id)->exists(),
                404,
            );

            return;
        }

        abort_unless(app(ClientContext::class)->owns($user, $clientId), 404);
    }

    private function assertCanReadTherapistDiary(User $user, int $therapistId): void
    {
        // A therapist may only read their own diary; a parent, none at all.
        abort_unless(
            $user->isAdmin() || ($user->isTherapist() && $user->id === $therapistId),
            404,
        );
    }

    private function assertOwnsOrAdmin(ScheduleSession $session, User $user): void
    {
        abort_unless($user->can('manage', $session), 404);
    }

    private function assertOwnsAsClient(ScheduleSession $session, User $user): void
    {
        abort_unless($user->can('verify', $session), 404);
    }

    private function assertParticipant(ScheduleSession $session, User $user): void
    {
        abort_unless($user->can('dispute', $session), 404);
    }

    /**
     * `update()` also handles edits that leave the appointment where it is —
     * retitling the notes, relinking a service. Only a genuine move of the
     * start time, or a handover to a different therapist, is worth emailing
     * the parent about; anything looser turns every save into inbox noise.
     */
    private function wasRescheduled(ScheduleSession $session, ?CarbonInterface $previousStart): bool
    {
        if ($session->wasChanged('therapist_id')) {
            return true;
        }

        $currentStart = $session->scheduled_start;

        if ($previousStart === null || $currentStart === null) {
            return $previousStart !== $currentStart;
        }

        return ! $previousStart->equalTo($currentStart);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, array{client_service_id: int, hours: float}>  $allocations
     * @return array<string, mixed>
     */
    private function scheduleAttributes(array $validated, array $allocations): array
    {
        $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
        $end = Carbon::parse("{$validated['date']} {$validated['end_time']}");

        return [
            'client_id' => $validated['client_id'],
            'service_id' => $validated['service_id'] ?? $this->leadServiceIdOf(array_column($allocations, 'client_service_id')),
            'location' => $validated['location'] ?? null,
            // Still stored, just derived rather than picked: ClientProgress
            // and the session emails both read it.
            'duration' => (int) $start->diffInMinutes($end),
            'notes' => $validated['notes'] ?? null,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
        ];
    }

    /**
     * A session covering several availed services still carries one
     * `service_id`, used by the list filters and as the invoice fallback, so
     * the first one picked stands for the visit. Therapists don't get a
     * separate "Service" field — the availed services they chose already say
     * what's being delivered — so it's resolved here rather than posted.
     *
     * @param  array<int, int>  $clientServiceIds
     */
    private function leadServiceIdOf(array $clientServiceIds): ?int
    {
        if ($clientServiceIds === []) {
            return null;
        }

        $services = ClientService::query()->findMany($clientServiceIds)->keyBy('id');

        foreach ($clientServiceIds as $id) {
            if ($services->has($id)) {
                return $services->get($id)->service_id;
            }
        }

        return null;
    }

    private function routeName(Request $request, string $suffix): string
    {
        return $request->user()->isAdmin() ? "admin.{$suffix}" : "therapist.{$suffix}";
    }

    /**
     * @return Collection<int, User>
     */
    private function therapists(): Collection
    {
        return User::query()
            ->where('role', 'therapist')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    /**
     * @return Collection<int, ServiceOffering>
     */
    private function services(): Collection
    {
        return ServiceOffering::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * Clients selectable in the session form.
     *
     * Therapists only ever see their own caseload — the matching server-side
     * guard lives in StoreSessionRequest so a hand-crafted `client_id` can't
     * bypass it — and within that, only the clients who still have an availed
     * service of theirs left to book. A child whose services under this
     * therapist have all been scheduled (or already delivered) drops off the
     * list, while the same child stays visible to another therapist holding
     * an unscheduled service for them.
     *
     * The session being edited is always kept in the list, otherwise
     * reopening a booked session would render an empty client field.
     *
     * @return Collection<int, Client>
     */
    private function clientOptions(User $user, ?ScheduleSession $session = null): Collection
    {
        $isAdmin = $user->isAdmin();

        /*
         * Bookability now depends on a date, because a contract covers one.
         * The picker is opened today and defaults to today, so that is the
         * day it asks about; the request validator asks again about whatever
         * date the therapist finally chose.
         */
        $today = Carbon::today();

        $bookableClientIds = ClientService::query()
            ->select('client_id')
            ->where('therapist_id', $user->id)
            ->bookableOn($today);

        $clients = Client::query()
            ->when(! $isAdmin, function (Builder $query) use ($bookableClientIds, $user, $session): void {
                $query->forTherapist($user->id)
                    ->where(function (Builder $selectable) use ($bookableClientIds, $session): void {
                        $selectable->whereIn('clients.id', $bookableClientIds);

                        if ($session?->client_id !== null) {
                            $selectable->orWhere('clients.id', $session->client_id);
                        }
                    });
            })
            ->with([
                'originalIntake:id,child_first_name,child_last_name,available_days,preferred_times',
                'clientServices' => $this->selectableClientServices($user, $session, $today),
            ])
            ->get(['id', 'original_intake_id']);

        $this->attachContractSummaries($clients, $today);

        return $clients;
    }

    /**
     * Hang the covering contract's numbers on each availed service so the
     * form can show what is left before the therapist picks anything.
     *
     * The balances are fetched for every contract in one query rather than
     * per service — a full caseload would otherwise cost a `SUM` apiece.
     *
     * @param  Collection<int, Client>  $clients
     */
    private function attachContractSummaries(Collection $clients, CarbonInterface $date): void
    {
        /** @var Collection<int, ClientService> $services */
        $services = $clients->flatMap(fn (Client $client): Collection => $client->clientServices);

        $covering = $services->mapWithKeys(fn (ClientService $service): array => [
            $service->id => $service->contracts->first(
                fn (ServiceContract $contract): bool => $contract->status !== ServiceContract::STATUS_CANCELLED
                    && $contract->coversDate($date),
            ),
        ]);

        $remaining = $this->ledger->remainingFor($covering->filter());

        foreach ($services as $service) {
            /** @var ServiceContract|null $contract */
            $contract = $covering->get($service->id);

            $service->setAttribute('contract', $contract === null ? null : [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'allotted_hours' => (float) $contract->allotted_hours,
                'remaining_hours' => $remaining[$contract->id] ?? 0.0,
                'period_start' => $contract->period_start?->toDateString(),
                'period_end' => $contract->period_end?->toDateString(),
            ]);

            // The raw contract rows were only ever a means to that summary.
            $service->unsetRelation('contracts');
        }
    }

    /**
     * Eager-load constraint keeping the form's "Client Service" dropdown in
     * step with the client one: a therapist only picks from their own availed
     * services that are still awaiting a booking, plus whichever ones the
     * session being edited is already linked to.
     *
     * @return Closure(Relation<*, *, *>): void
     */
    private function selectableClientServices(User $user, ?ScheduleSession $session, CarbonInterface $date): Closure
    {
        $alreadyLinkedIds = $session?->clientServices()->pluck('client_services.id')->all() ?? [];

        return function (Relation $services) use ($alreadyLinkedIds, $user, $date): void {
            // `contracts` feeds the remaining-hours summary the form shows
            // against each option, and is dropped again once it has.
            $services->with(['service', 'contracts']);

            if ($user->isAdmin()) {
                return;
            }

            $services->where('therapist_id', $user->id)
                ->where(function (Builder $selectable) use ($alreadyLinkedIds, $date): void {
                    $selectable->whereIn(
                        'client_services.id',
                        ClientService::query()->select('id')->bookableOn($date),
                    );

                    /*
                     * A session already holding a service keeps it in the
                     * list even if its contract has since run dry, or a
                     * booking made last month could never be corrected.
                     */
                    if ($alreadyLinkedIds !== []) {
                        $selectable->orWhereIn('client_services.id', $alreadyLinkedIds);
                    }
                });
        };
    }
}
