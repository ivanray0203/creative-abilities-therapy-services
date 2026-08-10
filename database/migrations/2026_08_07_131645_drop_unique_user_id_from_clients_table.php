<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 17 — a `Client` represents one child, not one parent, so a parent
 * with several children needs several client rows. The unique index on
 * `user_id` made that impossible at the database level.
 *
 * `original_intake_id` remains unique, which is the constraint that actually
 * expresses one-client-per-child.
 *
 * The foreign key on `user_id` depends on this index, so it is dropped and
 * re-added around the swap; a plain index takes the unique index's place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Only reversible while every parent still has at most one client — once
     * a second child is promoted, restoring the unique index will fail.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
