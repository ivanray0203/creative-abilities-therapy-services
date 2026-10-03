<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Redirect a user to their own role's home when they hit a route
     * outside the roles they hold, mirroring the reference frontend's
     * ProtectedRoute role-mismatch behavior.
     *
     * A user who holds the route's role acts in it for the rest of the
     * request, so someone with several roles is scoped to this portal.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('public.home');
        }

        if (! $user->hasAnyRole($roles)) {
            // An account whose primary role was never granted (not yet run
            // through roles:transfer) has no portal to land on; sending it
            // to that role's home would redirect straight back here.
            return redirect($user->hasRole($user->role) ? self::homeFor($user->role) : '/');
        }

        $user->actAs(collect($roles)->first(fn (string $role): bool => $user->hasRole($role)));

        return $next($request);
    }

    public static function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => '/admin/intake',
            'therapist' => '/therapist',
            'client' => '/client/calendar',
            default => '/',
        };
    }
}
