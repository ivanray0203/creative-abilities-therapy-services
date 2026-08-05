<?php

namespace Database\Seeders;

use App\Models\BillingAccount;
use App\Models\Client;
use App\Models\Intake;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One known-password account per role, for the login modal's "Quick Demo
 * Login" buttons (resources/js/components/login-modal.tsx). Idempotent —
 * safe to re-run.
 */
class DemoAccountsSeeder extends Seeder
{
    public const PASSWORD = 'Demo@12345';

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@cats.test'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ],
        );

        $therapistUser = User::query()->updateOrCreate(
            ['email' => 'therapist@cats.test'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Therapist',
                'role' => 'therapist',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ],
        );

        TeamMember::query()->updateOrCreate(
            ['user_id' => $therapistUser->id],
            [
                'position' => 'Occupational Therapist',
                'department' => 'clinical_services',
                'employment_status' => 'active',
                'hire_date' => now()->subYear()->toDateString(),
                'hourly_rate' => 65,
                'maximum_caseload' => 20,
                'credentials' => ['OTRL'],
                'specializations' => [],
                'can_access_finance' => false,
                'can_manage_team' => false,
                'can_manage_clients' => true,
                'phone' => '555-0100',
            ],
        );

        $clientUser = User::query()->updateOrCreate(
            ['email' => 'client@cats.test'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'role' => 'client',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ],
        );

        $client = Client::query()->where('user_id', $clientUser->id)->first();

        if (! $client) {
            $intake = Intake::factory()->create([
                'child_first_name' => 'Demo',
                'child_last_name' => 'Child',
                'primary_parent_name' => 'Demo Client',
                'primary_parent_email' => $clientUser->email,
                'status' => 'approved',
            ]);

            $client = Client::query()->create([
                'original_intake_id' => $intake->id,
                'user_id' => $clientUser->id,
                'primary_therapist_id' => $therapistUser->id,
                'assigned_therapist_id' => $therapistUser->id,
                'assigned_at' => now()->toDateString(),
                'status' => 'active',
            ]);

            BillingAccount::query()->firstOrCreate(['client_id' => $client->id]);
        }
    }
}
