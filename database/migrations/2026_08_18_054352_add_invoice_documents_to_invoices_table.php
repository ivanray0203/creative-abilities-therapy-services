<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each invoice PDF lives on Google Drive.
 *
 * `not_signed_invoice` is written when the admin raises the invoice
 * (Invoice/not-signed), `signed_invoice` when the parent signs and returns it
 * (Invoice/signed). A null `signed_invoice` is what "still awaiting the
 * parent's signature" means.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('not_signed_invoice')->nullable()->after('notes');
            $table->string('signed_invoice')->nullable()->after('not_signed_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['not_signed_invoice', 'signed_invoice']);
        });
    }
};
