<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\ClientService;
use App\Models\IntakeTherapistApproval;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\PdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin client pipeline (reference: cats-frontend/src/pages/admin/ClientsPage.tsx
 * and ClientDetailPage.tsx; business logic: cats-backend/cats/views.py ClientViewSet).
 */
class ClientController extends Controller
{
    /**
     * FSCD funding-source variants, grouped into a single "FSCD" bucket in
     * the funding overview and the funding filter — mirrors IntakeController.
     *
     * @var array<int, string>
     */
    private const FSCD_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

    public function index(Request $request): Response
    {
        $baseQuery = Client::query();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'paused' => (clone $baseQuery)->where('status', 'paused')->count(),
            'upcoming' => (clone $baseQuery)
                ->where('status', 'active')
                ->whereDate('contract_start_date', '>', now())
                ->count(),
            'fscd' => (clone $baseQuery)->whereHas('originalIntake', function (Builder $query): void {
                $query->whereIn('funding_source', self::FSCD_SOURCES);
            })->count(),
            'insurance' => (clone $baseQuery)->whereHas('originalIntake', function (Builder $query): void {
                $query->where('funding_source', 'Insurance');
            })->count(),
            'private' => (clone $baseQuery)->whereHas('originalIntake', function (Builder $query): void {
                $query->where('funding_source', 'private');
            })->count(),
        ];

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $funding = (string) $request->query('funding', 'all');

