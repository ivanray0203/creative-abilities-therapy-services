<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LoginCredentialsMail;
use App\Mail\OfferLetterMail;
use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationHiringService;
use App\Services\AuditLogger;
use App\Services\PdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin hiring pipeline (reference: cats-frontend/src/pages/admin/ApplicationsPage.tsx
 * and ApplicationDetailPage.tsx; business logic: cats-backend/cats/serializers.py
 * ApplicationSerializer). Application creation stays public-only (Phase 5) —
 * this controller never creates applications, only reviews/decides them.
 */
class ApplicationController extends Controller
{
    /**
     * Status transitions the reference's action buttons offer per current
     * status — not strictly linear (reviewing/interview_scheduled can both
     * jump straight to hired/declined).
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['reviewing'],
        'reviewing' => ['interview_scheduled', 'hired', 'declined'],
        'interview_scheduled' => ['interview_scheduled', 'hired', 'declined'],
        'hired' => [],
        'declined' => [],
    ];

    public function index(Request $request): Response
    {
        $stats = [
            'total' => Application::query()->count(),
            'pending' => Application::query()->where('application_status', 'pending')->count(),
            'reviewing' => Application::query()->where('application_status', 'reviewing')->count(),
            'interview_scheduled' => Application::query()->where('application_status', 'interview_scheduled')->count(),
            'hired' => Application::query()->where('application_status', 'hired')->count(),
            'declined' => Application::query()->where('application_status', 'declined')->count(),
        ];

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $applications = Application::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('position_applied', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', function (Builder $query) use ($status): void {
                $query->where('application_status', $status);
            })
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/applications/index', [
            'applications' => $applications,
            'stats' => $stats,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function show(Application $application): Response
    {
        return Inertia::render('admin/applications/show', [
            'application' => $application,
            'statusTransitions' => self::STATUS_TRANSITIONS[$application->application_status] ?? [],
        ]);
    }

    /**
     * The reference's UpdateApplicationStatus modal: hourly rate on hire,
     * interview date/time/platform on scheduling, an optional note
     * otherwise.
     */
    public function updateStatus(Request $request, Application $application, ApplicationHiringService $hiringService, PdfService $pdfService): RedirectResponse
    {
        $allowed = self::STATUS_TRANSITIONS[$application->application_status] ?? [];

        $validated = $request->validate([
            'application_status' => ['required', Rule::in($allowed)],
            'note' => ['nullable', 'string'],
            'hourly_rate' => [Rule::requiredIf($request->input('application_status') === 'hired'), 'nullable', 'numeric', 'min:0'],
            'interview_date' => [Rule::requiredIf($request->input('application_status') === 'interview_scheduled'), 'nullable', 'date'],
            'interview_time' => ['nullable', 'string'],
            'interview_platform' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = [
            'application_status' => $validated['application_status'],
        ];

        if (filled($validated['note'] ?? null)) {
            $attributes['internal_notes'] = [
                ...($application->internal_notes ?? []),
                $this->noteEntry((string) $validated['note'], $request->user()),
            ];
        }

        if ($validated['application_status'] === 'interview_scheduled') {
            $attributes['interview_date'] = $validated['interview_date'];
            $attributes['interview_time'] = $validated['interview_time'] ?? null;
            $attributes['interview_platform'] = $validated['interview_platform'] ?? null;
        }

        if ($validated['application_status'] === 'declined') {
            $attributes['declined'] = true;
        }

        if ($validated['application_status'] === 'hired') {
            $attributes['hired'] = true;
            $attributes['hire_date'] = now()->toDateString();
            $attributes['hourly_rate'] = $validated['hourly_rate'];

            $rawPassword = DB::transaction(function () use ($application, $attributes, $hiringService, $validated): ?string {
                $hired = $hiringService->hire($application, (float) $validated['hourly_rate']);
                $application->update($attributes);

                return $hired['rawPassword'];
            });

            Mail::to($application->email)->send(new OfferLetterMail(
                $application->first_name,
                $application->last_name,
                $application->position_applied,
                $pdfService->offerLetter($application),
            ));

            if ($rawPassword !== null) {
                Mail::to($application->email)->send(new LoginCredentialsMail(
                    $application->first_name,
                    $application->email,
                    $rawPassword,
                ));
            }

            AuditLogger::log('Hired applicant', 'Applications', "Hired application #{$application->id} ({$application->email})");

            return back()->with('success', 'Application status updated successfully.');
        }

        $application->update($attributes);

        AuditLogger::log('Updated application status', 'Applications', "Set application #{$application->id} status to {$validated['application_status']}");

        return back()->with('success', 'Application status updated successfully.');
    }

    public function updateRating(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $application->update(['candidate_rating' => $validated['candidate_rating']]);

        AuditLogger::log('Updated application rating', 'Applications', "Rated application #{$application->id}: {$validated['candidate_rating']}/5");

        return back()->with('success', 'Rating updated successfully.');
    }

    public function addNote(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $application->update([
            'internal_notes' => [
                ...($application->internal_notes ?? []),
                $this->noteEntry($validated['note'], $request->user()),
            ],
        ]);

        AuditLogger::log('Added application note', 'Applications', "Added a note to application #{$application->id}");

        return back()->with('success', 'Note added successfully.');
    }

    public function deleteNote(Application $application, string $note): RedirectResponse
    {
        $remaining = array_values(array_filter(
            $application->internal_notes ?? [],
            fn (array $entry): bool => ($entry['id'] ?? null) !== $note,
        ));

        $application->update(['internal_notes' => $remaining]);

        AuditLogger::log('Deleted application note', 'Applications', "Deleted a note from application #{$application->id}", 'warning');

        return back()->with('success', 'Note deleted successfully.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $application->delete();

        AuditLogger::log('Deleted application', 'Applications', "Deleted application #{$application->id}", 'warning');

        return to_route('admin.applications.index')->with('success', 'Application deleted successfully.');
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
