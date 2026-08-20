<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Group programmes families sign up for directly — camps, social groups,
 * parent workshops — as opposed to the one-to-one therapy an intake leads to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->text('summary');
            $table->text('description');
            $table->string('age_range')->nullable();
            $table->string('schedule')->nullable();
            $table->string('location')->nullable();
            $table->json('highlights')->nullable();
            /** Null when the programme takes an open number of participants. */
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->date('registration_closes_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
