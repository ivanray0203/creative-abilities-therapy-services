<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ClientDocument;
use App\Models\User;
use App\Services\ApplicationHiringService;
use App\Services\ApplicationNotifier;
use App\Services\AuditLogger;
use App\Services\Interviews\MeetingLinkGenerator;
use App\Services\OfferLetterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        'offer_sent' => ['offer_sent', 'onboarding', 'declined'],
        'onboarding' => ['hired', 'declined'],
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
            'onboarding' => Application::query()->where('application_status', 'onboarding')->count(),
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

    public function show(Application $application, ApplicationHiringService $hiringService): Response
    {
        return Inertia::render('admin/applications/show', [
            'application' => $application,
            'statusTransitions' => self::STATUS_TRANSITIONS[$application->application_status] ?? [],
            'onboarding' => $this->onboardingFor($application, $hiringService),
        ]);
    }

    /**
     * The document checklist an admin reviews before hiring: what the
     * position requires, what the candidate has uploaded through their
     * profile, and what is still outstanding. Null until onboarding starts.
     *
     * @return array{required_documents: array<int, string>, missing_documents: array<int, string>, documents: array<int, array<string, mixed>>}|null
     */
    private function onboardingFor(Application $application, ApplicationHiringService $hiringService): ?array
    {
        if (! in_array($application->application_status, ['onboarding', 'hired'], true)) {
            return null;
        }

        $teamMember = $hiringService->teamMemberFor($application);

        $documents = [];

        if ($teamMember !== null) {
            $documents = ClientDocument::query()
                ->where('user_id', $teamMember->user_id)
                ->latest('uploaded_at')
                ->get()
                ->map(fn (ClientDocument $document): array => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'doc_type' => $document->doc_type,
                    'drive_web_view' => $document->drive_web_view,
                    'uploaded_at' => $document->uploaded_at,
                ])
                ->all();
        }

        return [
            'required_documents' => $application->requiredDocuments(),
            'missing_documents' => $hiringService->missingDocuments($application),
            'documents' => $documents,
        ];
    }

    /**
     * Every status change goes through here. The modal asks for what the
     * target status needs: an hourly rate when the offer goes out, interview
     * date/time/platform when one is booked, an optional note otherwise.
     *
     * Onboarding and hire ask for nothing, because everything they need was
     * agreed on the offer the candidate has already signed. Onboarding
     * creates the account and emails the credentials with the list of
     * documents to upload; hire is allowed once every one of them is in.
     */
    public function updateStatus(Request $request, Application $application, ApplicationHiringService $hiringService, OfferLetterService $offers, MeetingLinkGenerator $meetings): RedirectResponse
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

            // A declined candidate's interview comes off the calendar too.
            $meetings->cancel($application);
            $attributes['interview_meeting_link'] = null;
            $attributes['interview_calendar_event_id'] = null;
        }

        if ($validated['application_status'] === 'offer_sent') {
            $attributes['hourly_rate'] = $validated['hourly_rate'];
            $application->update($attributes);

            $offers->send($application);

            AuditLogger::log('Offer sent', 'Applications', "Sent the offer letter for application #{$application->id} ({$application->email})");

            return back()->with('success', 'Offer letter sent successfully.');
        }

        if ($validated['application_status'] === 'onboarding') {
            if (! $application->hasSignedOffer()) {
                throw ValidationException::withMessages([
                    'application_status' => 'This candidate has not signed their offer letter yet.',
                ]);
            }

            $attributes['onboarding_started_at'] = now();

            $rawPassword = DB::transaction(function () use ($application, $attributes, $hiringService): ?string {
                $provisioned = $hiringService->startOnboarding($application, (float) $application->hourly_rate);
                $application->update($attributes);

                return $provisioned['rawPassword'];
            });

            ApplicationNotifier::onboardingStarted($application, $rawPassword);

            AuditLogger::log('Started onboarding', 'Applications', "Started onboarding for application #{$application->id} ({$application->email})");

            return back()->with('success', 'Onboarding started. The candidate has been emailed their login details and required documents.');
        }

        if ($validated['application_status'] === 'hired') {
            $missing = $hiringService->missingDocuments($application);

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'application_status' => 'The candidate still has to upload: '.implode(', ', $missing).'.',
                ]);
            }

            $attributes['hired'] = true;
            $attributes['hire_date'] = now()->toDateString();

            DB::transaction(function () use ($application, $attributes, $hiringService): void {
                $hiringService->hire($application);
                $application->update($attributes);
            });

            ApplicationNotifier::hired($application);

            AuditLogger::log('Hired applicant', 'Applications', "Hired application #{$application->id} ({$application->email})");

            return back()->with('success', 'Candidate hired. They have been notified by email.');
        }

        $application->update($attributes);

        if ($validated['application_status'] === 'interview_scheduled') {
            $this->bookVideoCall($application, $meetings);
        }

        $this->notifyCandidate($application, $previousStatus, $previousInterviewSchedule);

        AuditLogger::log('Updated application status', 'Applications', "Set application #{$application->id} status to {$validated['application_status']}");

        return back()->with('success', 'Application status updated successfully.');
    }

    /**
     * A video interview gets a Google Meet link, created (or moved, on a
     * reschedule) on the connected Google account's calendar. Switching a
     * booked video call to a phone or in-person interview removes the event
     * so the candidate is not left holding a live link. The link is written
     * before the candidate is emailed, so the invitation carries it.
     */
    private function bookVideoCall(Application $application, MeetingLinkGenerator $meetings): void
    {
        if ($application->interview_platform !== 'video') {
            $meetings->cancel($application);
            $application->update(['interview_meeting_link' => null, 'interview_calendar_event_id' => null]);

            return;
        }

        $booking = $meetings->schedule($application);

        $application->update([
            'interview_meeting_link' => $booking['meeting_link'] ?? $application->interview_meeting_link,
            'interview_calendar_event_id' => $booking['event_id'] ?? $application->interview_calendar_event_id,
        ]);
    }

    /**
     * Tells the candidate what just happened to their application. The offer,
     * onboarding and hire branches return before reaching here — each sends
     * its own email.
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
