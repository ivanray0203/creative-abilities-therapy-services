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
use Illuminate\Database\Eloquent\Builder;
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
            ->with(['client.originalIntake', 'therapist', 'service'])
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
            'clients' => $this->clientOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('sessions/create', [
            'isAdmin' => $request->user()->isAdmin(),
            'therapists' => $request->user()->isAdmin() ? $this->therapists() : [],
            'services' => $this->services(),
            'clients' => $this->clientOptions(),
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

        AuditLogger::log('Scheduled session', 'System', "Scheduled session #{$session->id}");

        return to_route($this->routeName($request, 'sessions.index'))->with('success', 'Session scheduled successfully.');
    }

    public function edit(Request $request, ScheduleSession $session): Response
    {
        $this->assertOwnsOrAdmin($session, $request->user());

        return Inertia::render('sessions/edit', [
            'session' => $session,
            'isAdmin' => $request->user()->isAdmin(),
            'therapists' => $request->user()->isAdmin() ? $this->therapists() : [],
            'services' => $this->services(),
            'clients' => $this->clientOptions(),
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
            ScheduleSession::query()->where($column, $validated['user_id'])->orderBy('scheduled_start')->get(),
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
            ScheduleSession::query()
                ->where('linked_client_service_id', $clientService->id)
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

        $sessions = ScheduleSession::query()
            ->where('linked_client_service_id', $clientService->id)
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

    private function assertOwnsOrAdmin(ScheduleSession $session, User $user): void
    {
        if (! $user->isAdmin() && $session->therapist_id !== $user->id) {
            abort(404);
        }
    }

    private function assertOwnsAsClient(ScheduleSession $session, User $user): void
    {
        if ($session->client_id !== $user->clientProfile?->id) {
            abort(404);
        }
    }

    /**
     * Admin can act on any session; a therapist or client may only act on a
     * session they're actually part of.
     */
    private function assertParticipant(ScheduleSession $session, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTherapist() && $session->therapist_id === $user->id) {
            return;
        }

        if ($user->isClient() && $session->client_id === $user->clientProfile?->id) {
            return;
        }

        abort(404);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function scheduleAttributes(array $validated): array
    {
        $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
        $minutes = $this->durationMinutes($validated['duration']);

        return [
            'client_id' => $validated['client_id'],
            'linked_client_service_id' => $validated['linked_client_service_id'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'location' => $validated['location'] ?? null,
            'duration' => $validated['duration'],
            'notes' => $validated['notes'] ?? null,
            'scheduled_start' => $start,
            'scheduled_end' => (clone $start)->addMinutes($minutes),
        ];
    }

    private function durationMinutes(string $duration): int
    {
        preg_match('/\d+/', $duration, $matches);

        return isset($matches[0]) ? (int) $matches[0] : 60;
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
     * @return Collection<int, Client>
     */
    private function clientOptions(): Collection
    {
        return Client::query()
            ->with('originalIntake:id,child_first_name,child_last_name,available_days,preferred_times')
            ->get(['id', 'original_intake_id'])
            ->load('clientServices.service');
    }
}
