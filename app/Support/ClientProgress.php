<?php

namespace App\Support;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use Illuminate\Support\Collection;

/**
 * The numbers behind the client Progress tab.
 *
 * Everything here is derived from data already captured but never read back:
 * `client_services.no_sessions` (how many sessions were authorised),
 * `client_services.goals`, and the `elapsed_time` a therapist's clock-out
 * records on each session.
 */
class ClientProgress
{
    /**
     * A visit counts as delivered once the therapist has clocked out of it:
     * `pending` awaits the family's sign-off, `confirmed` has it, and
     * `completed` has been paid for. Cancelled and missed visits are not
     * delivery — they are counted separately, under attendance.
     *
     * @var array<int, string>
     */
    public const DELIVERED_STATUSES = ['pending', 'confirmed', 'completed'];

    /**
     * @return array{
     *     services: array<int, array{id: int, name: string, therapist: string|null, frequency: string|null, goals: string|null, authorised: int|null, delivered: int, remaining: int|null, percent: int|null, hours: float}>,
     *     attendance: array{attended: int, cancelled: int, no_show: int, rate: int|null},
     *     hours: array{total: float, this_month: float},
     *     has_data: bool,
     * }
     */
    public static function for(Client $client): array
    {
        $client->loadMissing([
            'clientServices.service',
            'clientServices.therapist',
            'sessions.clientServices',
        ]);

        $sessions = $client->sessions;
        $delivered = $sessions->whereIn('status', self::DELIVERED_STATUSES);

        $services = $client->clientServices
            ->map(fn (ClientService $clientService): array => self::forService($clientService, $delivered))
            ->values()
            ->all();

        $attended = $delivered->count();
        $noShow = $sessions->where('status', 'no_show')->count();

        return [
            'services' => $services,
            'attendance' => [
                'attended' => $attended,
                'cancelled' => $sessions->where('status', 'cancelled')->count(),
                'no_show' => $noShow,
                // Cancellations are usually agreed in advance, so they do not
                // count against the family the way a missed visit does.
                'rate' => $attended + $noShow > 0
                    ? (int) round($attended / ($attended + $noShow) * 100)
                    : null,
            ],
            'hours' => [
                'total' => self::hours($delivered),
                'this_month' => self::hours($delivered->filter(
                    fn (ScheduleSession $session): bool => $session->scheduled_start !== null
                        && $session->scheduled_start->isCurrentMonth(),
                )),
            ],
            'has_data' => $services !== [] || $sessions->isNotEmpty(),
        ];
    }

    /**
     * @param  Collection<int, ScheduleSession>  $delivered
     * @return array{id: int, name: string, therapist: string|null, frequency: string|null, goals: string|null, authorised: int|null, delivered: int, remaining: int|null, percent: int|null, hours: float}
     */
    private static function forService(ClientService $clientService, Collection $delivered): array
    {
        // One visit can cover several services, so the same session counts
        // toward each of them here — but only once in the overall totals.
        $own = $delivered->filter(
            fn (ScheduleSession $session): bool => $session->clientServices->contains('id', $clientService->id),
        );

        $count = $own->count();
        // A service with no authorised figure has nothing to measure against,
        // so it reports a count rather than a misleading 0%.
        $authorised = $clientService->no_sessions > 0 ? $clientService->no_sessions : null;

        return [
            'id' => $clientService->id,
            'name' => $clientService->service !== null ? $clientService->service->name : 'Service',
            'therapist' => $clientService->therapist?->full_name,
            'frequency' => $clientService->frequency,
            'goals' => $clientService->goals,
            'authorised' => $authorised,
            'delivered' => $count,
            'remaining' => $authorised !== null ? max(0, $authorised - $count) : null,
            'percent' => $authorised !== null
                ? (int) min(100, round($count / $authorised * 100))
                : null,
            'hours' => self::hours($own),
        ];
    }

    /**
     * `elapsed_time` is the wall-clock duration a therapist actually spent,
     * stored as H:i:s at clock-out. Sessions delivered before that was
     * recorded fall back to the booked duration, which is in minutes.
     *
     * @param  Collection<int, ScheduleSession>  $sessions
     */
    private static function hours(Collection $sessions): float
    {
        $minutes = $sessions->sum(function (ScheduleSession $session): float {
            if ($session->elapsed_time !== null && $session->elapsed_time !== '') {
                [$hours, $mins, $seconds] = array_pad(explode(':', $session->elapsed_time), 3, '0');

                return (int) $hours * 60 + (int) $mins + (int) $seconds / 60;
            }

            return (float) ($session->duration ?? 0);
        });

        return round($minutes / 60, 1);
    }
}
