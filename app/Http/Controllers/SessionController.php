<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $therapistId = $request->user()->isAdmin() ? (int) $validated['therapist_id'] : $request->user()->id;

        $session = ScheduleSession::query()->create([
            ...$this->scheduleAttributes($validated),
            'therapist_id' => $therapistId,
            'status' => 'scheduled',
        ]);

        $session->clientServices()->sync($this->linkedClientServiceIds($validated));

        AuditLogger::log('Scheduled session', 'System', "Scheduled session #{$session->id}");

        return to_route($this->routeName($request, 'sessions.index'))->with('success', 'Session scheduled successfully.');
    }

    public function edit(Request $request, ScheduleSession $session): Response
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        return Inertia::render('sessions/edit', [
            'session' => $session->load('clientServices:id'),
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
        $therapistId = $request->user()->isAdmin() ? (int) $validated['therapist_id'] : $session->therapist_id;

        $session->update([
            ...$this->scheduleAttributes($validated),
            'therapist_id' => $therapistId,
        ]);

        $session->clientServices()->sync($this->linkedClientServiceIds($validated));

        AuditLogger::log('Updated session', 'System', "Updated session #{$session->id}");

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
     * validates date/duration/location fields irrelevant to a status flip.
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

        AuditLogger::log('Ended session', 'System', "Ended session #{$session->id}", 'info');

        return back()->with('success', 'Session ended.');
    }

    public function byUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'role' => ['required', 'in:client,therapist'],
        ]);

        $column = $validated['role'] === 'client' ? 'client_id' : 'therapist_id';

        return response()->json(
            ScheduleSession::query()
                ->where($column, $validated['user_id'])
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

        return response()->json(
            ScheduleSession::query()
                ->where('client_id', $validated['user_id'])
                ->where('service_id', $validated['service_id'])
                ->orderBy('scheduled_start')
                ->get(),
        );
    }

    public function byClientService(ClientService $clientService): JsonResponse
    {
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
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function scheduleAttributes(array $validated): array
    {
        $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
        $minutes = (int) $validated['duration'];
        $linkedIds = $this->linkedClientServiceIds($validated);

        return [
            'client_id' => $validated['client_id'],
            'service_id' => $validated['service_id'] ?? $this->leadServiceIdOf($linkedIds),
            'location' => $validated['location'] ?? null,
            'duration' => $minutes,
            'notes' => $validated['notes'] ?? null,
            'scheduled_start' => $start,
            'scheduled_end' => (clone $start)->addMinutes($minutes),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, int>
     */
    private function linkedClientServiceIds(array $validated): array
    {
        $ids = $validated['linked_client_service_ids'] ?? [];

        if (! is_array($ids)) {
            return [];
        }

        $unique = [];

        foreach ($ids as $id) {
            $unique[(int) $id] = true;
        }

        return array_keys($unique);
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

        $bookableClientIds = ClientService::query()
            ->select('client_id')
            ->where('therapist_id', $user->id)
            ->awaitingSchedule();

        return Client::query()
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
                'clientServices' => $this->selectableClientServices($user, $session),
            ])
            ->get(['id', 'original_intake_id']);
    }

    /**
     * Eager-load constraint keeping the form's "Client Service" dropdown in
     * step with the client one: a therapist only picks from their own availed
     * services that are still awaiting a booking, plus whichever ones the
     * session being edited is already linked to.
     *
     * @return Closure(Relation<*, *, *>): void
     */
    private function selectableClientServices(User $user, ?ScheduleSession $session): Closure
    {
        $bookableIds = ClientService::query()->select('id')->awaitingSchedule();

        $alreadyLinkedIds = $session?->clientServices()->pluck('client_services.id')->all() ?? [];

        if ($alreadyLinkedIds !== []) {
            $bookableIds->orWhereIn('id', $alreadyLinkedIds);
        }

        return function (Relation $services) use ($bookableIds, $user): void {
            $services->with('service');

            if ($user->isAdmin()) {
                return;
            }

            $services->where('therapist_id', $user->id)
                ->whereIn('client_services.id', $bookableIds);
        };
    }
}
