<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates INT-{year}-{seq} / APP-{year}-{seq} / INV-{year}-{seq} /
 * BIL-{year}-{seq} / PRG-{year}-{seq} / EXP-{year}-{seq} reference numbers.
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

    public function billingItem(): string
    {
        return $this->next('billing_items', 'BIL', 'billing_number');
    }

    public function programRegistration(): string
    {
        return $this->next('program_registrations', 'PRG');
    }

    public function expense(): string
    {
        return $this->next('expenses', 'EXP');
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
