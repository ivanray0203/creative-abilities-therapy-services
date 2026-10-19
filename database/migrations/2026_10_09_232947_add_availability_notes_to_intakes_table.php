<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-text scheduling details or restrictions the family adds beneath the
 * availability grid, such as school pick-up times or a recurring appointment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intakes', function (Blueprint $table): void {
            $table->text('availability_notes')->nullable()->after('availability_slots');
        });
    }

    public function down(): void
    {
        Schema::table('intakes', function (Blueprint $table): void {
            $table->dropColumn('availability_notes');
        });
    }
};
