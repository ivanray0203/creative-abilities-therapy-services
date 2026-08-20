<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One team member's override of a rate-card line.
 *
 * Exists as a pivot model so the rates cast to `decimal:2` the same way the
 * published rates on `invoice_services` do — the rates tab compares an
 * override against the standard rate, and "175" next to "142.14" reads as a
 * different kind of number.
 *
 * @property string|null $rate_fscd
 * @property string|null $rate_private
 */
class TeamMemberInvoiceServiceRate extends Pivot
{
    protected $table = 'team_member_invoice_service_rates';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'rate_fscd' => 'decimal:2',
            'rate_private' => 'decimal:2',
        ];
    }
}
