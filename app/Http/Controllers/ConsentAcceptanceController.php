<?php

namespace App\Http\Controllers;

use App\Models\ConsentDocument;
use App\Models\UserConsentAcceptance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Scaffolding only, per Phase 3 scope. Full consent-document CMS content
 * and the public intake-form wiring land in Phase 5.
 */
class ConsentAcceptanceController extends Controller
{
    /**
     * Current, active consent documents for a purpose (intake|application),
     * flagged with whether the given/authenticated user already accepted
     * them. Mirrors the reference's AllowAny `required_consents` action.
     */
    public function required(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'in:intake,application'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $userId = $validated['user_id'] ?? Auth::id();

        $documents = ConsentDocument::query()
            ->where('purpose', $validated['purpose'])
            ->where('is_active', true)
            ->where('effective_date', '<=', now())
            ->with('clauses')
            ->orderByDesc('effective_date')
            ->get()
            ->map(fn (ConsentDocument $document) => [
                ...$document->toArray(),
                'accepted' => $userId
                    ? $document->userAcceptances()->where('user_id', $userId)->where('is_revoked', false)->exists()
                    : false,
            ]);

        return response()->json($documents);
    }

    /**
     * The authenticated user's own consent acceptance history.
     */
    public function mine(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->consentAcceptances()->with('document')->get()
        );
    }

    /**
     * Record acceptance of a consent document. AllowAny in the reference
     * (used by the public intake form before an account necessarily
     * exists) — accepts an explicit user_id until Phase 5 wires the real
     * intake-submission user-resolution flow.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'integer', 'exists:consent_documents,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $userId = $validated['user_id'] ?? Auth::id();

        abort_unless($userId, 422, 'A user_id is required to accept consent.');

        $acceptance = UserConsentAcceptance::query()->updateOrCreate(
            ['document_id' => $validated['document_id'], 'user_id' => $userId],
            ['accepted_at' => now(), 'is_revoked' => false, 'revoked_at' => null],
        );

        return response()->json($acceptance);
    }

    public function revoke(UserConsentAcceptance $consentAcceptance): JsonResponse
    {
        $user = Auth::user();

        abort_unless($user && ($user->id === $consentAcceptance->user_id || $user->isAdmin()), 403);

        $consentAcceptance->revoke();

        return response()->json($consentAcceptance->fresh());
    }
}
