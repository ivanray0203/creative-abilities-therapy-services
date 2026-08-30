<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 20 — admin's authorization for one availed service: a pool of hours
 * over a fixed period, drawn down by the sessions booked against it.
 *
 * Before this, an assignment alone was licence to schedule, and
 * `ClientService::awaitingSchedule()` capped each availed service at a single
 * session for its lifetime. A contract replaces both rules: many sessions are
 * allowed, up to the hours admin authorized, inside the window they cover.
 *
 * The balance is deliberately *not* a column here. It is summed from
 * `client_service_schedule_session.hours` on read — see the sibling migration
 * and ServiceContract::usedHours(). A cached total would need adjusting on
 * every path that touches a session, and one missed path sells an hour twice
 * with nothing to show for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 100)->unique();
            $table->foreignId('client_service_id')->constrained('client_services')->cascadeOnDelete();

            /*
             * Snapshot of the therapist the contract authorizes. The availed
             * service's own `therapist_id` can be reassigned; the hours a
             * funder approved cannot follow that move silently.
             */
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('allotted_hours', 8, 2);
            $table->date('period_start');
            $table->date('period_end');

            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            /*
             * No unique key on (client_service_id, period): overlapping
             * contracts are a validation failure with a message naming the
             * clash, not a duplicate-key 500 in the admin's face.
             */
            $table->index(['client_service_id', 'status']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_contracts');
    }
};
