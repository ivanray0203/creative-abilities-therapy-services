<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A family signing a child up for a program from the public site. Kept
 * separate from `intakes`: this is a booking onto a group offering, not a
 * referral into the clinic's one-to-one caseload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('participant_first_name');
            $table->string('participant_last_name');
            $table->date('participant_date_of_birth')->nullable();
            $table->string('parent_name');
            $table->string('parent_email');
            $table->string('parent_phone');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'waitlisted', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_registrations');
    }
};
