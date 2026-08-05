<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page, or redirect an already-authenticated user home.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect(EnsureRole::homeFor(Auth::user()->role));
        }

        return Inertia::render('auth/login');
    }

    /**
     * Authenticate and redirect by role, mirroring the reference's
     * post-login redirect (admin -> intake, therapist -> dashboard,
     * client -> calendar).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        AuditLogger::log('User logged in', 'Authentication', "{$request->user()->email} logged in");

        return redirect(EnsureRole::homeFor(Auth::user()->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $email = $request->user()?->email;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($email !== null) {
            AuditLogger::log('User logged out', 'Authentication', "{$email} logged out", userEmail: $email);
        }

        return redirect()->route('public.home');
    }
}
