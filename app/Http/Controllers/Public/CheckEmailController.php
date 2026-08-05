<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Debounced duplicate-email check used by both public forms, mirroring the
 * reference's `checkEmail()` helper (src/lib/helpers.tsx) which warns but
 * does not block submission.
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
            || Application::query()->where('email', $email)->exists()
            || Intake::query()->where('primary_parent_email', $email)->exists();

        return response()->json(['exists' => $exists]);
    }
}
