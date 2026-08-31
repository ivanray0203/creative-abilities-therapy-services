<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 20 — the session-to-availed-service pivot becomes the hours ledger.
 *
 * The link already exists and is already unique per (session, availed
 * service), so recording the draw here means one row per draw and nothing to
 * reconcile against a second table.
 *
 * `service_contract_id` is frozen at booking on purpose. Resolving the
 * contract afresh each time the balance is summed would let an August session
 * re-attach itself to September's contract, quietly rewriting the month that
 * had already been reported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_service_schedule_session', function (Blueprint $table) {
            $table->decimal('hours', 6, 2)->default(0)->after('client_service_id');
            $table->foreignId('service_contract_id')->nullable()->after('hours')
                ->constrained('service_contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_service_schedule_session', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_contract_id');
            $table->dropColumn('hours');
        });
    }
};
