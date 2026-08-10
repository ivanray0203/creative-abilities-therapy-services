<?php

namespace App\Http\Requests;

use App\Models\ScheduleSession;

/**
 * Same field set as StoreSessionRequest — reschedules reuse the same
 * date/time/duration → scheduled_start/scheduled_end computation.
 */
class UpdateSessionRequest extends StoreSessionRequest
{
    /**
     * A session always overlaps itself, so the row being edited is excluded
     * from the conflict check — otherwise saving a session without moving it
     * would fail against its own booking.
     */
    protected function ignoredSessionId(): ?int
    {
        return $this->editedSession()?->id;
    }

    /**
     * @return array<int, int>
     */
    protected function linkedClientServiceIds(): array
    {
        return $this->editedSession()
            ?->clientServices()
            ->pluck('client_services.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all() ?? [];
    }

    private function editedSession(): ?ScheduleSession
    {
        $session = $this->route('session');

        return $session instanceof ScheduleSession ? $session : null;
    }
}
