<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Models\Client;
use App\Models\Complaint;
use App\Models\ScheduleSession;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ClientContext;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared complaint/dispute controller, reachable from the admin, therapist,
 * and client role route groups (reference: cats-backend/cats/views.py
 * ComplaintViewSet and cats-frontend/src/pages/admin/MessagesPage.tsx).
 * Disputes (`type=disputes`) are only ever created by
 * SessionController::dispute() — this controller's store() only ever
 * creates `type=complaints` rows, matching the reference's form.
 */
class ComplaintController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const CONSENT_TEXT = [
        'client' => 'I understand this complaint will be reviewed by CATS administration and may be shared with the relevant therapist as part of the review process.',
        'therapist' => 'I understand this complaint will be reviewed by CATS administration and handled in accordance with organizational policy.',
    ];

    public function __construct(private ClientContext $clientContext) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = $this->scopedQuery($user);

        $type = (string) $request->query('type', 'all');
        $status = (string) $request->query('status', 'all');

        $filtered = (clone $baseQuery)
            ->when($type !== 'all', fn (Builder $query) => $query->where('type', $type))
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status));

        $complaints = (clone $filtered)
            ->with(['client.originalIntake', 'therapist', 'session', 'reviewedBy', 'resolvedBy'])
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'complaints' => [
                'open' => (clone $baseQuery)->where('type', 'complaints')->where('status', 'open')->count(),
                'under_review' => (clone $baseQuery)->where('type', 'complaints')->where('status', 'under_review')->count(),
                'total' => (clone $baseQuery)->where('type', 'complaints')->count(),
            ],
            'disputes' => [
                'open' => (clone $baseQuery)->where('type', 'disputes')->where('status', 'open')->count(),
                'under_review' => (clone $baseQuery)->where('type', 'disputes')->where('status', 'under_review')->count(),
                'total' => (clone $baseQuery)->where('type', 'disputes')->count(),
            ],
        ];

        return Inertia::render('complaints/index', [
            'complaints' => $complaints,
            'stats' => $stats,
            'filters' => ['type' => $type, 'status' => $status],
            'role' => $user->role,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $prefix = $user->isTherapist() ? 'therapist' : 'client';
        $sessionUserId = $user->isTherapist() ? $user->id : $this->clientContext->currentId($user);

        return Inertia::render('complaints/create', [
            'role' => $user->role,
            'basePath' => "/{$prefix}/complaints",
            'sessionsUrl' => "/{$prefix}/sessions/by-user?user_id={$sessionUserId}&role={$prefix}",
        ]);
    }

    public function store(StoreComplaintRequest $request, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $complainedBy = $user->isClient() ? 'client' : 'therapist';

        $attributes = [
            'session_id' => $validated['session_id'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'complained_by' => $complainedBy,
            'status' => 'open',
            'type' => 'complaints',
            'consent_given' => true,
            'consent_info' => self::CONSENT_TEXT[$complainedBy],
            'consent_at' => now(),
            'ip_address' => $request->ip(),
        ];

        $session = ScheduleSession::query()->find((int) $validated['session_id']);

        // StoreComplaintRequest only checks that the session exists, so
        // ownership is enforced here — otherwise either role could file
        // against a session they had nothing to do with.
        if ($complainedBy === 'client') {
            abort_unless($this->clientContext->owns($user, $session?->client_id), 404);

            // Attribute the complaint to the child the session was actually
            // for, not whichever child the switcher happens to have selected.
            $attributes['client_id'] = $session->client_id;
            $attributes['therapist_id'] = $session->therapist_id;
        } else {
            abort_unless($session?->therapist_id === $user->id, 404);

            $attributes['therapist_id'] = $user->id;
            $attributes['client_id'] = $session->client_id;
        }

        if ($request->hasFile('file')) {
            $client = Client::query()->find($attributes['client_id']);
            $attributes = [...$attributes, ...$drive->upload($request->file('file'), 'client', $this->clientFolderName($client))];
        }

        $complaint = Complaint::query()->create($attributes);

        AuditLogger::log('Filed complaint', 'System', "Filed a {$complaint->type} #{$complaint->id}", 'warning');

        return to_route($this->routeName($request, 'complaints.index'))->with('success', 'Complaint filed successfully.');
    }

    private function clientFolderName(?Client $client): string
    {
        if ($client === null) {
            return 'unknown';
        }

        $intake = $client->originalIntake;
        $name = trim("{$intake?->child_first_name} {$intake?->child_last_name}");

        return trim("{$intake?->id}_{$name}", '_');
    }

    public function startReview(Request $request, Complaint $complaint): RedirectResponse
    {
        abort_unless($request->user()->can('review', $complaint), 404);

        $complaint->update([
            'status' => 'under_review',
            'reviewed_at' => now(),
            'reviewed_by_id' => $request->user()->id,
        ]);

        AuditLogger::log('Started complaint review', 'System', "Started review of complaint #{$complaint->id}");

        return back()->with('success', 'Review started.');
    }

    public function resolve(Request $request, Complaint $complaint): RedirectResponse
    {
        abort_unless($request->user()->can('review', $complaint), 404);

        $validated = $request->validate([
            'admin_response' => ['required', 'string'],
        ]);

        $complaint->update([
            'status' => 'resolved',
            'admin_response' => $validated['admin_response'],
            'resolved_by_id' => $request->user()->id,
            'resolve_at' => now(),
        ]);

        AuditLogger::log('Resolved complaint', 'System', "Resolved complaint #{$complaint->id}");

        return back()->with('success', 'Complaint resolved.');
    }

    /**
     * @return Builder<Complaint>
     */
    private function scopedQuery(User $user): Builder
    {
        if ($user->isAdmin()) {
            return Complaint::query();
        }

        if ($user->isTherapist()) {
            return Complaint::query()
                ->where('therapist_id', $user->id)
                ->where('complained_by', 'therapist');
        }

        // The list follows the portal switcher — one child at a time.
        return Complaint::query()
            ->where('client_id', $this->clientContext->currentId($user) ?? 0)
            ->where('complained_by', 'client');
    }

    private function routeName(Request $request, string $suffix): string
    {
        $user = $request->user();
        $prefix = $user->isAdmin() ? 'admin' : ($user->isTherapist() ? 'therapist' : 'client');

        return "{$prefix}.{$suffix}";
    }
}
