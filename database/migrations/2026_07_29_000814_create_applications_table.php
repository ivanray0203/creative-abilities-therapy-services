<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('phone');
            $table->string('email');
            $table->string('street_address')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('position_applied')->nullable();
            $table->foreignId('position_id')->nullable()->constrained('careers')->nullOnDelete();
            $table->string('profession_status')->nullable();
            $table->date('preferred_start_date')->nullable();
            $table->boolean('is_working_with_other')->default(false);
            $table->string('resume', 500)->nullable();
            $table->string('cover_letter', 500)->nullable();
            $table->boolean('drivers_license')->default(false);
            $table->boolean('has_vehicle')->default(false);
            $table->string('lead_source')->nullable();
            $table->text('reason_for_applying')->nullable();
            $table->text('other_notes')->nullable();
            $table->enum('application_status', ['pending', 'reviewing', 'interview_scheduled', 'shortlisted', 'hired', 'declined'])->default('pending');
            $table->json('internal_notes')->nullable();
            $table->json('notes')->nullable();
            $table->text('experience')->nullable();
            $table->string('expected_salary')->nullable();
            $table->string('notice_availability', 100)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('interview_date')->nullable();
            $table->time('interview_time')->nullable();
            $table->string('interview_platform')->nullable();
            $table->text('education')->nullable();
            $table->json('skills')->nullable();
            $table->unsignedInteger('candidate_rating')->nullable();
            $table->boolean('hired')->default(false);
            $table->boolean('declined')->default(false);
            $table->json('availability')->nullable();
            $table->json('references')->nullable();
            $table->string('resident_status', 200)->nullable();
            $table->string('reference_number', 50)->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
