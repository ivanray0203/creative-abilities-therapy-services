<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['resolved', 'open', 'under_review'])->default('open');
            $table->enum('type', ['complaints', 'disputes'])->default('complaints');
            $table->text('admin_response')->nullable();
            $table->string('category')->nullable();
            $table->dateTime('resolve_at')->nullable();
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('schedule_sessions')->nullOnDelete();
            $table->enum('complained_by', ['client', 'therapist'])->nullable();
            $table->boolean('consent_given')->default(false);
            $table->text('consent_info')->nullable();
            $table->dateTime('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('file')->nullable();
            $table->string('drive_file_id', 500)->nullable();
            $table->string('drive_file_url')->nullable();
            $table->string('drive_web_view')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
