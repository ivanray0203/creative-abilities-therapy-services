<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

/**
 * Grants every existing user the Spatie role matching their `users.role`
 * column, for accounts created before roles moved to spatie/laravel-permission.
 *
 * Access checks read the Spatie roles, so an account that has not been
 * through this command cannot reach its portal. Accounts created afterwards
 * are granted their role by the User model itself.
 *
 * A user who already holds their role is left alone, and no role is ever
 * taken away, which makes the command safe to re-run.
 */
#[Signature('roles:transfer
    {--dry-run : Show what would be transferred without writing anything}')]
#[Description('Grant each user the Spatie role matching their users.role column')]
class TransferRolesCommand extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        /** @var array<string, int> $transferred */
        $transferred = [];
        $alreadyHeld = 0;

        User::query()->with('roles')->chunkById(200, function (Collection $users) use ($dryRun, &$transferred, &$alreadyHeld): void {
            foreach ($users as $user) {
                if ($user->roles->contains('name', $user->role)) {
                    $alreadyHeld++;

                    continue;
                }

                if (! $dryRun) {
                    $user->assignRole(Role::findOrCreate($user->role));
                }

                $transferred[$user->role] = ($transferred[$user->role] ?? 0) + 1;
            }
        });

        ksort($transferred);
        $total = array_sum($transferred);
        $verb = $dryRun ? 'Would transfer' : 'Transferred';

        foreach ($transferred as $role => $count) {
            $this->line("{$verb} {$count} {$role} user(s).");
        }

        if (! $dryRun && $total > 0) {
            AuditLogger::log('Transferred roles', 'Users', "Granted Spatie roles to {$total} user(s) via roles:transfer");
        }

        $this->newLine();
        $this->info("{$verb} {$total} user(s), {$alreadyHeld} already held their role.");

        return self::SUCCESS;
    }
}
