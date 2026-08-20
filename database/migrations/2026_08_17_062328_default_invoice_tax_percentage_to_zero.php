<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices are no longer raised with GST, so the column default follows.
 *
 * Existing rows keep the rate they were billed at — only the default for new
 * rows changes, so an invoice already sent to a family still reconciles
 * against what it charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('tax_percentage', 5, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('tax_percentage', 5, 2)->default(5.00)->change();
        });
    }
};
