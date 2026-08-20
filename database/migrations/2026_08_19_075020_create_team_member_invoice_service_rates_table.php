<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-therapist overrides of the `invoice_services` rate card.
 *
 * The rate card holds the clinic's published rate for every billable line;
 * a row here says one team member bills a line at their own rate instead.
 * A null column falls back to the card, so a therapist can be given a custom
 * private rate while still billing FSCD at the standard rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_invoice_service_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_service_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate_fscd', 10, 2)->nullable();
            $table->decimal('rate_private', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['team_member_id', 'invoice_service_id'], 'team_member_invoice_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_invoice_service_rates');
    }
};
