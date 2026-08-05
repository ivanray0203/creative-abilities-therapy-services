<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin's own profile + notification preferences (reference:
 * cats-frontend's administratorTabs/SettingsTab.tsx). Email isn't editable
 * here, matching the reference's own disabled email field. Password change
 * reuses the existing Breeze `password.update` route.
 */
class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'new_intake' => ['boolean'],
            'invoice_payments' => ['boolean'],
            'session_reminders' => ['boolean'],
            'new_applications' => ['boolean'],
        ]);

        $request->user()->update($validated);

        AuditLogger::log('Updated profile', 'Users', "{$request->user()->email} updated their profile");

        return back()->with('success', 'Profile updated successfully.');
    }
}
