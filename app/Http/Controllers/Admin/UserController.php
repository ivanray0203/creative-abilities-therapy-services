<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StaffInviteMail;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Admin user-account management (reference: cats-backend accounts/views.py
 * UserViewSet's admin-only actions). `adminUpdate` backs both the "Edit"
 * and "Deactivate" actions on the Phase 12 Administrator panel's Admin
 * Users tab; `adminStore` backs its "Add Admin User" action.
 */
class UserController extends Controller
{
    public function adminList(): JsonResponse
    {
        return response()->json(
            User::query()->where('role', 'admin')->get(),
        );
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $password = Str::random(10);

        $user = User::query()->create([
            ...$validated,
            'role' => 'admin',
            'password' => $password,
            'is_active' => true,
        ]);

        Mail::to($user->email)->send(new StaffInviteMail($user->first_name, $user->email, $password));

        AuditLogger::log('Created admin user', 'Users', "Created admin user {$user->email}");

        return back()->with('success', 'Admin user created successfully.');
    }

    public function adminUpdate(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'string', 'in:admin,therapist,client'],
            'new_intake' => ['sometimes', 'boolean'],
            'invoice_payments' => ['sometimes', 'boolean'],
            'session_reminders' => ['sometimes', 'boolean'],
            'new_applications' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        AuditLogger::log('Updated user', 'Users', "Updated user #{$user->id} ({$user->email})");

        return back()->with('success', 'User updated successfully.');
    }
}
