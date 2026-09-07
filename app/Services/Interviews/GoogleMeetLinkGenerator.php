<?php

namespace App\Services\Interviews;

use App\Models\Application;
use App\Services\GoogleDrive\GoogleAccountClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Books the interview on the connected Google account's primary calendar
 * with a Google Meet conference attached, and hands back the join link.
 *
 * Google only issues Meet links through calendar events, which is why an
 * event is created rather than a bare link. The event id is stored on the
 * application so a reschedule moves that event — the candidate keeps the
 * link they were already sent — and a decline or cancellation removes it.
 */
class GoogleMeetLinkGenerator implements MeetingLinkGenerator
{
    private const CALENDAR_ID = 'primary';

    private ?GoogleCalendar $service = null;

    public function __construct(private readonly GoogleAccountClient $account) {}

    public function schedule(Application $application): ?array
    {
        $startsAt = $application->interviewStartsAt();

        if ($startsAt === null) {
            return null;
        }

        $endsAt = $startsAt->addMinutes((int) config('cats.interview.duration_minutes', 60));
        $timezone = (string) config('cats.interview.timezone');

        $event = new Event([
            'summary' => "Interview: {$application->first_name} {$application->last_name} – {$application->position_applied}",
            'description' => "Interview for the {$application->position_applied} position. Application reference: {$application->reference_number}.",
            'start' => new EventDateTime(['dateTime' => $startsAt->toRfc3339String(), 'timeZone' => $timezone]),
            'end' => new EventDateTime(['dateTime' => $endsAt->toRfc3339String(), 'timeZone' => $timezone]),
            'attendees' => [new EventAttendee(['email' => $application->email])],
        ]);

        try {
            $existingId = $application->interview_calendar_event_id;

            $saved = $existingId !== null
                ? $this->service()->events->patch(self::CALENDAR_ID, $existingId, $event, ['conferenceDataVersion' => 1])
                : $this->service()->events->insert(self::CALENDAR_ID, $this->withMeet($event), ['conferenceDataVersion' => 1]);

            $link = $saved->getHangoutLink();

            // An event that pre-dates the Meet request, or whose conference
            // was removed by hand, comes back without a link — attach one.
            if (blank($link)) {
                $saved = $this->service()->events->patch(self::CALENDAR_ID, $saved->getId(), $this->withMeet(new Event), ['conferenceDataVersion' => 1]);
                $link = $saved->getHangoutLink();
            }

            if (blank($link)) {
                Log::warning('Google Calendar created the interview event but returned no Meet link.', [
                    'application_id' => $application->id,
                    'event_id' => $saved->getId(),
                ]);

                return null;
            }

            return ['meeting_link' => $link, 'event_id' => $saved->getId()];
        } catch (\Throwable $exception) {
            Log::error('Failed to create the Google Meet link for an interview.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function cancel(Application $application): void
    {
        $eventId = $application->interview_calendar_event_id;

        if ($eventId === null) {
            return;
        }

        try {
            $this->service()->events->delete(self::CALENDAR_ID, $eventId);
        } catch (\Throwable $exception) {
            Log::warning('Failed to remove the interview event from Google Calendar.', [
                'application_id' => $application->id,
                'event_id' => $eventId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function withMeet(Event $event): Event
    {
        $event->setConferenceData(new ConferenceData([
            'createRequest' => new CreateConferenceRequest([
                'requestId' => (string) Str::uuid(),
                'conferenceSolutionKey' => new ConferenceSolutionKey(['type' => 'hangoutsMeet']),
            ]),
        ]));

        return $event;
    }

    private function service(): GoogleCalendar
    {
        return $this->service ??= new GoogleCalendar(
            $this->account->make([GoogleCalendar::CALENDAR_EVENTS]),
        );
    }
}
