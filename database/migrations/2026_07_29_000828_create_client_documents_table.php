<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')->nullable()->constrained('intakes')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('doc_type')->nullable();
            $table->string('title', 200)->nullable();
            $table->enum('upload_origin', ['admin', 'therapist', 'client'])->default('admin');
            $table->string('drive_file_id', 500)->nullable();
            $table->string('drive_file_url')->nullable();
            $table->string('drive_web_view')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
