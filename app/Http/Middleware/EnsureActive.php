<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of an account that has been deactivated.
 *
 * `users.is_active` is set from a team member's employment status — marking
 * someone terminated flips it — but nothing read it back, so a suspended
 * therapist kept full access until they happened to log out. Checking on each
 * request means revoking access takes effect immediately rather than at the
 * next login.
 */
class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Please contact an administrator.',
            ]);
        }

        return $next($request);
    }
}
