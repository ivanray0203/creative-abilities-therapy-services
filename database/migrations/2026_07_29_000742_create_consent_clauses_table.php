<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('consent_documents')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->text('text_template');
            $table->timestamps();

            $table->unique(['document_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_clauses');
    }
};
