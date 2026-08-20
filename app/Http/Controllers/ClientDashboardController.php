<?php

namespace App\Http\Controllers;

use App\Models\ScheduleSession;
use App\Services\ClientContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client calendar (reference: cats-frontend/src/pages/client/CalendarPage.tsx).
 * The client role's post-login landing page is this calendar, not a
 * dashboard — see EnsureRole::homeFor('client').
 *
 * Scoped to the child currently selected in the portal switcher (Phase 17).
 */
class ClientDashboardController extends Controller
{
    public function __construct(private ClientContext $clientContext) {}

    public function calendar(Request $request): Response
    {
        $clientId = $this->clientContext->currentId($request->user());

        $sessions = ScheduleSession::query()
            ->where('client_id', $clientId)
            ->with(['therapist', 'service', 'clientServices.service'])
            ->orderBy('scheduled_start')
            ->get();

        return Inertia::render('client/calendar', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Switch the portal to another of the parent's children. The id is
     * validated against their own children inside ClientContext, so a
     * tampered value falls through to a 404 rather than exposing another
     * family's records.
     */
    public function selectChild(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
        ]);

        abort_unless(
            $this->clientContext->select($request->user(), (int) $validated['client_id']),
            404,
        );

        return back();
    }
}
