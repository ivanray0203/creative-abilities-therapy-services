<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')->constrained('intakes')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('file')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('drive_file_url')->nullable();
            $table->string('drive_web_view')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_documents');
    }
};
