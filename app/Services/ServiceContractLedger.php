<?php

namespace App\Services;

use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 20 — every hour that moves between a session and a contract moves
 * through here.
 *
 * A session draws hours when it is booked and hands them back when it is
 * cancelled or missed. The figure drawn is the length the session was booked
 * for, not the time it actually ran: admin authorizes scheduled hours, and a
 * visit that overruns by ten minutes must not quietly resize the pool. The
 * record of each draw is a row on `client_service_schedule_session`: which
 * availed service, how many hours, and which contract they came from.
 *
 * The balance is never stored. It is summed from those rows, so there is no
 * cached total to drift out of step with the sessions themselves.
 */
class ServiceContractLedger
{
    /**
     * The error bag key the session forms show contract problems under.
     */
    public const ERROR_KEY = 'linked_client_services';

    /**
     * A therapist's own arithmetic is allowed to be a hundredth of an hour
     * out from the session's length. Anything wider is a real disagreement.
     */
    private const SPLIT_TOLERANCE = 0.011;

    /**
     * Spread a session's length evenly across the availed services it covers.
     *
     * The remainder lands on the last service so the parts still add up to
     * the whole: three services across two hours is 0.67 + 0.67 + 0.66, not
     * three times 0.67.
     *
     * @param  array<int, int>  $clientServiceIds
     * @return array<int, array{client_service_id: int, hours: float}>
     */
    public function defaultAllocations(array $clientServiceIds, int $sessionMinutes): array
    {
        $count = count($clientServiceIds);

        if ($count === 0) {
            return [];
        }

        $total = round($sessionMinutes / 60, 2);
        $share = round($total / $count, 2);

        $allocations = [];

        foreach (array_values($clientServiceIds) as $index => $id) {
            $allocations[] = [
                'client_service_id' => (int) $id,
                'hours' => $index === $count - 1
                    ? round($total - $share * ($count - 1), 2)
                    : $share,
            ];
        }

        return $allocations;
    }

    /**
     * Everything wrong with a proposed draw, in the order a therapist would
     * want to hear it. An empty array means the booking may go ahead.
     *
     * Both the form request and `apply()` call this. The first pass gives the
     * therapist a field error instead of an exception page; the second runs
     * under a row lock, because between the two another therapist may have
     * taken the hours.
     *
     * @param  array<int, array{client_service_id: int, hours: float}>  $allocations
     * @return array<int, string>
     */
    public function problems(
        array $allocations,
        CarbonInterface $date,
        int $sessionMinutes,
        ?int $ignoreSessionId = null,
    ): array {
        if ($allocations === []) {
            return [];
        }

        $problems = [];

        $splitProblem = $this->splitProblem($allocations, $sessionMinutes);

        if ($splitProblem !== null) {
            $problems[] = $splitProblem;
        }

        $services = $this->servicesFor($allocations);

        foreach ($allocations as $allocation) {
            $service = $services->get($allocation['client_service_id']);

            if ($service === null) {
                continue;
            }

            $contract = $service->contractOn($date);

            if ($contract === null) {
                $problems[] = $this->unbookableReason($service, $date);

                continue;
            }

            /*
             * The session's own existing draw is excluded, or moving a
             * two-hour booking by ten minutes would fail against itself.
             */
            $remaining = $contract->remainingHours($ignoreSessionId);

            if ($allocation['hours'] > $remaining + self::SPLIT_TOLERANCE) {
                $problems[] = sprintf(
                    '%s has %s of %s hours left on its contract, and this session needs %s.',
                    $service->serviceName(),
                    $this->format($remaining),
                    $this->format((float) $contract->allotted_hours),
                    $this->format($allocation['hours']),
                );
            }
        }

        return $problems;
    }

    /**
     * Why an availed service cannot be booked on a given day.
     *
     * "No contract yet", "the contract ended last week" and "the hours are
     * gone" are three different problems, and only one of them is the
     * therapist's to solve. Saying which one saves a support ticket.
     */
    public function unbookableReason(ClientService $service, CarbonInterface $date): string
    {
        $name = $service->serviceName();

        $covering = $service->contractOn($date);

        if ($covering !== null) {
            return sprintf(
                '%s has no hours left: %s of %s used.',
                $name,
                $this->format($covering->usedHours()),
                $this->format((float) $covering->allotted_hours),
            );
        }

        /** @var ServiceContract|null $latest */
        $latest = $service->contracts()
            ->where('status', '!=', ServiceContract::STATUS_CANCELLED)
            ->orderByDesc('period_end')
            ->first();

        if ($latest === null) {
            return "{$name} has no contract. An admin has to issue one before it can be scheduled.";
        }

        if ($latest->period_end !== null && $latest->period_end->lessThan($date)) {
            return sprintf('%s has no contract covering that date — the last one ended %s.',
                $name, $latest->period_end->format('j M Y'));
        }

        return sprintf('%s has no contract covering that date — the next one starts %s.',
            $name, $latest->period_start?->format('j M Y') ?? 'later');
    }

