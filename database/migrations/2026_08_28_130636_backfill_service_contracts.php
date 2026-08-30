<?php

use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 20 — gives every availed service that predates contracts one that is
 * generous enough to keep it working.
 *
 * Without this, the new gate would take effect the moment it deploys and
 * every therapist would find their whole caseload unbookable until admins had
 * worked through it by hand.
 *
 * Two passes: first the pivot rows are filled with the hours their sessions
 * booked, then each service gets a contract sized to cover what it has
 * already delivered, rounded up.
 *
 * The arithmetic runs in PHP rather than SQL. The suite runs on SQLite
 * in-memory while the app runs on MySQL, and the two disagree about date
 * functions and integer division — the same reason the `duration` migration
 * gives for doing its own conversion row by row.
 */
return new class extends Migration
{
    /** Nothing smaller is worth issuing, and it covers a normal month. */
    private const MINIMUM_HOURS = 40;

    public function up(): void
    {
        $this->backfillLedgerHours();
        $this->issueContracts();
    }

    /**
     * A session's booked minutes, split evenly across the availed services it
     * covers, with the remainder on the last so the parts add up to the whole.
     */
    private function backfillLedgerHours(): void
    {
        DB::table('client_service_schedule_session')
            ->orderBy('schedule_session_id')
            ->orderBy('id')
            ->get()
            ->groupBy('schedule_session_id')
            ->each(function ($links, $sessionId): void {
                $minutes = (int) (DB::table('schedule_sessions')->where('id', $sessionId)->value('duration') ?? 0);

                $total = round($minutes / 60, 2);
                $count = $links->count();
                $share = round($total / $count, 2);

                foreach ($links->values() as $index => $link) {
                    DB::table('client_service_schedule_session')
                        ->where('id', $link->id)
                        ->update([
                            'hours' => $index === $count - 1
                                ? round($total - $share * ($count - 1), 2)
                                : $share,
                        ]);
                }
            });
    }

    /**
     * One contract per availed service, wide enough in both hours and dates
     * that nothing already booked falls outside it.
     */
    private function issueContracts(): void
    {
        $sequence = 0;
        $year = now()->year;

        DB::table('client_services')
            ->orderBy('id')
            ->chunk(200, function ($services) use (&$sequence, $year): void {
                foreach ($services as $service) {
                    $drawn = $this->drawnHours($service->id);
                    $sessionDates = $this->sessionDateRange($service->id);

                    $start = $this->startDate($service, $sessionDates['first']);
                    $end = $start->copy()->addYear();

                    // A session already sitting past the year mark would be
                    // stranded outside its own contract on the first edit.
                    if ($sessionDates['last'] !== null && $sessionDates['last']->greaterThan($end)) {
                        $end = $sessionDates['last']->copy()->endOfMonth();
                    }

                    $sequence++;

                    $contractId = DB::table('service_contracts')->insertGetId([
                        'contract_number' => sprintf('CON-%d-%s', $year, str_pad((string) $sequence, 3, '0', STR_PAD_LEFT)),
                        'client_service_id' => $service->id,
                        'therapist_id' => $service->therapist_id,
                        'issued_by_id' => null,
                        'allotted_hours' => $this->allotment($drawn),
                        'period_start' => $start->toDateString(),
                        'period_end' => $end->toDateString(),
                        'status' => ServiceContract::STATUS_ACTIVE,
                        'notes' => 'Backfilled at contract rollout.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('client_service_schedule_session')
                        ->where('client_service_id', $service->id)
                        ->update(['service_contract_id' => $contractId]);
                }
            });
    }

    /** Hours the service's live sessions have already taken. */
    private function drawnHours(int $clientServiceId): float
    {
        return round((float) DB::table('client_service_schedule_session as ledger')
            ->join('schedule_sessions as drawn_session', 'drawn_session.id', '=', 'ledger.schedule_session_id')
            ->where('ledger.client_service_id', $clientServiceId)
            ->whereNotIn('drawn_session.status', ScheduleSession::HOURS_RELEASING_STATUSES)
            ->sum('ledger.hours'), 2);
    }

    /**
     * @return array{first: Carbon|null, last: Carbon|null}
     */
    private function sessionDateRange(int $clientServiceId): array
    {
        $bounds = DB::table('client_service_schedule_session as ledger')
            ->join('schedule_sessions as drawn_session', 'drawn_session.id', '=', 'ledger.schedule_session_id')
            ->where('ledger.client_service_id', $clientServiceId)
            ->selectRaw('MIN(drawn_session.scheduled_start) as first_at, MAX(drawn_session.scheduled_start) as last_at')
            ->first();

        return [
            'first' => $bounds?->first_at !== null ? Carbon::parse($bounds->first_at)->startOfDay() : null,
            'last' => $bounds?->last_at !== null ? Carbon::parse($bounds->last_at)->startOfDay() : null,
        ];
    }

    /**
     * The service's own start date, pulled back if a session was somehow
     * booked before it.
     */
    private function startDate(stdClass $service, ?Carbon $firstSession): Carbon
    {
        $start = $service->start_date !== null
            ? Carbon::parse($service->start_date)->startOfDay()
            : Carbon::parse($service->created_at)->startOfDay();

        if ($firstSession !== null && $firstSession->lessThan($start)) {
            return $firstSession;
        }

        return $start;
    }

    /** Round the delivered hours up to the next ten, never below the floor. */
    private function allotment(float $drawn): float
    {
        return max(self::MINIMUM_HOURS, (float) (ceil($drawn / 10) * 10));
    }

    /**
     * The contracts and the ledger figures are both derived, so dropping them
     * loses nothing that cannot be recomputed. The old one-session-per-service
     * rule is not restored: the scope it lived in no longer exists.
     */
    public function down(): void
    {
        DB::table('client_service_schedule_session')->update([
            'service_contract_id' => null,
            'hours' => 0,
        ]);

        DB::table('service_contracts')->where('notes', 'Backfilled at contract rollout.')->delete();
    }
};
