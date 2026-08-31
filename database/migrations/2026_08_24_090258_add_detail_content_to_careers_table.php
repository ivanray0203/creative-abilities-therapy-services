<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job postings grew a full narrative — the pitch, the role summary, who you
 * collaborate with, what CATS offers, FSCD participation, and the contractor
 * terms. The flat columns already cover the plain lists (responsibilities,
 * qualifications, skills), so the remaining prose lives in `detail` rather
 * than one column per section.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->json('detail')->nullable()->after('required_documents');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->dropColumn(['detail', 'sort_order']);
        });
    }
};
