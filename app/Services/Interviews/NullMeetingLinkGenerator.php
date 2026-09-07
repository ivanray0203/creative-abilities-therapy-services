<?php

namespace App\Services\Interviews;

use App\Models\Application;

/**
 * Stand-in until the admin has connected a Google account: interviews are
 * still booked and emailed, just without a Meet link.
 */
class NullMeetingLinkGenerator implements MeetingLinkGenerator
{
    public function schedule(Application $application): ?array
    {
        return null;
    }

    public function cancel(Application $application): void {}
}