    /**
     * Write the draw, under a lock, having re-checked it.
     *
     * Syncing the pivot and checking the balance have to happen together: two
     * therapists taking the last hours of one contract would otherwise both
     * pass validation and both write.
     *
     * @param  array<int, array{client_service_id: int, hours: float}>  $allocations
     *
     * @throws ValidationException
     */
    public function apply(ScheduleSession $session, array $allocations, int $sessionMinutes): void
    {
        $date = $session->scheduled_start ?? now();

        DB::transaction(function () use ($session, $allocations, $sessionMinutes, $date): void {
            $this->lockContractsFor($allocations, $date);

            $problems = $this->problems($allocations, $date, $sessionMinutes, $session->id);

            if ($problems !== []) {
                throw ValidationException::withMessages([self::ERROR_KEY => $problems]);
            }

            $services = $this->servicesFor($allocations);
            $payload = [];

            foreach ($allocations as $allocation) {
                $service = $services->get($allocation['client_service_id']);

                if ($service === null) {
                    continue;
                }

                $payload[$allocation['client_service_id']] = [
                    'hours' => $allocation['hours'],
                    'service_contract_id' => $service->contractOn($date)?->id,
                ];
            }

            $session->clientServices()->sync($payload);
        });
    }

    /**
     * Remaining hours for many contracts in one query, keyed by contract id.
     *
     * `ServiceContract::remainingHours()` costs a `SUM` per contract, which a
     * picker listing a whole caseload cannot afford. Same definition of
     * "used", one round trip.
     *
     * @param  iterable<int, ServiceContract>  $contracts
     * @return array<int, float>
     */
    public function remainingFor(iterable $contracts): array
    {
        $contracts = collect($contracts);

        if ($contracts->isEmpty()) {
            return [];
        }

        $used = DB::table('client_service_schedule_session as ledger')
            ->join('schedule_sessions as drawn_session', 'drawn_session.id', '=', 'ledger.schedule_session_id')
            ->whereIn('ledger.service_contract_id', $contracts->pluck('id'))
            ->whereNotIn('drawn_session.status', ScheduleSession::HOURS_RELEASING_STATUSES)
            ->groupBy('ledger.service_contract_id')
            ->selectRaw('ledger.service_contract_id as contract_id, SUM(ledger.hours) as drawn')
            ->get()
            ->keyBy('contract_id');

        return $contracts->mapWithKeys(fn (ServiceContract $contract): array => [
            $contract->id => round(
                (float) $contract->allotted_hours - (float) ($used->get($contract->id)->drawn ?? 0),
                2,
            ),
        ])->all();
    }

    /**
     * The availed services named by a set of allocations, keyed by id.
     *
     * @param  array<int, array{client_service_id: int, hours: float}>  $allocations
     * @return Collection<int, ClientService>
     */
    private function servicesFor(array $allocations): Collection
    {
        return ClientService::query()
            ->with('service')
            ->findMany(array_column($allocations, 'client_service_id'))
            ->keyBy('id');
    }

    /**
     * Take the contract rows in ascending id order so two multi-service
     * bookings racing each other queue up rather than deadlock.
     *
     * @param  array<int, array{client_service_id: int, hours: float}>  $allocations
     */
    private function lockContractsFor(array $allocations, CarbonInterface $date): void
    {
        $contractIds = ServiceContract::query()
            ->whereIn('client_service_id', array_column($allocations, 'client_service_id'))
            ->coveringOn($date)
            ->orderBy('id')
            ->pluck('id');

        if ($contractIds->isEmpty()) {
            return;
        }

        ServiceContract::query()
            ->whereIn('id', $contractIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  array<int, array{client_service_id: int, hours: float}>  $allocations
     */
    private function splitProblem(array $allocations, int $sessionMinutes): ?string
    {
        $expected = round($sessionMinutes / 60, 2);
        $given = round(array_sum(array_column($allocations, 'hours')), 2);

        if (abs($given - $expected) <= self::SPLIT_TOLERANCE) {
            return null;
        }

        return sprintf(
            'The hours split across the selected services adds up to %s, but the session is %s hours long.',
            $this->format($given),
            $this->format($expected),
        );
    }

    /** Trims a trailing `.00` so "40 hours" does not read as "40.00 hours". */
    private function format(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') ?: '0';
    }
}
