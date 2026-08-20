<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves which of a parent's children the client portal is currently
 * showing.
 *
 * Phase 17 allows one parent User to own several Client records (one per
 * child). Rather than merging them, the portal scopes every page to exactly
 * one child at a time, chosen by a switcher in the client layout and
 * remembered in the session.
 */
class ClientContext
{
    /**
     * Session key holding the currently selected child's client id.
     */
    public const SESSION_KEY = 'selected_client_id';

    /**
     * Every child belonging to this parent, oldest record first so the
     * default selection is stable across requests.
     *
     * The intake is loaded whole rather than column-limited — callers of
     * {@see current()} read address and contact fields off it, and a partial
     * select would silently hand them nulls.
     *
     * @return Collection<int, Client>
     */
    public function children(User $user): Collection
    {
        return $user->clientProfiles()
            ->with('originalIntake')
            ->orderBy('id')
            ->get();
    }

    /**
     * The child currently in view. Falls back to the first child when nothing
     * is selected, or when the selected id doesn't belong to this parent —
     * which also stops a tampered session value from reaching another
     * family's records.
     */
    public function current(User $user): ?Client
    {
        $children = $this->children($user);
        $selectedId = session(self::SESSION_KEY);

        if ($selectedId !== null) {
            $selected = $children->firstWhere('id', (int) $selectedId);

            if ($selected !== null) {
                return $selected;
            }
        }

        return $children->first();
    }

    /**
     * Convenience for the many queries that scope by `client_id` alone.
     */
    public function currentId(User $user): ?int
    {
        return $this->current($user)?->id;
    }

    /**
     * Ids of every child belonging to this parent.
     *
     * @return array<int, int>
     */
    public function childIds(User $user): array
    {
        return $user->clientProfiles()->pluck('id')->all();
    }

    /**
     * Whether a client record belongs to this parent.
     *
     * Authorization deliberately spans *all* the parent's children rather
     * than the selected one — a parent verifying an invoice or disputing a
     * session for their second child shouldn't have to switch first. Only
     * list scoping uses {@see currentId()}.
     */
    public function owns(User $user, ?int $clientId): bool
    {
        return $clientId !== null
            && in_array($clientId, $this->childIds($user), true);
    }

    /**
     * Switch the portal to another of this parent's children. Returns false
     * when the id isn't theirs, leaving the current selection untouched.
     */
    public function select(User $user, int $clientId): bool
    {
        if (! $this->children($user)->contains('id', $clientId)) {
            return false;
        }

        session([self::SESSION_KEY => $clientId]);

        return true;
    }
}
