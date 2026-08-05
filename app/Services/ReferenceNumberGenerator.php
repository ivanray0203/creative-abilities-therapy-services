<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates INT-{year}-{seq} / APP-{year}-{seq} / INV-{year}-{seq} reference numbers.
 *
 * The Django reference derives {seq} from a per-year row COUNT, which
 * races under concurrent submissions. This locks the max existing
 * sequence for the year inside a transaction instead.
 */
class ReferenceNumberGenerator
{
    public function intake(): string
    {
        return $this->next('intakes', 'INT');
    }

    public function application(): string
    {
        return $this->next('applications', 'APP');
    }

    public function invoice(): string
    {
        return $this->next('invoices', 'INV', 'invoice_id');
    }

    private function next(string $table, string $prefix, string $column = 'reference_number'): string
    {
        $year = now()->year;
        $yearPrefix = "{$prefix}-{$year}-";

        return DB::transaction(function () use ($table, $column, $yearPrefix) {
            $last = DB::table($table)
                ->where($column, 'like', "{$yearPrefix}%")
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = is_string($last) ? ((int) substr($last, strlen($yearPrefix))) + 1 : 1;

            return $yearPrefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        });
    }
}
