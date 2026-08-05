<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\ScheduleSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Therapist dashboard (reference: cats-frontend/src/pages/therapist/Dashboard.tsx),
 * assembling server-side what the reference composes from three separate
 * client-side fetches.
 */
class TherapistDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $therapistId = $request->user()->id;

        $todaysSessions = ScheduleSession::query()
            ->where('therapist_id', $therapistId)
            ->whereDate('scheduled_start', now()->toDateString())
            ->with(['client.originalIntake', 'service'])
            ->orderBy('scheduled_start')
            ->get();

        $pendingReviews = Intake::query()
            ->whereHas('therapistReviews', function (Builder $query) use ($therapistId): void {
                $query->where('therapist_id', $therapistId)
                    ->whereIn('status', ['pending', 'reassign']);
            })
            ->with([
                'therapistReviews' => fn ($query) => $query->where('therapist_id', $therapistId),
                'therapistReviewHistory' => fn ($query) => $query->latest('created_at')->limit(1),
            ])
            ->get()
            ->filter(fn (Intake $intake): bool => $intake->therapistReviews->contains(
                fn (IntakeTherapistApproval $review): bool => in_array($review->status, ['pending', 'reassign'], true)
                    && $review->latestHistoryStatus() === 'sent',
            ))
            ->values();

        $completedThisWeek = ScheduleSession::query()
            ->where('therapist_id', $therapistId)
            ->where('status', 'completed')
            ->whereBetween('scheduled_start', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $hoursThisMonth = ScheduleSession::query()
            ->where('therapist_id', $therapistId)
            ->where('status', 'completed')
            ->whereBetween('scheduled_start', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['scheduled_start', 'scheduled_end'])
            ->sum(fn (ScheduleSession $session): int => $session->scheduled_start->diffInMinutes($session->scheduled_end)) / 60;

        return Inertia::render('therapist/dashboard', [
            'stats' => [
                'todays_sessions' => $todaysSessions->count(),
                'completed_this_week' => $completedThisWeek,
                'active_clients' => Client::query()->where('primary_therapist_id', $therapistId)->count(),
                'hours_this_month' => round($hoursThisMonth, 1),
            ],
            'pendingReviews' => $pendingReviews,
            'todaysSessions' => $todaysSessions,
        ]);
    }

    /**
     * Reference: cats-frontend/src/pages/therapist/CalendarPage.tsx fetches every
     * session for the therapist via `fetchSessionByTherapist` and filters by the
     * selected date client-side — mirrored here rather than paginating server-side.
     */
    public function calendar(Request $request): Response
    {
        $sessions = ScheduleSession::query()
            ->where('therapist_id', $request->user()->id)
            ->with(['client.originalIntake', 'service'])
            ->orderBy('scheduled_start')
            ->get();

        return Inertia::render('therapist/calendar', [
            'sessions' => $sessions,
        ]);
    }
}
