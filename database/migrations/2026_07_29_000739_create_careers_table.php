<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('position');
            $table->string('location');
            $table->string('schedule');
            $table->string('contract');
            $table->string('rate');
            $table->text('short_description');
            $table->text('about_description');
            $table->json('responsibilities')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('skills')->nullable();
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('due_date')->nullable();
            $table->json('highlights')->nullable();
            $table->string('level')->nullable();
            $table->string('hours')->nullable();
            $table->json('required_documents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
