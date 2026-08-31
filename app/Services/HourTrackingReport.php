<?php

namespace App\Services;

use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 21 — contracted hours, month by month.
 *
 * This is the clinic's hour-tracking sheet rendered from live data instead of
 * kept by hand: one section per service, one row per contract, a column per
 * month in the window, and what is left at the end of the row.
 *
 * One builder serves both readers. A therapist passes themselves and sees the
 * contracts they are authorized on; an admin passes null and sees the whole
 * clinic, or one therapist when they pick one from the filter.
 *
 * The monthly figures are the *booked* hours each session drew, summed by the
 * month the session was scheduled in — the same rows `ServiceContract::usedHours()`
 * counts, so a row's months and its remaining figure can never disagree.
 * Cancelled and no-show sessions release their hours and are excluded from
 * both, exactly as they are everywhere else.
 *
 * @phpstan-type MonthColumn array{key: string, label: string, year: int}
 * @phpstan-type ContractRow array{
 *     contract_id: int,
 *     contract_number: string,
 *     client_name: string,
 *     therapist_name: string,
 *     funding_code: string|null,
 *     period_start: string|null,
 *     period_end: string|null,
 *     allotted_hours: float,
 *     used_hours: float,
 *     remaining_hours: float,
 *     months: array<string, float|null>,
 * }
 */
