<?php

namespace App\Console\Commands;

use App\Mail\StaffInviteMail;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Creates the clinic's administrator accounts and emails each new one their
 * login email and temporary password, the same invite `therapists:create`
 * sends.
 *
 * The accounts are the static `ACCOUNTS` list below, so the command needs no
 * input. The first role listed is the primary one — the portal the account
 * lands on — and an account that is also a therapist gets a team-member
 * record so the therapist portal has a profile to show.
 *
 * An email that already belongs to a user keeps its password and is not
 * emailed; it is only granted whichever listed roles it is missing. That
 * makes the command safe to re-run.
 */
#[Signature('admins:create
    {--dry-run : Show what would be created without writing or emailing}')]
#[Description('Create the administrator accounts and email each their temporary password')]
class CreateAdminAccountsCommand extends Command
{
    /**
     * @var array<int, array{first_name: string, last_name: string, email: string, roles: array<int, string>, position?: string}>
     */
    public const ACCOUNTS = [
        [
            'first_name' => 'Bryan',
            'last_name' => 'Lerit',
            'email' => 'bryan.lerit@creativeabilitiestherapyservices.ca',
            'roles' => ['admin'],
        ],
        [
            'first_name' => 'Bernard',
            'last_name' => 'Lerit',
            'email' => 'bernard.lerit@creativeabilitiestherapyservices.ca',
            'roles' => ['admin'],
        ],
        [
            'first_name' => 'Mary Ann',
            'last_name' => 'Lerit',
            'email' => 'maryann.lerit@creativeabilitiestherapyservices.ca',
            'roles' => ['admin', 'therapist'],
            'position' => 'Occupational Therapist (OT)',
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $existing = 0;

        foreach ($this->accounts() as $account) {
            $email = Str::lower(trim($account['email']));
            $label = "{$account['first_name']} {$account['last_name']} <{$email}>";
            $roles = implode(' + ', $account['roles']);
            $user = User::query()->where('email', $email)->first();

            if ($user !== null) {
                $missing = array_values(array_diff($account['roles'], $user->getRoleNames()->all()));

                if (! $dryRun) {
                    $this->grantRoles($user, $account, $missing);
                }

                $this->line("Skipped {$label}: already has an account".($missing === [] ? '.' : ', '.($dryRun ? 'would grant ' : 'granted ').implode(' + ', $missing).'.'));
                $existing++;

                continue;
            }

            if (! $dryRun) {
                $this->createAccount($account, $email);
            }

            $this->info(($dryRun ? 'Would create' : 'Created')." {$label} as {$roles}.");
            $created++;
        }

        $verb = $dryRun ? 'Would create' : 'Created';
        $this->newLine();
        $this->info("{$verb} {$created}, skipped {$existing} existing.");

        return self::SUCCESS;
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, roles: array<int, string>, position?: string}  $account
     */
    private function createAccount(array $account, string $email): void
    {
        $password = Str::random(10);

        DB::transaction(function () use ($account, $email, $password): void {
            $user = User::query()->create([
                'email' => $email,
                'password' => $password,
                'role' => $account['roles'][0],
                'first_name' => $account['first_name'],
                'last_name' => $account['last_name'],
                'is_active' => true,
            ]);

            $this->grantRoles($user, $account, array_slice($account['roles'], 1));

            AuditLogger::log('Created admin user', 'Users', "Created admin account {$user->email} via admins:create");
        });

        Mail::to($email)->send(new StaffInviteMail($account['first_name'], $email, $password));
    }

    /**
     * Grants the given roles, and gives an account holding the therapist
     * role the team-member record the therapist portal reads.
     *
     * @param  array{first_name: string, last_name: string, email: string, roles: array<int, string>, position?: string}  $account
     * @param  array<int, string>  $roles
     */
    private function grantRoles(User $user, array $account, array $roles): void
    {
        if ($roles !== []) {
            $user->assignRole($roles);
        }

        if (in_array('therapist', $account['roles'], true)) {
            TeamMember::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'position' => $account['position'] ?? null,
                    'title' => $account['position'] ?? null,
                    'department' => 'clinical_services',
                    'employment_status' => 'active',
                    'can_manage_clients' => true,
                ],
            );
        }
    }

    /**
     * The accounts to process; a seam so tests can run against their own list.
     *
     * @return array<int, array{first_name: string, last_name: string, email: string, roles: array<int, string>, position?: string}>
     */
    protected function accounts(): array
    {
        return self::ACCOUNTS;
    }
}
