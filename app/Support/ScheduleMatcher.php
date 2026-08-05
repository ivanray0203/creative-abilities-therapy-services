<?php

namespace App\Support;

/**
 * Ported from cats-frontend/src/lib/helpers.tsx `getScheduleMatchDetails`.
 * Compares an intake's `available_days`/`preferred_times` against a
 * therapist's `TeamMember.availability` to produce a none/partial/full
 * schedule-match verdict for the therapist-facing intake review page.
 */
class ScheduleMatcher
{
    /**
     * Fixed preferred-time buckets, matching the reference's hardcoded map
     * (resources/js/lib/content/intake-taxonomy.ts's `times` list).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const TIME_RANGES = [
        'Mornings (8am-11am)' => ['08:00', '11:00'],
        'Afternoon (1pm-3pm)' => ['13:00', '15:00'],
        'Evenings (4pm-7pm)' => ['16:00', '19:00'],
    ];

    /**
     * @param  array<int, string>  $availableDays
     * @param  array<int, string>  $preferredTimes
     * @param  array<int, array{week_day?: string, time_from?: ?string, time_to?: ?string}>  $therapistAvailability
     * @return array{match: bool, matched_days: array<int, string>, matched_times: array<int, string>, details: array<int, array<string, string>>, matchLevel: string}
     */
    public static function match(array $availableDays, array $preferredTimes, array $therapistAvailability): array
    {
        $matchedDays = [];
        $matchedTimes = [];
        $details = [];
        $totalChecks = 0;
        $totalMatches = 0;

        foreach ($availableDays as $day) {
            $therapistDay = self::findTherapistDay($therapistAvailability, $day);

            foreach ($preferredTimes as $preferred) {
                $totalChecks++;

                $range = self::TIME_RANGES[$preferred] ?? null;

                if ($range === null || $therapistDay === null) {
                    continue;
                }

                [$preferredStart, $preferredEnd] = $range;
                $hasOverlap = $therapistDay['time_from'] <= $preferredEnd && $therapistDay['time_to'] >= $preferredStart;

                if (! $hasOverlap) {
                    continue;
                }

                $totalMatches++;

                if (! in_array($day, $matchedDays, true)) {
                    $matchedDays[] = $day;
                }

                if (! in_array($preferred, $matchedTimes, true)) {
                    $matchedTimes[] = $preferred;
                }

                $details[] = [
                    'day' => $day,
                    'preferred' => $preferred,
                    'therapist_from' => $therapistDay['time_from'],
                    'therapist_to' => $therapistDay['time_to'],
                ];
            }
        }

        $matchLevel = match (true) {
            $totalMatches === 0 => 'none',
            $totalMatches < $totalChecks => 'partial',
            default => 'full',
        };

        return [
            'match' => count($details) > 0,
            'matched_days' => $matchedDays,
            'matched_times' => $matchedTimes,
            'details' => $details,
            'matchLevel' => $matchLevel,
        ];
    }

    /**
     * @param  array<int, array{week_day?: string, time_from?: ?string, time_to?: ?string}>  $therapistAvailability
     * @return array{time_from: string, time_to: string}|null
     */
    private static function findTherapistDay(array $therapistAvailability, string $day): ?array
    {
        foreach ($therapistAvailability as $slot) {
            if (($slot['week_day'] ?? null) === $day && filled($slot['time_from'] ?? null) && filled($slot['time_to'] ?? null)) {
                return ['time_from' => $slot['time_from'], 'time_to' => $slot['time_to']];
            }
        }

        return null;
    }
}
