<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamMemberRequest;
use App\Http\Requests\Admin\UpdateTeamMemberRequest;
use App\Models\Career;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\ScheduleSession;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin team-member management (reference: cats-frontend/src/pages/admin/TeamPage.tsx
 * and TeamMemberDetailPage.tsx; business logic: cats-backend/accounts's
 * TeamMemberViewSet / TeamMemberAdminSerializer).
 */
class TeamMemberController extends Controller
{
    /**
     * Employment statuses that deactivate the linked User account, mirroring
     * TeamMemberAdminSerializer.update()'s is_active side effect.
     *
     * @var array<int, string>
     */
    private const INACTIVE_STATUSES = ['inactive', 'on_leave', 'terminated', 'archived'];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $position = (string) $request->query('position', 'all');
        $status = (string) $request->query('status', 'all');

        $baseQuery = TeamMember::query()->with('user');

        $filtered = (clone $baseQuery)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('user', function (Builder $inner) use ($search): void {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($position !== 'all', fn (Builder $query) => $query->where('position', $position))
            ->when($status !== 'all', fn (Builder $query) => $query->where('employment_status', $status));

        $teamMembers = $filtered->latest('created_at')->paginate(12)->withQueryString();

        $teamMembers->getCollection()->transform(function (TeamMember $teamMember): TeamMember {
            $teamMember->setAttribute('caseload', $this->caseload($teamMember->user_id));

            return $teamMember;
        });

        $allMembers = (clone $baseQuery)->get();
        $activeMembers = $allMembers->where('employment_status', 'active');
        $totalCaseload = $activeMembers->sum(fn (TeamMember $teamMember): int => $this->caseload($teamMember->user_id));

        $stats = [
            'total' => $allMembers->count(),
            'active' => $activeMembers->count(),
            'caseload' => $totalCaseload,
            'avg_caseload' => $activeMembers->count() > 0 ? round($totalCaseload / $activeMembers->count(), 1) : 0,
        ];

        return Inertia::render('admin/team/index', [
            'teamMembers' => $teamMembers,
            'stats' => $stats,
            'positions' => Career::query()->distinct()->orderBy('position')->pluck('position'),
            'filters' => ['search' => $search, 'position' => $position, 'status' => $status],
        ]);
    }

    public function show(TeamMember $teamMember): Response
    {
        $teamMember->load('user', 'application');

        $career = Career::query()->where('position', $teamMember->position)->first();
        $documents = $this->documentsFor($teamMember);

        return Inertia::render('admin/team/show', [
            'teamMember' => $teamMember,
            'caseload' => $this->caseload($teamMember->user_id),
            'clients' => $this->clientsFor($teamMember->user_id),
            'recentSessions' => ScheduleSession::query()
                ->where('therapist_id', $teamMember->user_id)
                ->with(['client.originalIntake', 'service'])
                ->latest('scheduled_start')
                ->limit(10)
                ->get(),
            'documents' => $documents,
            'missingDocuments' => array_values(array_diff(
                $career !== null ? ($career->required_documents ?? []) : [],
                $documents->pluck('doc_type')->all(),
            )),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/team/create');
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = Str::lower(trim($validated['email']));

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::query()->create([
                'email' => $email,
                'password' => Str::random(10),
                'role' => 'therapist',
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);
        }

        $teamMember = TeamMember::query()->create([
            ...$this->teamMemberAttributes($validated),
            'user_id' => $user->id,
        ]);

        AuditLogger::log('Created team member', 'Users', "Created team member {$user->email}");

        return to_route('admin.team.show', $teamMember)->with('success', 'Team member added successfully.');
    }

    public function edit(TeamMember $teamMember): Response
    {
        return Inertia::render('admin/team/edit', [
            'teamMember' => $teamMember->load('user'),
        ]);
    }

    public function update(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $validated = $request->validated();

        $teamMember->update($this->teamMemberAttributes($validated));

        $this->syncUserActiveState($teamMember);

        AuditLogger::log('Updated team member', 'Users', "Updated team member #{$teamMember->id}");

        return to_route('admin.team.show', $teamMember)->with('success', 'Team member updated successfully.');
    }

    public function destroy(TeamMember $teamMember): RedirectResponse
    {
        $teamMember->delete();

        AuditLogger::log('Deleted team member', 'Users', "Deleted team member #{$teamMember->id}", 'warning');

        return to_route('admin.team.index')->with('success', 'Team member removed successfully.');
    }

    /**
     * The "Manage Access and Status" modal action.
     */
    public function updateAccess(Request $request, TeamMember $teamMember): RedirectResponse
    {
        $validated = $request->validate([
            'can_access_finance' => ['required', 'boolean'],
            'can_manage_team' => ['required', 'boolean'],
            'can_manage_clients' => ['required', 'boolean'],
            'employment_status' => ['required', Rule::in(StoreTeamMemberRequest::EMPLOYMENT_STATUSES)],
        ]);

        $teamMember->update($validated);

        $this->syncUserActiveState($teamMember);

        AuditLogger::log('Updated access and status', 'Users', "Updated access/status for team member #{$teamMember->id}");

        return back()->with('success', 'Access and status updated successfully.');
    }

    public function uploadDocument(Request $request, TeamMember $teamMember, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $uploaded = $drive->upload($request->file('file'), 'therapists', $this->teamMemberFolderName($teamMember));

        ClientDocument::query()->create([
            'user_id' => $teamMember->user_id,
            'title' => $validated['name'] ?? $request->file('file')->getClientOriginalName(),
            'doc_type' => $validated['type'],
            ...$uploaded,
            // 'client'/'intake' upload_origin values don't apply here — this
            // document belongs to the team member's own User record, scoped
            // by `user_id` (client/intake documents never set `user_id`).
            'upload_origin' => 'admin',
            'uploaded_by_id' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        $this->refreshDocumentsCache($teamMember);

        AuditLogger::log('Uploaded team member document', 'Users', "Uploaded a document for team member #{$teamMember->id}");

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(ClientDocument $document, DriveStorage $drive): RedirectResponse
    {
        $drive->delete($document->drive_file_id);

        $teamMember = TeamMember::query()->where('user_id', $document->user_id)->first();

        $document->delete();

        if ($teamMember) {
            $this->refreshDocumentsCache($teamMember);
        }

        AuditLogger::log('Deleted team member document', 'Users', "Deleted document #{$document->id}", 'warning');

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Therapist self-service view — restricted field set, matching
     * TeamMemberSelfSerializer's read-only employment fields.
     */
    public function me(Request $request): Response
    {
        $teamMember = TeamMember::query()->where('user_id', $request->user()->id)->firstOrFail();
        $teamMember->load('user');

        $career = Career::query()->where('position', $teamMember->position)->first();
        $documents = $this->documentsFor($teamMember);

        return Inertia::render('therapist/profile', [
            'teamMember' => $teamMember,
            'documents' => $documents,
            'missingDocuments' => array_values(array_diff(
                $career !== null ? ($career->required_documents ?? []) : [],
                $documents->pluck('doc_type')->all(),
            )),
        ]);
    }

    public function updateMe(Request $request): RedirectResponse
    {
        $teamMember = TeamMember::query()->where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'office_phone' => ['nullable', 'string', 'max:20'],
            'secondary_email' => ['nullable', 'email', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'availability' => ['nullable', 'array', 'size:7'],
            'availability.*.week_day' => ['required_with:availability', 'string'],
            'availability.*.time_from' => ['nullable', 'string'],
            'availability.*.time_to' => ['nullable', 'string'],
        ]);

        $teamMember->update($validated);

        AuditLogger::log('Updated profile', 'Users', "{$request->user()->email} updated their profile");

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Same body as uploadDocument(), but resolves the team member from the
     * authenticated user instead of route-model-binding — a therapist has
     * no business hitting another member's document routes.
     */
    public function uploadMyDocument(Request $request, DriveStorage $drive): RedirectResponse
    {
        $teamMember = TeamMember::query()->where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $uploaded = $drive->upload($request->file('file'), 'therapists', $this->teamMemberFolderName($teamMember));

        ClientDocument::query()->create([
            'user_id' => $teamMember->user_id,
            'title' => $validated['name'] ?? $request->file('file')->getClientOriginalName(),
            'doc_type' => $validated['type'],
            ...$uploaded,
            'upload_origin' => 'admin',
            'uploaded_by_id' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        $this->refreshDocumentsCache($teamMember);

        AuditLogger::log('Uploaded document', 'Users', "{$request->user()->email} uploaded their own document");

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteMyDocument(Request $request, ClientDocument $document, DriveStorage $drive): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id, 404);

        $drive->delete($document->drive_file_id);

        $teamMember = TeamMember::query()->where('user_id', $document->user_id)->first();

        $document->delete();

        if ($teamMember) {
            $this->refreshDocumentsCache($teamMember);
        }

        AuditLogger::log('Deleted document', 'Users', "{$request->user()->email} deleted their own document #{$document->id}", 'warning');

        return back()->with('success', 'Document deleted successfully.');
    }

    private function caseload(int $userId): int
    {
        return Client::query()
            ->where('primary_therapist_id', $userId)
            ->orWhere('assigned_therapist_id', $userId)
            ->distinct()
            ->count('id');
    }

    /**
     * @return Collection<int, Client>
     */
    private function clientsFor(int $userId): Collection
    {
        return Client::query()
            ->where('primary_therapist_id', $userId)
            ->orWhere('assigned_therapist_id', $userId)
            ->with('originalIntake')
            ->distinct()
            ->get();
    }

    /**
     * @return Collection<int, ClientDocument>
     */
    private function documentsFor(TeamMember $teamMember): Collection
    {
        return ClientDocument::query()
            ->where('user_id', $teamMember->user_id)
            ->get();
    }

    private function refreshDocumentsCache(TeamMember $teamMember): void
    {
        $teamMember->update([
            'documents' => $this->documentsFor($teamMember)->pluck('id')->all(),
        ]);
    }

    private function teamMemberFolderName(TeamMember $teamMember): string
    {
        $user = $teamMember->user ?? User::query()->find($teamMember->user_id);
        $name = trim("{$user?->first_name} {$user?->last_name}");

        return trim("{$teamMember->id}_{$name}", '_');
    }

    private function syncUserActiveState(TeamMember $teamMember): void
    {
        $teamMember->user()->update([
            'is_active' => ! in_array($teamMember->employment_status, self::INACTIVE_STATUSES, true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function teamMemberAttributes(array $validated): array
    {
        return collect($validated)->except(['email'])->all();
    }
}
