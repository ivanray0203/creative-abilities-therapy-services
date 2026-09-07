<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a therapist who is still onboarding on their profile.
 *
 * Hiding the sidebar is not access control — without this a candidate who
 * types `/therapist/clients` would still reach client records before an
 * admin has reviewed their documents and hired them. Everything under the
 * therapist portal except the profile (and its document uploads) redirects
 * back to the profile until the hire goes through.
 */
class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isOnboarding() === true && ! $request->routeIs('therapist.team.*')) {
            return redirect()->route('therapist.team.me');
        }

        return $next($request);
    }
}