        $clients = (clone $baseQuery)
            ->with(['originalIntake', 'assignedTherapist'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('originalIntake', function (Builder $inner) use ($search): void {
                    $inner->where('child_first_name', 'like', "%{$search}%")
                        ->orWhere('child_last_name', 'like', "%{$search}%")
                        ->orWhere('primary_parent_name', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($funding === 'fscd', function (Builder $query): void {
                $query->whereHas('originalIntake', function (Builder $inner): void {
                    $inner->whereIn('funding_source', self::FSCD_SOURCES);
                });
            })
            ->when(in_array($funding, ['Insurance', 'private'], true), function (Builder $query) use ($funding): void {
                $query->whereHas('originalIntake', function (Builder $inner) use ($funding): void {
                    $inner->where('funding_source', $funding);
                });
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/clients/index', [
            'clients' => $clients,
            'stats' => $stats,
            'filters' => ['search' => $search, 'status' => $status, 'funding' => $funding],
        ]);
    }

    public function show(Client $client): Response
    {
        $client->load([
            'originalIntake.therapistReviews.therapist',
            'assignedTherapist',
            'primaryTherapist',
            'careTeam',
            'clientServices.service',
            'clientServices.therapist',
            'documents',
            'invoices',
        ]);

        return Inertia::render('admin/clients/show', [
            'client' => $client,
            'declinedServices' => $this->declinedServices($client),
            'therapists' => $this->therapists(),
            'services' => ServiceOffering::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Services a therapist refused, so they can be picked up again.
     *
     * A refusal leaves no ClientService behind, and the intake drops off the
     * admin list once the child is promoted — without this the service would
     * sit in the Overview's "requested" list, indistinguishable from one that
     * was never sent to anyone.
     *
     * @return array<int, array{service: string, therapist_id: int|null, therapist: string|null, notes: string|null, decided_at: string|null}>
     */
    private function declinedServices(Client $client): array
    {
        $intake = $client->originalIntake;

        if ($intake === null) {
            return [];
        }

        return $intake->therapistReviews
            ->where('status', 'rejected')
            ->filter(fn (IntakeTherapistApproval $review): bool => $review->service !== null)
            ->map(fn (IntakeTherapistApproval $review): array => [
                'service' => (string) $review->service,
                // The id, not just the name: the reassign picker drops the
                // therapist who declined, and matching on a display name
                // breaks the moment two people share one.
                'therapist_id' => $review->therapist_id,
                'therapist' => $review->therapist?->full_name,
                'notes' => $review->notes,
                'decided_at' => $review->decided_at?->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * The client page as a PDF — Overview, Sessions, Funding, Notes and
     * Therapist in one document, for case files and referrals.
     */
    public function exportPdf(Client $client, PdfService $pdfService): StreamedResponse
    {
        $pdf = $pdfService->clientProfile($client);
        $name = Str::slug($client->displayName());

        AuditLogger::log('Exported client profile', 'Clients', "Exported client #{$client->id} as PDF");

        return response()->streamDownload(
            fn () => print ($pdf),
            "{$name}-profile.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function edit(Client $client): Response
    {
        $client->load('originalIntake');

        return Inertia::render('admin/clients/edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        AuditLogger::log('Updated client', 'Clients', "Updated client #{$client->id}");

        return to_route('admin.client.show', $client)->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        AuditLogger::log('Deleted client', 'Clients', "Deleted client #{$client->id}", 'warning');

        return to_route('admin.client.index')->with('success', 'Client deleted successfully.');
    }

    /**
     * First-time therapist assignment — no timeline entry (that's
     * `reassignTherapist`'s job).
     */
    public function assignTherapist(Request $request, Client $client): RedirectResponse
    {
        $therapist = $this->resolveTherapist($this->validatedTherapistId($request));

        $client->assignTherapist($therapist);

        AuditLogger::log('Assigned therapist', 'Clients', "Assigned {$therapist->full_name} to client #{$client->id}");

        return back()->with('success', "{$therapist->full_name} assigned as primary therapist.");
    }

    public function reassignTherapist(Request $request, Client $client): RedirectResponse
    {
        $therapist = $this->resolveTherapist($this->validatedTherapistId($request));

        $client->reassignPrimaryTherapist($therapist, $request->user());

        AuditLogger::log('Reassigned therapist', 'Clients', "Reassigned client #{$client->id} to {$therapist->full_name}");

        return back()->with('success', "Primary therapist reassigned to {$therapist->full_name}.");
    }

    /**
     * Add/remove a secondary care-team member. The primary therapist can't
     * be removed here — use reassignTherapist instead.
     */
    public function updateCareTeam(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'therapist_id' => ['required', 'integer', 'exists:users,id'],
            'action' => ['required', Rule::in(['add', 'remove'])],
        ]);

        $therapist = $this->resolveTherapist((int) $validated['therapist_id']);

        if ($validated['action'] === 'remove') {
            if ($client->primary_therapist_id === $therapist->id) {
                throw ValidationException::withMessages([
                    'therapist_id' => 'Cannot remove the primary therapist from the care team.',
                ]);
            }

            $client->careTeam()->detach($therapist->id);

            AuditLogger::log('Removed care team member', 'Clients', "Removed {$therapist->full_name} from client #{$client->id}'s care team");

            return back()->with('success', "{$therapist->full_name} removed from the care team.");
        }

        $client->careTeam()->syncWithoutDetaching([$therapist->id]);

        AuditLogger::log('Added care team member', 'Clients', "Added {$therapist->full_name} to client #{$client->id}'s care team");

        return back()->with('success', "{$therapist->full_name} added to the care team.");
    }

    public function storeService(Request $request, Client $client): RedirectResponse
    {
        $validated = $this->validateClientService($request);

        $client->clientServices()->create($validated);
        $client->refreshServiceAvailedCache();

        AuditLogger::log('Added client service', 'Clients', "Added a service to client #{$client->id}");

        return back()->with('success', 'Service added successfully.');
    }

    public function updateService(Request $request, Client $client, ClientService $clientService): RedirectResponse
    {
        $this->guardServiceBelongsToClient($client, $clientService);

        $validated = $this->validateClientService($request);

        $clientService->update($validated);
        $client->refreshServiceAvailedCache();

        AuditLogger::log('Updated client service', 'Clients', "Updated service #{$clientService->id} for client #{$client->id}");

        return back()->with('success', 'Service updated successfully.');
    }

    public function destroyService(Client $client, ClientService $clientService): RedirectResponse
    {
        $this->guardServiceBelongsToClient($client, $clientService);

        $clientService->delete();
        $client->refreshServiceAvailedCache();

        AuditLogger::log('Removed client service', 'Clients', "Removed service #{$clientService->id} from client #{$client->id}", 'warning');

        return back()->with('success', 'Service removed successfully.');
    }

    public function uploadDocument(Request $request, Client $client, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $uploaded = $drive->upload($request->file('file'), 'client', $this->clientFolderName($client));

        $client->documents()->create([
            'title' => $validated['name'] ?? $request->file('file')->getClientOriginalName(),
            'doc_type' => $validated['type'],
            ...$uploaded,
            'upload_origin' => 'admin',
            'uploaded_by_id' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        AuditLogger::log('Uploaded client document', 'Clients', "Uploaded a document to client #{$client->id}");

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(ClientDocument $document, DriveStorage $drive): RedirectResponse
    {
        $drive->delete($document->drive_file_id);

        $document->delete();

        AuditLogger::log('Deleted client document', 'Clients', "Deleted document #{$document->id}", 'warning');

        return back()->with('success', 'Document deleted successfully.');
    }

    private function clientFolderName(Client $client): string
    {
        $intake = $client->originalIntake;
        $name = trim("{$intake?->child_first_name} {$intake?->child_last_name}");

        return trim("{$intake?->id}_{$name}", '_');
    }

    public function addNote(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $client->update([
            'clinical_notes' => [
                ...($client->clinical_notes ?? []),
                $this->noteEntry($validated['note'], $request->user()),
            ],
        ]);

        AuditLogger::log('Added client note', 'Clients', "Added a note to client #{$client->id}");

        return back()->with('success', 'Note added successfully.');
    }

    public function deleteNote(Client $client, string $note): RedirectResponse
    {
        $remaining = array_values(array_filter(
            $client->clinical_notes ?? [],
            fn (array $entry): bool => ($entry['id'] ?? null) !== $note,
        ));

        $client->update(['clinical_notes' => $remaining]);

        AuditLogger::log('Deleted client note', 'Clients', "Deleted a note from client #{$client->id}", 'warning');

        return back()->with('success', 'Note deleted successfully.');
    }

    private function validatedTherapistId(Request $request): int
    {
        $validated = $request->validate([
            'therapist_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return (int) $validated['therapist_id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClientService(Request $request): array
    {
        return $request->validate([
            'service_id' => ['required', 'integer', 'exists:service_offerings,id'],
            'therapist_id' => ['nullable', 'integer', 'exists:users,id'],
            'frequency' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'no_sessions' => ['nullable', 'integer', 'min:0'],
            'goals' => ['nullable', 'string'],
        ]);
    }

    private function guardServiceBelongsToClient(Client $client, ClientService $clientService): void
    {
        if ($clientService->client_id !== $client->id) {
            abort(404);
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
