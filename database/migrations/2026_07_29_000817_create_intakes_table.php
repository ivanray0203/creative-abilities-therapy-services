<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('completed')->default(false);

            // Child
            $table->string('child_first_name', 100)->nullable();
            $table->string('child_middle_name', 100)->nullable();
            $table->string('child_last_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedInteger('age')->default(0);
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('status')->default('pending');

            // Address
            $table->string('street_address')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();

            $table->string('grade_level')->nullable();
            $table->string('school_name')->nullable();
            $table->json('services_needed')->nullable();
            $table->boolean('currently_receiving_services')->default(false);
            $table->json('diagnosis')->nullable();
            $table->boolean('has_medical_conditions')->default(false);
            $table->string('languages_spoken_at_home')->nullable();
            $table->boolean('require_interpreter')->default(false);
            $table->string('funding_source')->nullable();
            $table->json('available_days')->nullable();
            $table->json('preferred_times')->nullable();

            // Primary parent
            $table->string('primary_parent_name')->nullable();
            $table->string('primary_parent_phone')->nullable();
            $table->string('primary_parent_email')->nullable();
            $table->string('primary_relationship_to_child')->nullable();
            $table->string('primary_contact_method')->nullable();

            // Secondary parent
            $table->string('secondary_parent_name')->nullable();
            $table->string('secondary_parent_phone')->nullable();
            $table->string('secondary_parent_email')->nullable();
            $table->string('secondary_relationship_to_child')->nullable();
            $table->string('secondary_contact_method')->nullable();

            $table->text('additional_information')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->boolean('approved_as_client')->default(false);
            $table->uuid('linked_client_id')->nullable();

            $table->string('medical_conditions')->nullable();
            $table->string('interpreter_needed')->nullable();
            $table->string('receiving_services_desc')->nullable();
            $table->string('admin_addition_informations')->nullable();
            $table->string('theraphy_goals')->nullable();
            $table->string('referral_source')->nullable();
            $table->string('funding_number')->nullable();
            $table->string('annual_funding')->nullable();

            $table->json('notes')->nullable();
            $table->json('timeline')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            $table->json('funding_source_info')->nullable();

            $table->foreignId('assigned_therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assigned_at')->nullable();
            $table->string('reference_number', 50)->unique()->nullable();
            $table->json('consents')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intakes');
    }
};
