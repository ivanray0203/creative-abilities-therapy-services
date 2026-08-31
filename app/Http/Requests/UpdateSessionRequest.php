<?php

namespace App\Http\Requests;

use App\Models\ScheduleSession;

/**
 * Same field set as StoreSessionRequest — reschedules reuse the same
 * date + start_time + end_time → scheduled_start/scheduled_end computation.
 */
class UpdateSessionRequest extends StoreSessionRequest
{
    /**
     * A session always overlaps itself, so the row being edited is excluded
     * from the conflict check — otherwise saving a session without moving it
     * would fail against its own booking. Phase 20 gave the same id a second
     * job: excluding the session's own hours from its contract's balance.
     */
    protected function ignoredSessionId(): ?int
    {
        return $this->editedSession()?->id;
    }

    private function editedSession(): ?ScheduleSession
    {
        $session = $this->route('session');

        return $session instanceof ScheduleSession ? $session : null;
    }
}
