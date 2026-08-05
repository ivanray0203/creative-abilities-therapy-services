<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offerings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('type', ['general_service', 'specific_service', 'non_direct_service'])->default('general_service');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offerings');
    }
};
