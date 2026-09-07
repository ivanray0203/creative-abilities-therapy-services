<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The real administrator accounts. Runs in every environment, including
 * production. Idempotent — safe to re-run.
 */
class ProductionAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@creativeabilitiestherapyservices.ca'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('CaTsInc123*'),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'bernard.lerit@creativeabilitiestherapyservices.ca'],
            [
                'first_name' => 'Bernard',
                'last_name' => 'Lerit',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('CaTsInc123'),
            ],
        );
    }
}
