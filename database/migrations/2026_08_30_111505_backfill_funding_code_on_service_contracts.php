<?php

use App\Models\ServiceContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 21 — fills in `funding_code` on the contracts that predate it, so the
 * hour-tracking sheet reads with a funder against each row instead of a dash.
 *
 * The funder was already recorded, just not on the contract: an availed
 * service carries its own `funding_source`, and behind that the child's intake
 * carries the one the family was approved under. Both use the intake's
 * vocabulary — `SS-FSCD`, `BDS-FSCD`, `Counselling-FSCD`, `private`,
 * `Insurance` — which `ServiceContract::fundingCodeFor()` translates into the
 * sheet's own shorthand.
 *
 * Two deliberate silences:
 *
 * - **`Insurance` is left blank.** The sheet has no code for it. Writing one
 *   would be inventing a category the office does not use, and a blank an
 *   admin fills in is better than a wrong value nobody notices.
 * - **`split` and `BDS/split` are never written.** A split contract is a
 *   judgement about how one child's hours are shared, recorded nowhere in the
 *   data. Only an admin can say so.
 *
 * The mapping runs in PHP rather than SQL: this is a lookup with a fallback
 * chain, and expressing it as a CASE across a join would be both unreadable
 * and split between MySQL and the SQLite the suite runs on.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_contracts')
            ->whereNull('funding_code')
            ->orderBy('id')
            ->chunkById(200, function ($contracts): void {
                foreach ($contracts as $contract) {
                    $code = $this->codeFor($contract->client_service_id);

                    if ($code === null) {
                        continue;
                    }

                    DB::table('service_contracts')
                        ->where('id', $contract->id)
                        ->update(['funding_code' => $code]);
                }
            });
    }

    /**
     * The availed service's own funding source first, the child's intake
     * second.
     *
     * The service-level value is the more specific of the two — a child can
     * be approved under one stream and avail a service funded by another —
     * so it wins where it is both set and recognised.
     */
    private function codeFor(int $clientServiceId): ?string
    {
        $service = DB::table('client_services')
            ->where('id', $clientServiceId)
            ->first(['funding_source', 'client_id']);

        if ($service === null) {
            return null;
        }

        $code = ServiceContract::fundingCodeFor($service->funding_source);

        if ($code !== null) {
            return $code;
        }

        $intakeSource = DB::table('clients')
            ->join('intakes', 'intakes.id', '=', 'clients.original_intake_id')
            ->where('clients.id', $service->client_id)
            ->value('intakes.funding_source');

        return ServiceContract::fundingCodeFor($intakeSource);
    }

    /**
     * Only what this migration could have written is cleared. A code an admin
     * typed by hand before rolling back is theirs, not ours — but the two are
     * indistinguishable by then, so `down()` clears every code and accepts
     * that a rollback loses hand-entered ones. They are re-derivable for every
     * value the mapping covers.
     */
    public function down(): void
    {
        DB::table('service_contracts')->update(['funding_code' => null]);
    }
};
