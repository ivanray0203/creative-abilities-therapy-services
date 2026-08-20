<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "Service Provided" rate card an invoice line is picked from — the
 * billable activities (home visits, documentation, travel, mileage) and
 * their FSCD and private rates, as published in the clinic's rate sheet.
 *
 * Distinct from `service_offerings`, which is the coarse list of therapies
 * a client avails (Occupational Therapy, Physiotherapy); one offering bills
 * through many of these rate lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 150)->unique();
            $table->string('discipline', 40)->index();
            $table->decimal('rate_fscd', 10, 2)->nullable();
            $table->decimal('rate_private', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_services');
    }
};
