<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The three portal roles have to exist before any user does: role-scoped
 * queries (`User::role('therapist')`) fail outright on a role that was never
 * created, which a fresh install with no therapists yet would otherwise hit.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.roles');

        foreach (User::ROLES as $role) {
            DB::table($table)->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table(config('permission.table_names.roles'))
            ->whereIn('name', User::ROLES)
            ->where('guard_name', 'web')
            ->delete();
    }
};
