<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which side of the ledger a billing item is on used to be read off the
 * issuer's role: raised by an admin meant the clinic's own billing. Once a
 * user can be both an admin and a therapist that no longer says which hat
 * they wore, so the side is recorded on the line itself — the same
 * `billed_by` invoices already carry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_items', function (Blueprint $table) {
            $table->string('billed_by', 20)->default('therapist')->after('issued_by_id');
        });

        DB::table('billing_items')
            ->whereIn('issued_by_id', DB::table('users')->where('role', 'admin')->select('id'))
            ->update(['billed_by' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('billing_items', function (Blueprint $table) {
            $table->dropColumn('billed_by');
        });
    }
};
