<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The public intake form (Phase 5) records consent acceptance for
     * guest submitters (no authenticated user), mirroring the reference's
     * AllowAny consent-acceptance behavior. user_id must be nullable to
     * support that.
     */
    public function up(): void
    {
        Schema::table('user_consent_acceptances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('user_consent_acceptances', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('user_consent_acceptances', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_consent_acceptances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('user_consent_acceptances', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('user_consent_acceptances', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
