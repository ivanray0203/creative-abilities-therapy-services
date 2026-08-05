<?php

namespace App\Http\Controllers;

use App\Models\ScheduleSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client calendar (reference: cats-frontend/src/pages/client/CalendarPage.tsx).
 * The client role's post-login landing page is this calendar, not a
 * dashboard — see EnsureRole::homeFor('client').
 */
class ClientDashboardController extends Controller
{
    public function calendar(Request $request): Response
    {
        $clientId = $request->user()->clientProfile?->id;

        $sessions = ScheduleSession::query()
            ->where('client_id', $clientId)
            ->with(['therapist', 'service'])
            ->orderBy('scheduled_start')
            ->get();

        return Inertia::render('client/calendar', [
            'sessions' => $sessions,
        ]);
    }
}
