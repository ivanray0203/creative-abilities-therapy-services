<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\ClientContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client self-service profile (reference: cats-frontend/src/pages/client/ProfilePage.tsx).
 * The editable fields all live on the client's original Intake record, not
 * on the Client model itself — mirrors Admin\TeamMemberController::me()'s
 * pattern of resolving the acting user's own record rather than route
 * binding.
 *
 * A parent with several children edits whichever child the portal switcher
 * currently has selected (Phase 17).
 */
class ClientProfileController extends Controller
{
    public function __construct(private ClientContext $clientContext) {}

    public function show(Request $request): Response
    {
        $client = $this->clientContext->current($request->user());
        abort_unless($client !== null, 404);

        $client->load(['originalIntake', 'documents']);

        return Inertia::render('client/profile', [
            'client' => $client,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $client = $this->clientContext->current($request->user());
        abort_unless($client?->originalIntake !== null, 404);

        $validated = $request->validate([
            'child_first_name' => ['required', 'string', 'max:255'],
            'child_last_name' => ['required', 'string', 'max:255'],
            'primary_parent_phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $client->originalIntake->update($validated);

        AuditLogger::log('Updated profile', 'Clients', "{$request->user()->email} updated their client profile");

        return back()->with('success', 'Profile updated successfully.');
    }
}
