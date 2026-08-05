<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('position', 200)->nullable();
            $table->string('resident_status', 200)->nullable();
            $table->enum('department', ['clinical_services', 'administration', 'finance', 'operations'])->default('clinical_services');
            $table->enum('employment_status', ['active', 'inactive', 'on_leave', 'terminated', 'archived'])->default('active');
            $table->date('hire_date')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->unsignedInteger('maximum_caseload')->default(0);
            $table->json('credentials')->nullable();
            $table->json('specializations')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->boolean('can_access_finance')->default(false);
            $table->boolean('can_manage_team')->default(false);
            $table->boolean('can_manage_clients')->default(false);
            $table->text('additional_notes')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('photo')->nullable();
            $table->json('client')->nullable();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('office_phone')->nullable();
            $table->text('street_address')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code')->nullable();
            $table->json('availability')->nullable();
            $table->json('documents')->nullable();
            $table->string('secondary_email')->nullable();
            $table->date('birthdate')->nullable();
            $table->json('required_documents')->nullable();
            $table->string('sin_number')->nullable();
            $table->string('license_number')->nullable();
            $table->string('years_of_experience', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
