<?php

namespace App\Policies;

use App\Models\ScheduleSession;
use App\Models\User;
use App\Services\ClientContext;

/**
 * Phase 18 — centralises the ownership rules SessionController previously
 * carried as private assert helpers, so the same questions get the same
 * answer wherever they're asked.
 */
class ScheduleSessionPolicy
{
    public function __construct(private ClientContext $clientContext) {}

    /**
     * Editing, cancelling, deleting and clocking a session in or out all
     * belong to the therapist running it, or to an admin.
     */
    public function manage(User $user, ScheduleSession $session): bool
    {
        return $user->isAdmin() || $session->therapist_id === $user->id;
    }

    /**
     * Sign-off is the client's own act. It spans every one of the parent's
     * children so they needn't switch the portal first.
     */
    public function verify(User $user, ScheduleSession $session): bool
    {
        return $this->clientContext->owns($user, $session->client_id);
    }

    /**
     * Either side of a session may dispute it; admins may act on any.
     */
    public function dispute(User $user, ScheduleSession $session): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTherapist()) {
            return $session->therapist_id === $user->id;
        }

        return $user->isClient()
            && $this->clientContext->owns($user, $session->client_id);
    }
}
