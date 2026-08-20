<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

/**
 * Phase 18 — `startReview` and `resolve` previously had no authorization;
 * only their placement in the admin route group kept them out of reach.
 *
 * List scoping stays in ComplaintController::scopedQuery() — that decides
 * which rows a role sees, which is a different question from whether they may
 * act on a given one.
 */
class ComplaintPolicy
{
    /**
     * Reviewing and resolving are administrative acts: a therapist named in a
     * complaint must not be able to close it themselves.
     */
    public function review(User $user, Complaint $complaint): bool
    {
        return $user->isAdmin();
    }
}
