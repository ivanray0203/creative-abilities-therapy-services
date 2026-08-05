<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('intake_therapist_approvals', function (Blueprint $table) {
            $table->dropForeign(['intake_id']);
            $table->dropUnique(['intake_id']);
            $table->string('service')->nullable()->after('intake_id');
            $table->foreign('intake_id')->references('id')->on('intakes')->cascadeOnDelete();
            $table->unique(['intake_id', 'service']);
        });

        Schema::table('intake_therapist_approval_histories', function (Blueprint $table) {
            $table->string('service')->nullable()->after('intake_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intake_therapist_approvals', function (Blueprint $table) {
            $table->dropForeign(['intake_id']);
            $table->dropUnique(['intake_id', 'service']);
            $table->dropColumn('service');
            $table->foreign('intake_id')->references('id')->on('intakes')->cascadeOnDelete();
            $table->unique(['intake_id']);
        });

        Schema::table('intake_therapist_approval_histories', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
