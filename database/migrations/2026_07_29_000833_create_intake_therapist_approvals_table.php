<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_therapist_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')->unique()->constrained('intakes')->cascadeOnDelete();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'reassign'])->default('pending');
            $table->text('notes')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_therapist_approvals');
    }
};
