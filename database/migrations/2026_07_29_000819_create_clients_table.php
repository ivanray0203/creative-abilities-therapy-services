<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_intake_id')->nullable()->unique()->constrained('intakes')->nullOnDelete();
            $table->foreignId('primary_therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assigned_at')->nullable();
            $table->timestamp('approved_date')->useCurrent();
            $table->json('clinical_notes')->nullable();
            $table->text('active_services')->nullable();
            $table->json('allergies')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('signed_date')->nullable();
            $table->json('timeline')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'inactive', 'archive'])->default('active');
            $table->json('consents')->nullable();
            $table->json('service_availed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
