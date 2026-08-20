<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIntakeRequest;
use App\Http\Requests\Admin\UpdateIntakeRequest;
use App\Mail\IntakeAssignedToTherapistMail;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Intake;
use App\Models\IntakeDocument;
use App\Models\IntakeTherapistApproval;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\ServiceOffering;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\IntakeApprovalService;
use App\Services\IntakeSubmissionService;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin intake pipeline (reference: cats-frontend/src/pages/admin/IntakePage.tsx
 * and IntakeDetailPage.tsx; business logic: cats-backend/cats/views.py IntakeViewSet).
 */
class IntakeController extends Controller
{
    /**
     * FSCD funding-source variants, grouped into a single "FSCD" bucket in
     * the funding overview and the funding filter.
     *
     * @var array<int, string>
     */
    private const FSCD_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

    /**
     * Status transitions the reference's "Change Status" panel offers per
     * current status. Shared by the frontend dropdown and this controller's
     * server-side validation.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['under_review', 'denied'],
        'under_review' => ['approved', 'waitlist', 'denied'],
        'waitlist' => ['approved', 'denied'],
        'approved' => ['approved'],
        'denied' => ['denied'],
    ];

    public function index(Request $request): Response
    {
        $baseQuery = Intake::query()->where('approved_as_client', false);

        /*
         * A promoted intake normally leaves this list — the child is a client
         * now. One a therapist refused a service on is the exception: the
         * refusal is still outstanding work, and dropping the row would leave
         * nowhere to pick it up from. The stats stay on the intake pipeline
         * proper, so these don't inflate the pending counts.
         */
        $listQuery = Intake::query()->where(function (Builder $query): void {
            $query->where('approved_as_client', false)
                ->orWhereHas('therapistReviews', function (Builder $reviews): void {
                    $reviews->where('status', 'rejected');
                });
        });

        $stats = [
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'under_review' => (clone $baseQuery)->where('status', 'under_review')->count(),
            'waitlist' => (clone $baseQuery)->where('status', 'waitlist')->count(),
            'denied' => (clone $baseQuery)->where('status', 'denied')->count(),
            'fscd' => (clone $baseQuery)->whereIn('funding_source', self::FSCD_SOURCES)->count(),
            'insurance' => (clone $baseQuery)->where('funding_source', 'Insurance')->count(),
            'private' => (clone $baseQuery)->where('funding_source', 'private')->count(),
        ];

        $search = trim((string) $request->query('search', ''));
        $funding = (string) $request->query('funding', 'all');

