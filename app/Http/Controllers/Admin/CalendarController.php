<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Clinic-wide calendar: every therapist's diary in one grid.
     *
     * The page navigates between months, weeks, and days entirely in the
     * browser, so the whole set is sent up front rather than a window around
     * today — paging server-side would leave the arrows silently landing on
     * empty months. Only the handful of fields the grid renders are sent, so
     * the payload stays small even though the row count is unbounded.
     */
    public function index(): Response
    {
        return Inertia::render('admin/calendar', [
            'sessions' => $this->sessions(),
            'therapists' => $this->therapists(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sessions(): array
    {
        return ScheduleSession::query()
            ->whereNotNull('scheduled_start')
            ->with(['client.originalIntake', 'service', 'therapist'])
            ->orderBy('scheduled_start')
            ->get()
            ->map(fn (ScheduleSession $session): array => [
                'id' => (string) $session->id,
                'date' => $session->scheduled_start?->format('Y-m-d') ?? '',
                'time' => $session->scheduled_start?->format('H:i') ?? '',
                'endTime' => $session->scheduled_end?->format('H:i') ?? '',
                'client' => $session->client?->displayName() ?? 'Unknown client',
                'type' => optional($session->service)->name ?? $session->service_name ?? 'Session',
                'location' => $session->location ?? '',
                'status' => $session->status ?? 'scheduled',
                'therapistId' => $session->therapist_id,
                'therapist' => trim("{$session->therapist?->first_name} {$session->therapist?->last_name}") ?: 'Unassigned',
            ])
            ->all();
    }

    /**
     * Only therapists who actually hold a session appear in the filter —
     * listing the whole roster would offer mostly empty calendars.
     *
     * @return array<int, array<string, mixed>>
     */
    private function therapists(): array
    {
        return User::query()
            ->where('role', 'therapist')
            ->whereHas('sessionsAsTherapist')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (User $therapist): array => [
                'id' => $therapist->id,
                'name' => trim("{$therapist->first_name} {$therapist->last_name}") ?: $therapist->email,
            ])
            ->all();
    }
}
