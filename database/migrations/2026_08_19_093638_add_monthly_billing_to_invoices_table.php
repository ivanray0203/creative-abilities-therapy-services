<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Therapist billing becomes monthly.
 *
 * A therapist's per-client bill now stays on their own side of the wall: it
 * is raised, listed, and left alone. At month end one monthly invoice rolls
 * every bill in that calendar month into a single statement addressed to the
 * clinic, and that is what the admin sees.
 *
 * `is_monthly` marks the rolled-up statement, `period_start`/`period_end`
 * the month it covers, and `monthly_invoice_id` points a client bill at the
 * statement that absorbed it — which is also how a bill is known to have
 * been billed already.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_monthly')->default(false)->after('billed_by');
            $table->date('period_start')->nullable()->after('is_monthly');
            $table->date('period_end')->nullable()->after('period_start');
            $table->foreignId('monthly_invoice_id')->nullable()->after('linked_therapist_invoice_id')
                ->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('monthly_invoice_id');
            $table->dropColumn(['is_monthly', 'period_start', 'period_end']);
        });
    }
};