        $intakes = $listQuery
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('child_first_name', 'like', "%{$search}%")
                        ->orWhere('child_last_name', 'like', "%{$search}%")
                        ->orWhere('primary_parent_name', 'like', "%{$search}%");
                });
            })
            ->when($funding === 'fscd', function (Builder $query): void {
                $query->whereIn('funding_source', self::FSCD_SOURCES);
            })
            ->when(in_array($funding, ['Insurance', 'private'], true), function (Builder $query) use ($funding): void {
                $query->where('funding_source', $funding);
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $reviews = IntakeTherapistApproval::query()
            ->whereIn('intake_id', $intakes->pluck('id'))
            ->get()
            ->groupBy('intake_id');

        $intakes->getCollection()->transform(function (Intake $intake) use ($reviews): Intake {
            return $this->withStatusBadge($intake, $reviews->get($intake->id, collect()));
        });

        return Inertia::render('admin/intake/index', [
            'intakes' => $intakes,
            'stats' => $stats,
            'filters' => ['search' => $search, 'funding' => $funding],
        ]);
    }

    public function show(Intake $intake): Response
    {
        $intake->load([
            'documents',
            'therapistReviews.therapist',
            'therapistReviewHistory.therapist',
            'assignedTherapist',
        ]);

        return Inertia::render('admin/intake/show', [
            'intake' => $this->withStatusBadge($intake, $intake->therapistReviews),
            'therapists' => $this->therapists(),
            'statusTransitions' => self::STATUS_TRANSITIONS[$intake->status] ?? [],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/intake/create');
    }

    public function store(StoreIntakeRequest $request, ReferenceNumberGenerator $referenceNumberGenerator): RedirectResponse
    {
        $validated = $request->validated();
        $dateOfBirth = Carbon::parse($validated['date_of_birth']);

        $intake = Intake::query()->create([
            ...$this->intakeAttributes($validated),
            'date_of_birth' => $dateOfBirth,
            'age' => $dateOfBirth->age,
            'status' => $validated['status'] ?? 'pending',
            'timeline' => [$this->timelineEntry('Intake form submitted via website')],
            'reference_number' => $referenceNumberGenerator->intake(),
        ]);

        AuditLogger::log('Created intake', 'Intake', "Created intake #{$intake->id} ({$intake->reference_number})");

        return to_route('admin.intake.show', $intake)->with('success', 'Intake created successfully.');
    }

    public function edit(Intake $intake): Response
    {
        $intake->load('documents');

        return Inertia::render('admin/intake/edit', [
            'intake' => $intake,
        ]);
    }

    public function update(UpdateIntakeRequest $request, Intake $intake): RedirectResponse
    {
        $validated = $request->validated();
        $dateOfBirth = Carbon::parse($validated['date_of_birth']);

        $attributes = [
            ...$this->intakeAttributes($validated),
            'date_of_birth' => $dateOfBirth,
            'age' => $dateOfBirth->age,
            'status' => $validated['status'] ?? $intake->status,
            'timeline' => [...($intake->timeline ?? []), $this->timelineEntry('Intake Edited via Admin')],
        ];

        if ($request->primaryParentEmailIsLocked()) {
            unset($attributes['primary_parent_email']);
        }

        $intake->update($attributes);

        AuditLogger::log('Updated intake', 'Intake', "Updated intake #{$intake->id}");

        return to_route('admin.intake.show', $intake)->with('success', 'Intake updated successfully.');
    }

    public function destroy(Intake $intake): RedirectResponse
    {
        $intake->delete();

        AuditLogger::log('Deleted intake', 'Intake', "Deleted intake #{$intake->id}", 'warning');

        return to_route('admin.intake.index')->with('success', 'Intake deleted successfully.');
    }

    /**
     * The reference's UpdateStatusModal: append a timeline entry, optionally
     * append an internal note, optionally assign a therapist. Moving to
     * `approved` means "assign to therapist for review", not client promotion.
     */
    public function updateStatus(Request $request, Intake $intake): RedirectResponse
    {
        $allowed = self::STATUS_TRANSITIONS[$intake->status] ?? [];

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowed)],
            'note' => ['nullable', 'string'],
            'therapist_assignments' => [
                Rule::requiredIf($request->input('status') === 'approved'),
                'array',
            ],
            'therapist_assignments.*.service' => ['nullable', 'string'],
            'therapist_assignments.*.therapist_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignments = $validated['status'] === 'approved'
            ? $this->resolveTherapistAssignments($intake, $validated['therapist_assignments'] ?? [])
            : [];

        DB::transaction(function () use ($intake, $validated, $assignments, $request): void {
            $attributes = [
                'status' => $validated['status'],
                'timeline' => [
                    ...($intake->timeline ?? []),
                    $this->timelineEntry("Updated Status to {$validated['status']} from {$intake->status}"),
                ],
            ];

            if (filled($validated['note'] ?? null)) {
                $attributes['notes'] = [
                    ...($intake->notes ?? []),
                    $this->noteEntry((string) $validated['note'], $request->user()),
                ];
            }

            if ($assignments !== []) {
                $lastTherapist = end($assignments)['therapist'];
                $attributes['assigned_therapist_id'] = $lastTherapist->id;
                $attributes['assigned_at'] = now()->toDateString();
            }

            $intake->update($attributes);

            foreach ($assignments as $assignment) {
                $this->assignTherapistForReview($intake, $assignment['therapist'], $assignment['service']);
            }

            $this->sendAssignmentEmails($intake, $assignments);
        });

        AuditLogger::log('Updated intake status', 'Intake', "Set intake #{$intake->id} status to {$validated['status']}");

        return back()->with('success', 'Intake status updated successfully.');
    }

    public function sendToTherapist(Request $request, Intake $intake): RedirectResponse
    {
        /*
         * A null service means "the whole intake", which is only meaningful
         * for an intake that lists none. Accepting it for the rest let a
         * caller that forgot the field silently create a second, parallel
         * review instead of routing the service it meant to.
         */
        $listedServices = $intake->services_needed ?? [];

        $validated = $request->validate([
            'service' => $listedServices === []
                ? ['nullable', 'string']
                : ['required', 'string', Rule::in($listedServices)],
            'therapist_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $service = $validated['service'] ?? null;
        $therapist = $this->resolveTherapist((int) $validated['therapist_id']);

        $this->assignTherapistForReview($intake, $therapist, $service);
        $this->sendAssignmentEmails($intake, [['service' => $service, 'therapist' => $therapist]]);

        AuditLogger::log('Sent intake to therapist', 'Intake', "Sent intake #{$intake->id} to {$therapist->full_name}");

        return back()->with('success', "Intake sent to {$therapist->full_name} for approval.");
    }

    /**
     * Admin direct-approve — promotes the intake straight to a Client,
     * bypassing the therapist review loop.
     */
    public function approve(Request $request, Intake $intake, IntakeApprovalService $approvalService): RedirectResponse
    {
        $validated = $request->validate([
            'therapist_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->guardAgainstDoublePromotion($intake);

        $therapist = isset($validated['therapist_id'])
            ? $this->resolveTherapist((int) $validated['therapist_id'])
            : null;

        DB::transaction(function () use ($intake, $therapist, $approvalService): void {
            if ($therapist && $intake->assigned_therapist_id !== $therapist->id) {
                $intake->update([
                    'assigned_therapist_id' => $therapist->id,
                    'assigned_at' => now()->toDateString(),
                ]);
            }

            // Unlike the Django reference (which assigns the whole tuple to
            // `client`), both halves of the promotion result are unpacked here.
            // $rawPassword feeds the Phase 15 welcome email for new accounts.
            ['client' => $client, 'rawPassword' => $rawPassword] = $approvalService->promote($intake, $therapist ? collect([$therapist]) : collect());

            unset($client, $rawPassword);
        });

        AuditLogger::log('Approved intake', 'Intake', "Approved intake #{$intake->id} and promoted to client");

        return back()->with('success', 'Intake approved and moved to clients.');
    }

    /**
     * Therapist-side approval, reachable from both the admin and therapist
     * route groups (Phase 13 adds the therapist-facing UI) — this action is
     * always meant to be the assigned therapist's own decision, so the
     * ownership guard applies unconditionally regardless of which route
     * group called it — except for admins, who retain the direct-access
     * shortcut Phase 6 built this endpoint with.
     *
     * The first approved service creates the client immediately; any later
     * service approvals on the same intake just add their therapist to the
     * existing client's care team instead of promoting again. Either way, a
     * `ClientService` row is recorded for the approved service so it shows
     * up on the client's service list (e.g. the therapist-facing client
     * card), not just in the intake's review history.
     */
    public function therapistApprove(Request $request, Intake $intake, IntakeApprovalService $approvalService): RedirectResponse
    {
        $review = $this->resolveReviewForDecision($request, $intake, ['pending', 'reassign']);

        abort_unless($request->user()->isAdmin() || $review->therapist_id === $request->user()->id, 403);

        $justPromoted = DB::transaction(function () use ($intake, $review, $approvalService): bool {
            $review->update(['status' => 'approved', 'decided_at' => now()]);

            IntakeTherapistApprovalHistory::query()->create([
                'intake_id' => $intake->id,
                'therapist_id' => $review->therapist_id,
                'service' => $review->service,
                'status' => 'approved',
                'notes' => $review->notes,
                'decided_at' => $review->decided_at,
            ]);

            $existingClient = $intake->promotedClient()->first();

            if ($existingClient) {
                $existingClient->careTeam()->syncWithoutDetaching([$review->therapist_id]);
                $client = $existingClient;
                $justPromoted = false;
            } else {
                ['client' => $client] = $approvalService->promote($intake, collect([$review->therapist]));
                $justPromoted = true;
            }

            if ($review->service !== null) {
                $this->recordClientService($client, $review->service, $review->therapist_id);
            }

            $teamMember = TeamMember::query()->where('user_id', $review->therapist_id)->first();

            if ($teamMember) {
                $clients = $teamMember->client ?? [];

                if (! in_array($client->id, $clients, true)) {
                    $clients[] = $client->id;
                    $teamMember->update(['client' => $clients]);
                }
            }

            return $justPromoted;
        });

        AuditLogger::log('Therapist approved intake', 'Intake', "Therapist approved intake #{$intake->id}".($justPromoted ? ' and client created' : ' and added to existing client'));

        return back()->with('success', $justPromoted
            ? 'Intake approved and client created.'
            : 'Service approved and added to the client.');
    }

    public function therapistReject(Request $request, Intake $intake): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $review = $this->resolveReviewForDecision($request, $intake, ['pending']);

        abort_unless($request->user()->isAdmin() || $review->therapist_id === $request->user()->id, 403);

        DB::transaction(function () use ($intake, $review, $validated): void {
            $review->update([
                'status' => 'rejected',
                'notes' => $validated['notes'] ?? '',
                'decided_at' => now(),
            ]);

            IntakeTherapistApprovalHistory::query()->create([
                'intake_id' => $intake->id,
                'therapist_id' => $review->therapist_id,
                'service' => $review->service,
                'status' => 'rejected',
                'notes' => $review->notes,
                'decided_at' => $review->decided_at,
            ]);
        });

        AuditLogger::log('Therapist rejected intake', 'Intake', "Therapist rejected intake #{$intake->id}", 'warning');

        return back()->with('success', 'Intake rejected by therapist.');
    }

    /**
     * Admin's flat list of every therapist review. Returned as JSON — no
     * Phase 6 screen consumes it; the therapist review dashboard is Phase 13.
     */
    public function therapistReviews(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'reassign'])],
            'therapist_id' => ['nullable', 'integer'],
        ]);

        $reviews = IntakeTherapistApproval::query()
            ->with(['intake.therapistReviewHistory', 'therapist'])
            ->when(isset($validated['status']), function (Builder $query) use ($validated): void {
                $query->where('status', $validated['status']);
            })
            ->when(isset($validated['therapist_id']), function (Builder $query) use ($validated): void {
                $query->where('therapist_id', $validated['therapist_id']);
            })
            ->get();

        return response()->json([
            'success' => true,
            'total' => $reviews->count(),
            'results' => $reviews,
        ]);
    }

    public function uploadDocument(Request $request, Intake $intake, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $uploaded = $drive->upload($request->file('file'), 'client', $this->intakeFolderName($intake));

        $intake->documents()->create([
            'name' => $validated['name'] ?? $request->file('file')->getClientOriginalName(),
            'type' => $validated['type'],
            ...$uploaded,
            'uploaded_at' => now(),
        ]);

        $intake->update([
            'timeline' => [
                ...($intake->timeline ?? []),
                $this->timelineEntry("Uploaded a new File {$validated['type']}"),
            ],
        ]);

        AuditLogger::log('Uploaded intake document', 'Intake', "Uploaded a document to intake #{$intake->id}");

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Bulk document upload — mirrors the reference's `intakes/multiple` action
     * for attaching several files to an intake in one request.
     */
    public function uploadMultipleDocuments(Request $request, Intake $intake, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.type' => ['required', 'string', 'max:255'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $folderName = $this->intakeFolderName($intake);

        foreach ($validated['documents'] as $entry) {
            $uploaded = $drive->upload($entry['file'], 'client', $folderName);

            $intake->documents()->create([
                'name' => $entry['name'] ?? $entry['file']->getClientOriginalName(),
                'type' => $entry['type'],
                ...$uploaded,
                'uploaded_at' => now(),
            ]);
        }

        $intake->update([
            'timeline' => [
                ...($intake->timeline ?? []),
                $this->timelineEntry('Uploaded '.count($validated['documents']).' new documents'),
            ],
        ]);

        AuditLogger::log('Uploaded intake documents', 'Intake', 'Uploaded '.count($validated['documents'])." documents to intake #{$intake->id}");

        return back()->with('success', 'Documents uploaded successfully.');
    }

    /**
     * Updates an existing intake document's metadata and, optionally, its
     * file — the reference's `PATCH intakes/{id}/documents` action.
     */
    public function updateDocument(Request $request, Intake $intake, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'integer'],
            'type' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $document = $intake->documents()->findOrFail((int) $validated['document_id']);

        $attributes = array_filter([
            'type' => $validated['type'] ?? null,
            'name' => $validated['name'] ?? null,
        ], fn ($value) => $value !== null);

        if ($request->hasFile('file')) {
            $drive->delete($document->drive_file_id);

            $attributes = [
                ...$attributes,
                ...$drive->upload($request->file('file'), 'client', $this->intakeFolderName($intake)),
                'uploaded_at' => now(),
            ];
        }

        $document->update($attributes);

        $intake->update([
            'timeline' => [
                ...($intake->timeline ?? []),
                $this->timelineEntry("Updated document {$document->type}"),
            ],
        ]);

        AuditLogger::log('Updated intake document', 'Intake', "Updated document #{$document->id} on intake #{$intake->id}");

        return back()->with('success', 'Document updated successfully.');
    }

    public function deleteDocument(IntakeDocument $document, DriveStorage $drive): RedirectResponse
    {
        $drive->delete($document->drive_file_id);

        $document->delete();

        AuditLogger::log('Deleted intake document', 'Intake', "Deleted document #{$document->id}", 'warning');

        return back()->with('success', 'Document deleted successfully.');
    }

    private function intakeFolderName(Intake $intake): string
    {
        $name = trim("{$intake->child_first_name} {$intake->child_last_name}");

        return trim("{$intake->id}_{$name}", '_');
    }

    public function addNote(Request $request, Intake $intake): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $intake->update([
            'notes' => [...($intake->notes ?? []), $this->noteEntry($validated['note'], $request->user())],
            'timeline' => [...($intake->timeline ?? []), $this->timelineEntry('Internal note added')],
        ]);

        AuditLogger::log('Added intake note', 'Intake', "Added a note to intake #{$intake->id}");

        return back()->with('success', 'Note added successfully.');
    }

    public function deleteNote(Intake $intake, string $note): RedirectResponse
    {
        $remaining = array_values(array_filter(
            $intake->notes ?? [],
            fn (array $entry): bool => ($entry['id'] ?? null) !== $note,
        ));

        $intake->update(['notes' => $remaining]);

        AuditLogger::log('Deleted intake note', 'Intake', "Deleted a note from intake #{$intake->id}", 'warning');

        return back()->with('success', 'Note deleted successfully.');
    }

    /**
     * Validates that `therapist_assignments` is non-empty and every entry's
     * `service` is one the intake actually needs (or null, for intakes with
     * no listed services), with no service assigned more than once — but
     * does NOT require every service to be covered, since a service with no
     * available specialist can be left unassigned and picked up later.
     * Resolves each `therapist_id` to a real therapist `User`.
     *
     * @param  array<int, array{service?: string|null, therapist_id: int}>  $rawAssignments
     * @return array<int, array{service: string|null, therapist: User}>
     */
    private function resolveTherapistAssignments(Intake $intake, array $rawAssignments): array
    {
        $allowedServices = ! empty($intake->services_needed) ? $intake->services_needed : [null];

        $submittedServices = array_map(
            fn (array $assignment) => $assignment['service'] ?? null,
            $rawAssignments,
        );

        $hasUnknownService = count(array_diff($submittedServices, $allowedServices)) > 0;
        $hasDuplicateService = count($submittedServices) !== count(array_unique($submittedServices));

        if ($rawAssignments === [] || $hasUnknownService || $hasDuplicateService) {
            throw ValidationException::withMessages([
                'therapist_assignments' => 'At least one requested service must be assigned to a valid therapist, with no service assigned twice.',
            ]);
        }

        return array_map(fn (array $assignment): array => [
            'service' => $assignment['service'] ?? null,
            'therapist' => $this->resolveTherapist((int) $assignment['therapist_id']),
        ], $rawAssignments);
    }

    /**
     * Shared by `sendToTherapist` and the `approved` branch of `updateStatus`
     * (reference: IntakeViewSet.send_to_therapist). One review row per
     * (intake, service) pair — `$service` is null for intakes with no listed
     * services, preserving the original single-review-per-intake shape.
     */
    private function assignTherapistForReview(Intake $intake, User $therapist, ?string $service): void
    {
        $review = $intake->therapistReviews()->where('service', $service)->first();

        if (! $review) {
            $review = IntakeTherapistApproval::query()->create([
                'intake_id' => $intake->id,
                'therapist_id' => $therapist->id,
                'service' => $service,
                'status' => 'pending',
            ]);
        } elseif ($review->status === 'pending') {
            throw ValidationException::withMessages([
                'therapist_assignments' => 'This intake already has a pending therapist approval for this service.',
            ]);
        } elseif ($review->status === 'approved') {
            throw ValidationException::withMessages([
                'therapist_assignments' => 'This service has already been approved. Cannot reassign.',
            ]);
        } else {
            $review->update([
                'therapist_id' => $therapist->id,
                'status' => 'reassign',
                'notes' => null,
                'decided_at' => null,
            ]);
        }

        IntakeTherapistApprovalHistory::query()->create([
            'intake_id' => $intake->id,
            'therapist_id' => $therapist->id,
            'service' => $service,
            'status' => 'sent',
            'notes' => "Sent to {$therapist->full_name}",
        ]);
    }

    /**
     * Sends one `IntakeAssignedToTherapistMail` per therapist, listing every
     * service they were just assigned in this batch (empty list = the whole
     * intake, for services_needed-less intakes).
     *
     * @param  array<int, array{service: string|null, therapist: User}>  $assignments
     */
    private function sendAssignmentEmails(Intake $intake, array $assignments): void
    {
        $byTherapist = collect($assignments)->groupBy(fn (array $assignment) => $assignment['therapist']->id);

        foreach ($byTherapist as $grouped) {
            $therapist = $grouped->first()['therapist'];
            $services = $grouped->pluck('service')->filter()->values()->all();

            Mail::to($therapist->email)->send(new IntakeAssignedToTherapistMail($therapist->first_name, $intake, $services));
        }
    }

    /**
     * Mirrors the reference's layered double-promotion checks, including its
     * self-healing of a stale `linked_client_id`.
     */
    private function guardAgainstDoublePromotion(Intake $intake): void
    {
        $intake->refresh();

        $promotedClientId = $intake->promotedClient()->value('id');

        if ($promotedClientId) {
            $intake->forceFill([
                'linked_client_id' => $promotedClientId,
                'approved_as_client' => true,
            ])->save();

            throw ValidationException::withMessages([
                'therapist_id' => 'This intake has already been approved and a client already exists for it.',
            ]);
        }

        if ($intake->approved_as_client) {
            throw ValidationException::withMessages([
                'therapist_id' => 'This intake has already been approved.',
            ]);
        }

        if ($intake->linked_client_id) {
            $intake->forceFill(['linked_client_id' => null, 'approved_as_client' => false])->save();
        }
    }

    private function resolveTherapist(int $therapistId): User
    {
        $therapist = User::query()->where('id', $therapistId)->where('role', 'therapist')->first();

        if (! $therapist) {
            throw ValidationException::withMessages([
                'therapist_id' => 'Invalid therapist ID or role.',
            ]);
        }

        return $therapist;
    }

    /**
     * Records that `$therapistId` provides `$serviceName` for `$client`, so
     * it shows up in the client's service list. Reuses an existing
     * `ClientService` row for this exact (client, service, therapist) combo
     * if the approval flow ever runs twice for the same service.
     */
    private function recordClientService(Client $client, string $serviceName, int $therapistId): void
    {
        $service = $this->resolveServiceOffering($serviceName);

        $clientService = ClientService::query()->firstOrCreate([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'therapist_id' => $therapistId,
        ]);

        if ($clientService->wasRecentlyCreated) {
            $client->refreshServiceAvailedCache();
        }
    }

    /**
     * Intake `services_needed` are free-text labels from the intake
     * taxonomy, not foreign-keyed to `service_offerings` — find an existing
     * offering with a matching name, or provision one so the label isn't
     * silently lost.
     */
    private function resolveServiceOffering(string $name): ServiceOffering
    {
        $existing = ServiceOffering::query()->where('name', $name)->first();

        if ($existing) {
            return $existing;
        }

        return ServiceOffering::query()->create([
            'name' => $name,
            'code' => Str::slug($name).'-'.Str::random(6),
        ]);
    }

    /**
     * Resolves which of an intake's (now potentially several) per-service
     * reviews `therapistApprove`/`therapistReject` is deciding. An explicit
     * `service` in the request body disambiguates; when omitted, falls back
     * to the single review in one of the given `$statuses` (the common case
     * for intakes with just one service, keeping the old no-body-param call
     * shape working).
     *
     * @param  array<int, string>  $statuses
     */
    private function resolveReviewForDecision(Request $request, Intake $intake, array $statuses): IntakeTherapistApproval
    {
        $reviews = $intake->therapistReviews;

        if ($request->has('service')) {
            $service = $request->input('service');
            $review = $reviews->first(fn (IntakeTherapistApproval $r): bool => $r->service === $service);
        } else {
            $candidates = $reviews->filter(fn (IntakeTherapistApproval $r): bool => in_array($r->status, $statuses, true));
            $review = $candidates->count() === 1 ? $candidates->first() : null;
        }

        if (! $review || ! in_array($review->status, $statuses, true)) {
            throw ValidationException::withMessages([
                'service' => 'No pending therapist approval for the specified service.',
            ]);
        }

        return $review;
    }

    /**
     * Therapist picker source, including each therapist's specializations so
     * the "Assign to Therapist" UI can filter the dropdown per service.
     *
     * @return Collection<int, array{id: int, first_name: string, last_name: string, email: string, specializations: array<int, string>}>
     */
    private function therapists(): Collection
    {
        return User::query()
            ->where('role', 'therapist')
            ->with('teamMember:id,user_id,specializations')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (User $therapist): array => [
                'id' => $therapist->id,
                'first_name' => $therapist->first_name,
                'last_name' => $therapist->last_name,
                'email' => $therapist->email,
                // @phpstan-ignore nullsafe.neverNull (a therapist User isn't guaranteed to have a TeamMember row; Larastan doesn't model that)
                'specializations' => $therapist->teamMember?->specializations ?? [],
            ])
            ->values();
    }

    /**
     * Resolves the badge the reference's client-side `getStatusBadge` renders
     * (intake status overlaid with any outstanding therapist review).
     *
     * @param  Collection<int, IntakeTherapistApproval>  $reviews
     */
    private function withStatusBadge(Intake $intake, Collection $reviews): Intake
    {
        $badge = $this->resolveStatusBadge($intake, $reviews);

        $intake->setAttribute('status_label', $badge['label']);
        $intake->setAttribute('status_short_label', $badge['short_label']);
        $intake->setAttribute('status_variant', $badge['variant']);

        return $intake;
    }

    /**
     * @param  Collection<int, IntakeTherapistApproval>  $reviews
     * @return array{label: string, short_label: string, variant: string}
     */
    private function resolveStatusBadge(Intake $intake, Collection $reviews): array
    {
        $hasRejection = $reviews->contains(
            fn (IntakeTherapistApproval $review): bool => $review->status === 'rejected',
        );

        if ($intake->approved_as_client && $hasRejection) {
            return [
                'label' => 'Service Declined — Needs Reassignment',
                'short_label' => 'Declined',
                'variant' => 'therapist_rejected',
            ];
        }

        if (! $intake->approved_as_client && $reviews->isNotEmpty()) {
            if ($hasRejection) {
                return [
                    'label' => 'Rejected by Therapist',
                    'short_label' => 'Rejected',
                    'variant' => 'therapist_rejected',
                ];
            }

            if ($reviews->contains(fn (IntakeTherapistApproval $review): bool => in_array($review->status, ['pending', 'reassign'], true))) {
                return [
                    'label' => 'Awaiting Approval from Therapist',
                    'short_label' => 'Awaiting App',
                    'variant' => 'therapist_pending',
                ];
            }
        }

        $labels = [
            'approved' => 'Approved',
            'under_review' => 'Under Review',
            'waitlist' => 'Waitlist',
            'denied' => 'Denied',
            'pending' => 'Pending',
        ];

        $status = $intake->status ?: 'pending';
        $label = $labels[$status] ?? $labels['pending'];

        return [
            'label' => $label,
            'short_label' => $label,
            'variant' => array_key_exists($status, $labels) ? $status : 'pending',
        ];
    }

    /**
     * Flattens the validated form payload into intake column values,
     * merging the FSCD/insurance sub-objects into funding_source_info.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function intakeAttributes(array $validated): array
    {
        $availability = IntakeSubmissionService::resolveAvailability($validated['availability_slots'] ?? []);

        $referralSource = ($validated['referral_source'] ?? null) === 'Other' && ! empty($validated['referral_source_other'])
            ? $validated['referral_source_other']
            : $validated['referral_source'];

        return [
            'child_first_name' => $validated['child_first_name'],
            'child_middle_name' => $validated['child_middle_name'] ?? null,
            'child_last_name' => $validated['child_last_name'],
            'gender' => $validated['gender'] ?? null,
            'street_address' => $validated['street_address'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'],
            'state_province' => $validated['state_province'],
            'postal_code' => $validated['postal_code'],
            'grade_level' => $validated['grade_level'] ?? null,
            'school_name' => $validated['school_name'] ?? null,
            'services_needed' => $validated['services_needed'] ?? [],
            'currently_receiving_services' => $validated['currently_receiving_services'] ?? false,
            'receiving_services_desc' => $validated['receiving_services_desc'] ?? null,
            'diagnosis' => IntakeSubmissionService::resolveDiagnosis(
                $validated['diagnosis'] ?? [],
                $validated['diagnosis_other'] ?? null,
            ),
            'has_medical_conditions' => $validated['has_medical_conditions'] ?? false,
            'languages_spoken_at_home' => $validated['languages_spoken_at_home'] ?? null,
            'require_interpreter' => $validated['require_interpreter'] ?? false,
            'interpreter_needed' => $validated['interpreter_needed'] ?? null,
            'medical_conditions' => $validated['medical_conditions'] ?? null,
            'theraphy_goals' => $validated['theraphy_goals'] ?? null,
            'admin_addition_informations' => $validated['admin_addition_informations'] ?? null,
            'funding_source' => $validated['funding_source'],
            'available_days' => $availability['days'],
            'preferred_times' => $availability['times'],
            'availability_slots' => $availability['slots'],
            'primary_parent_name' => $validated['primary_parent_name'],
            'primary_parent_phone' => $validated['primary_parent_phone'],
            'primary_parent_email' => $validated['primary_parent_email'] ?? null,
            'primary_relationship_to_child' => $validated['primary_relationship_to_child'],
            'primary_contact_method' => $validated['primary_contact_method'],
            'secondary_parent_name' => $validated['secondary_parent_name'] ?? null,
            'secondary_parent_phone' => $validated['secondary_parent_phone'] ?? null,
            'secondary_parent_email' => $validated['secondary_parent_email'] ?? null,
            'secondary_relationship_to_child' => $validated['secondary_relationship_to_child'] ?? null,
            'secondary_contact_method' => $validated['secondary_contact_method'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
            'completed' => $validated['completed'] ?? false,
            'referral_source' => $referralSource,
            'emergency_contact_name' => $validated['emergency_contact_name'],
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'],
            'emergency_contact_phone' => $validated['emergency_contact_phone'],
            'funding_source_info' => $this->buildFundingSourceInfo($validated),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildFundingSourceInfo(array $validated): array
    {
        $fundingSource = $validated['funding_source'];
        $isFscd = in_array($fundingSource, self::FSCD_SOURCES, true);

        $info = match (true) {
            $fundingSource === 'Insurance' => $validated['insurance_info'] ?? [],
            $isFscd => $validated['fscd_info'] ?? [],
            default => [],
        };

        $info['consents'] = $isFscd
            ? [
                ['title' => 'FSCD Worker Communication', 'datetime' => now()->toIso8601String()],
                ['title' => 'Reports Sharing', 'datetime' => now()->toIso8601String()],
                ['title' => 'Non-Approved Costs Acknowledgment', 'datetime' => now()->toIso8601String()],
            ]
            : [];

        return $info;
    }

    /**
     * @return array{id: string, title: string, date: string, time: string}
     */
    private function timelineEntry(string $title): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'date' => now()->toDateString(),
            'time' => now()->format('g:i:s A'),
        ];
    }

    /**
     * @return array{id: string, note: string, date: string, time: string, user: string}
     */
    private function noteEntry(string $note, ?User $author): array
    {
        return [
            'id' => (string) Str::uuid(),
            'note' => $note,
            'date' => now()->toDateString(),
            'time' => now()->format('g:i:s A'),
            'user' => $author ? $author->full_name : 'Unknown User',
        ];
    }
}
