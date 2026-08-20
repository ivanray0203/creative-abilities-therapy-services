<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Debounced duplicate-email check used by both public forms, mirroring the
 * reference's `checkEmail()` helper (src/lib/helpers.tsx).
 *
 * Phase 17 narrowed this to accounts and job applications. A pending Intake
 * no longer counts: a parent whose first intake is still under review has no
 * account yet, so flagging them would leave them unable to submit here and
 * unable to log in anywhere else.
 *
 * Rate-limited at the route to blunt its use as a client-list oracle.
 */
class CheckEmailController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];

        $exists = User::query()->where('email', $email)->exists()
            || Application::query()->where('email', $email)->exists();

        return response()->json(['exists' => $exists]);
    }
}
