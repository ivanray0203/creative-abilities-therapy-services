<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The aide's FSCD time sheet: every hour they logged for one child over a
 * chosen period, on one form signed by both the aide and the parent.
 *
 * The aide's side of the billing pipeline. Where a therapist's loose billing
 * lines are claimed by a generated invoice, an aide's loose hour entries are
 * claimed by a generated timesheet — but the form carries hours only, so
 * there are no rates, amounts or totals in money anywhere on it.
 *
 * `rows` is the frozen snapshot of the entries this form was generated from.
 * A signed timesheet is a record of what the parent agreed to, so the PDF
 * renders from the snapshot, never from the live entries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->string('timesheet_number', 100)->unique();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            /** The claimed entries as printed, shaped like {date, hourly_respite, community_support, bda_direct, bda_indirect}. */
            $table->json('rows')->nullable();

            $table->decimal('total_hourly_respite', 8, 2)->default(0);
            $table->decimal('total_community_support', 8, 2)->default(0);
            $table->decimal('total_bda_direct', 8, 2)->default(0);
            $table->decimal('total_bda_indirect', 8, 2)->default(0);
            $table->decimal('total_hours', 8, 2)->default(0);

            // PNG data URIs drawn on the signature pad. The aide signs as they
            // generate, so only the parent's is ever pending.
            $table->longText('aide_signature')->nullable();
            $table->longText('parent_signature')->nullable();
            $table->timestamp('aide_signed_at')->nullable();
            $table->timestamp('parent_signed_at')->nullable();

            $table->string('status', 30)->default('awaiting_client');

            // Drive web-view URLs, the same pair invoices keep.
            $table->string('not_signed_timesheet')->nullable();
            $table->string('signed_timesheet')->nullable();

            $table->json('timeline')->nullable();
            $table->timestamps();

            $table->index(['therapist_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
