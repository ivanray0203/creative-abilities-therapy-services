<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the therapy consent and family service agreement the parent signs
 * once every service on the intake is approved. The signed copy itself is a
 * ClientDocument; these columns only say where the client is in the flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->timestamp('consent_sent_at')->nullable()->after('signed_date');
            $table->timestamp('consent_signed_at')->nullable()->after('consent_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn(['consent_sent_at', 'consent_signed_at']);
        });
    }
};
