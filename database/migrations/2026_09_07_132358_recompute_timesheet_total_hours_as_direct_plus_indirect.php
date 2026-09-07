<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Total Hours" on a time sheet is direct plus indirect aide-support hours.
 * Respite and community-support hours keep their own column totals but no
 * longer roll into the grand total, so every stored total is recomputed to
 * match what the form and the PDF now print.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('timesheets')->update([
            'total_hours' => DB::raw('ROUND(total_bda_direct + total_bda_indirect, 2)'),
        ]);
    }

    public function down(): void
    {
        DB::table('timesheets')->update([
            'total_hours' => DB::raw('ROUND(total_hourly_respite + total_community_support + total_bda_direct + total_bda_indirect, 2)'),
        ]);
    }
};
