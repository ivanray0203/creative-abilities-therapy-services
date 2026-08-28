<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Splits the therapist portal down the middle: an aide records hours on a
 * time sheet, everyone else bills services and raises invoices.
 *
 * Hiding a menu item is not access control — without this an aide who types
 * `/therapist/billing` still reaches a form that would raise money lines
 * against their name. Both halves are guarded:
 *
 *   ->middleware('aide')        Hours and Timesheets — aides only.
 *   ->middleware('aide:never')  Billing and Invoices — everyone but aides.
 *
 * A mismatch redirects to the equivalent screen on the user's own side
 * rather than aborting, mirroring EnsureRole's role-mismatch behaviour.
 */
class EnsureAide
{
    private const AIDE_HOME = '/therapist/hours';

    private const THERAPIST_HOME = '/therapist/billing';

    /**
     * @param  string  $mode  'only' (the default) or 'never'.
     */
    public function handle(Request $request, Closure $next, string $mode = 'only'): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('public.home');
        }

        $isAide = $user->isAide();

        if ($mode === 'never' && $isAide) {
            return redirect(self::AIDE_HOME);
        }

        if ($mode === 'only' && ! $isAide) {
            return redirect(self::THERAPIST_HOME);
        }

        return $next($request);
    }
}
