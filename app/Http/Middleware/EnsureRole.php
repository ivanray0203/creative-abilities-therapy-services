<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Redirect a user to their own role's home when they hit a route
     * outside their role, mirroring the reference frontend's
     * ProtectedRoute role-mismatch behavior.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('public.home');
        }

        if (! in_array($user->role, $roles, true)) {
            return redirect(self::homeFor($user->role));
        }

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
