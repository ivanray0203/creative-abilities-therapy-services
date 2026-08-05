<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->decimal('base_price', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('benefits')->nullable();
            $table->json('offerings')->nullable();
            $table->json('approaches')->nullable();
            $table->json('outcomes')->nullable();
            $table->json('area_of_focus')->nullable();
            $table->string('description_highlight')->nullable();
            $table->json('tags')->nullable();
            $table->string('ages')->nullable();
            $table->text('signs_to_look_for')->nullable();
            $table->text('conditions')->nullable();
            $table->string('frequency')->nullable();
            $table->string('location')->nullable();
            $table->string('main_tag')->nullable();
            $table->string('duration')->nullable();
            $table->text('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
