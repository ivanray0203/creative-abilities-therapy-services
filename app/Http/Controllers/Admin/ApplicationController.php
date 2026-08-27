<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LoginCredentialsMail;
use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationHiringService;
use App\Services\ApplicationNotifier;
use App\Services\AuditLogger;
use App\Services\OfferLetterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        'reviewing' => ['interview_scheduled', 'offer_sent', 'declined'],
        'interview_scheduled' => ['interview_scheduled', 'offer_sent', 'declined'],
        'offer_sent' => ['offer_sent', 'hired', 'declined'],
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
            'offer_sent' => Application::query()->where('application_status', 'offer_sent')->count(),
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
     * Every status change goes through here. The modal asks for what the
     * target status needs: an hourly rate when the offer goes out, interview
     * date/time/platform when one is booked, an optional note otherwise.
     *
     * Hire is the odd one out — it asks for nothing, because everything it
     * needs was agreed on the offer the candidate has already signed.
     */
    public function updateStatus(Request $request, Application $application, ApplicationHiringService $hiringService, OfferLetterService $offers): RedirectResponse
    {
        $allowed = self::STATUS_TRANSITIONS[$application->application_status] ?? [];
        $previousStatus = $application->application_status;
        $previousInterviewSchedule = $application->interview_schedule;

        $validated = $request->validate([
            'application_status' => ['required', Rule::in($allowed)],
            'note' => ['nullable', 'string'],
            'hourly_rate' => [Rule::requiredIf($request->input('application_status') === 'offer_sent'), 'nullable', 'numeric', 'min:0'],
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

        if ($validated['application_status'] === 'offer_sent') {
            $attributes['hourly_rate'] = $validated['hourly_rate'];
            $application->update($attributes);

            $offers->send($application);

            AuditLogger::log('Offer sent', 'Applications', "Sent the offer letter for application #{$application->id} ({$application->email})");

            return back()->with('success', 'Offer letter sent successfully.');
        }

        if ($validated['application_status'] === 'hired') {
            if (! $application->hasSignedOffer()) {
                throw ValidationException::withMessages([
                    'application_status' => 'This candidate has not signed their offer letter yet.',
                ]);
            }

            // The rate was agreed when the offer went out and the candidate
            // signed against it, so it is read off the application rather
            // than asked for again here.
            $hourlyRate = (float) $application->hourly_rate;

            $attributes['hired'] = true;
            $attributes['hire_date'] = now()->toDateString();

            $rawPassword = DB::transaction(function () use ($application, $attributes, $hiringService, $hourlyRate): ?string {
                $hired = $hiringService->hire($application, $hourlyRate);
                $application->update($attributes);

                return $hired['rawPassword'];
            });

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

        $this->notifyCandidate($application, $previousStatus, $previousInterviewSchedule);

        AuditLogger::log('Updated application status', 'Applications', "Set application #{$application->id} status to {$validated['application_status']}");

        return back()->with('success', 'Application status updated successfully.');
    }

    /**
     * Tells the candidate what just happened to their application. The hire
     * branch returns before reaching here — it sends its own offer letter and
     * login credentials instead.
     */
    private function notifyCandidate(Application $application, string $previousStatus, ?string $previousInterviewSchedule): void
    {
        match ($application->application_status) {
            'reviewing' => ApplicationNotifier::underReview($application),
            'interview_scheduled' => $previousStatus === 'interview_scheduled'
                ? ApplicationNotifier::interviewRescheduled($application, $previousInterviewSchedule)
                : ApplicationNotifier::interviewScheduled($application),
            'declined' => ApplicationNotifier::declined($application),
            default => null,
        };
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
