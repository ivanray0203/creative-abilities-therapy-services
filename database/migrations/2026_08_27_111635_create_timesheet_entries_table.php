<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One line of an aide's time sheet: the hours they gave one child on one
 * day, split across the four service columns the FSCD form prints.
 *
 * Entries stay loose until the aide generates a timesheet, at which point
 * that timesheet claims them by filling `timesheet_id` — the same shape as
 * billing items and invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('entry_date');

            // The four columns of the printed form. Hours are fractional:
            // 0.75 and 1.5 are real quantities.
            $table->decimal('hourly_respite_hours', 6, 2)->default(0);
            $table->decimal('community_support_hours', 6, 2)->default(0);
            $table->decimal('bda_direct_hours', 6, 2)->default(0);
            $table->decimal('bda_indirect_hours', 6, 2)->default(0);

            $table->text('notes')->nullable();
            $table->foreignId('timesheet_id')->nullable()->constrained('timesheets')->nullOnDelete();
            $table->timestamps();

            // One row per aide, per child, per day — logging the same day
            // twice corrects the first entry rather than adding a second.
            $table->unique(['therapist_id', 'client_id', 'entry_date']);

            // Generating a timesheet reads the still-unclaimed entries.
            $table->index(['therapist_id', 'timesheet_id']);
            $table->index(['client_id', 'timesheet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_entries');
    }
};