class HourTrackingReport
{
    /**
     * The report over one window, for one therapist or for the whole clinic.
     *
     * `$therapist` null is the admin's view: every contract, whoever is
     * authorized on it. The shape is identical either way, so one renderer
     * serves both — only the therapist column is worth hiding when every row
     * would repeat the same name.
     *
     * A contract is in scope when its period overlaps the window at all, so a
     * year-long contract still shows on a one-month view with that month's
     * hours against it. Filtering on the contract's *start* would hide every
     * contract issued before the window and make the sheet read empty.
     *
     * @return array{
     *     months: array<int, MonthColumn>,
     *     sections: array<int, array{service: string, rows: array<int, ContractRow>,
     *         allotted_hours: float, used_hours: float, remaining_hours: float}>,
     *     totals: array{allotted_hours: float, used_hours: float, remaining_hours: float},
     * }
     */
    public function build(?User $therapist, CarbonInterface $from, CarbonInterface $to): array
    {
        $months = $this->months($from, $to);
        $contracts = $this->contractsFor($therapist, $from, $to);

        if ($contracts->isEmpty()) {
            return [
                'months' => $months,
                'sections' => [],
                'totals' => ['allotted_hours' => 0.0, 'used_hours' => 0.0, 'remaining_hours' => 0.0],
            ];
        }

        $drawn = $this->drawnByContractAndMonth($contracts->pluck('id')->all());
        $sections = [];

        foreach ($contracts->groupBy(fn (ServiceContract $c): string => $c->clientService?->serviceName() ?? 'Unassigned service') as $service => $group) {
            $rows = $group
                ->map(fn (ServiceContract $contract): array => $this->row($contract, $drawn, $months))
                ->sortBy('client_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();

            $sections[] = [
                'service' => (string) $service,
                'rows' => $rows,
                'allotted_hours' => $this->sum($rows, 'allotted_hours'),
                'used_hours' => $this->sum($rows, 'used_hours'),
                'remaining_hours' => $this->sum($rows, 'remaining_hours'),
            ];
        }

        usort($sections, fn (array $a, array $b): int => strcasecmp($a['service'], $b['service']));

        return [
            'months' => $months,
            'sections' => $sections,
            'totals' => [
                'allotted_hours' => round(array_sum(array_column($sections, 'allotted_hours')), 2),
                'used_hours' => round(array_sum(array_column($sections, 'used_hours')), 2),
                'remaining_hours' => round(array_sum(array_column($sections, 'remaining_hours')), 2),
            ],
        ];
    }

    /**
     * One column per month the window touches, oldest first.
     *
     * The year rides along on every column: a window spanning a new year would
     * otherwise show two columns both labelled "Jan".
     *
     * @return array<int, MonthColumn>
     */
    public function months(CarbonInterface $from, CarbonInterface $to): array
    {
        $cursor = $from->copy()->startOfMonth();
        $last = $to->copy()->startOfMonth();
        $months = [];

        // A guard rather than a limit: a hand-typed range of a thousand years
        // would otherwise build a thousand columns before rendering fails.
        while ($cursor->lessThanOrEqualTo($last) && count($months) < 120) {
            $months[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M'),
                'year' => (int) $cursor->format('Y'),
            ];

            // Assigned, not called for its side effect: this app's dates are
            // immutable, and `$cursor->addMonth()` alone would loop forever.
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * The contracts in scope: those whose period overlaps the window, and —
     * unless the whole clinic is being asked for — those the given therapist
     * is authorized on.
     *
     * Cancelled contracts are included: a cancelled contract's delivered hours
     * are still part of the record the sheet is for.
     *
     * @return Collection<int, ServiceContract>
     */
    private function contractsFor(?User $therapist, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return ServiceContract::query()
            // `originalIntake` too: Client::displayName() reads the child's
            // name off it, and a row per contract would otherwise be a query
            // per contract.
            ->with(['clientService.service', 'clientService.client.originalIntake', 'therapist'])
            ->when(
                $therapist !== null,
                fn (Builder $query) => $query->where('therapist_id', $therapist->id),
            )
            ->whereDate('period_start', '<=', $to->toDateString())
            ->whereDate('period_end', '>=', $from->toDateString())
            ->orderBy('period_start')
            ->get();
    }

    /**
     * Hours drawn per contract per month, in one query.
     *
     * Keyed `"{contract id}:{Y-m}"`. Grouping in PHP rather than SQL keeps the
     * month arithmetic off the database: the suite runs on SQLite and
     * production is MySQL, and their date functions do not agree.
     *
     * @param  array<int, int>  $contractIds
     * @return array<string, float>
     */
    private function drawnByContractAndMonth(array $contractIds): array
    {
        if ($contractIds === []) {
            return [];
        }

        $rows = DB::table('client_service_schedule_session as ledger')
            ->join('schedule_sessions as drawn_session', 'drawn_session.id', '=', 'ledger.schedule_session_id')
            ->whereIn('ledger.service_contract_id', $contractIds)
            ->whereNotIn('drawn_session.status', ScheduleSession::HOURS_RELEASING_STATUSES)
            ->select([
                'ledger.service_contract_id as contract_id',
                'ledger.hours as hours',
                'drawn_session.scheduled_start as scheduled_start',
            ])
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            if ($row->scheduled_start === null) {
                continue;
            }

            $key = $row->contract_id.':'.Carbon::parse($row->scheduled_start)->format('Y-m');
            $totals[$key] = round(($totals[$key] ?? 0) + (float) $row->hours, 2);
        }

        return $totals;
    }

    /**
     * @param  array<string, float>  $drawn
     * @param  array<int, MonthColumn>  $months
     * @return ContractRow
     */
    private function row(ServiceContract $contract, array $drawn, array $months): array
    {
        $cells = [];

        foreach ($months as $month) {
            // Null, not zero: a month with no session reads as a blank cell,
            // which is a different fact from a month that drew nothing.
            $cells[$month['key']] = $drawn[$contract->id.':'.$month['key']] ?? null;
        }

        $used = $contract->usedHours();

        return [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'client_name' => $contract->clientService?->client?->displayName() ?? 'Client',
            'therapist_name' => $this->therapistName($contract),
            'funding_code' => $contract->funding_code,
            'period_start' => $contract->period_start?->toDateString(),
            'period_end' => $contract->period_end?->toDateString(),
            'allotted_hours' => round((float) $contract->allotted_hours, 2),
            'used_hours' => $used,
            'remaining_hours' => round((float) $contract->allotted_hours - $used, 2),
            'months' => $cells,
        ];
    }

    /**
     * Who the contract authorizes, by the snapshot taken when it was issued
     * rather than the availed service's current assignment — a reassignment
     * must not rewrite who delivered last month's hours.
     */
    private function therapistName(ServiceContract $contract): string
    {
        $therapist = $contract->therapist;

        if ($therapist === null) {
            return 'Unassigned';
        }

        return trim("{$therapist->first_name} {$therapist->last_name}") ?: 'Unassigned';
    }

    /**
     * @param  array<int, ContractRow>  $rows
     */
    private function sum(array $rows, string $key): float
    {
        return round(array_sum(array_column($rows, $key)), 2);
    }
}
