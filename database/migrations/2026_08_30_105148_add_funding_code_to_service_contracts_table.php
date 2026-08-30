<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 21 — which funding stream paid for a contract's hours.
 *
 * The hour-tracking sheet the clinic keeps carries this beside every child's
 * contract: `SS`, `BDS`, `split`, `BDS/split`, `BD` or `Private`. It changes
 * nothing about how hours are drawn — it says who is being billed for them,
 * and the funder is the first thing anyone reading the sheet looks for.
 *
 * Nullable, because contracts issued before this existed have no answer and
 * a guess would be worse than a blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_contracts', function (Blueprint $table) {
            $table->string('funding_code', 20)->nullable()->after('allotted_hours');
        });
    }

    public function down(): void
    {
        Schema::table('service_contracts', function (Blueprint $table) {
            $table->dropColumn('funding_code');
        });
    }
};
