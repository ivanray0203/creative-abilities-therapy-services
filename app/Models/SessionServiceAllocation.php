<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One session's draw against one availed service: the hours it accounted for,
 * and the contract those hours came out of.
 *
 * The link row itself is older than Phase 20 — a visit has always been able
 * to cover several of a child's services. Giving it numbers is what turned it
 * into the hours ledger, and the balance on every contract is summed from
 * these rows rather than cached anywhere.
 *
 * Modelled as a real pivot class rather than a bare `withPivot` list so
 * `hours` comes back cast, and so the two extra columns are declared
 * somewhere instead of being implied at four call sites.
 *
 * @property string $hours
 * @property int|null $service_contract_id
 * @property int $client_service_id
 * @property int $schedule_session_id
 */
class SessionServiceAllocation extends Pivot
{
    protected $table = 'client_service_schedule_session';

    /** The table carries its own `id`, unlike a plain many-to-many pivot. */
    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
        ];
    }
}
