<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_offerings')->cascadeOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->date('start_date')->nullable();
            $table->string('funding_source')->nullable();
            $table->unsignedInteger('no_sessions')->default(0);
            $table->text('goals')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_services');
    }
};
