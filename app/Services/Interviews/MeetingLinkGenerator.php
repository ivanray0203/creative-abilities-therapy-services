<?php

namespace App\Services\Interviews;

use App\Models\Application;

/**
 * Creates or moves the video-call booking behind an interview and returns
 * the link the candidate joins with. Implementations must not throw for an
 * ordinary outage — a booked interview is worth more than a missing link,
 * so failures are logged and reported as null.
 */
interface MeetingLinkGenerator
{
    /**
     * @return array{meeting_link: string, event_id: string}|null
     */
    public function schedule(Application $application): ?array;

    public function cancel(Application $application): void;
}
