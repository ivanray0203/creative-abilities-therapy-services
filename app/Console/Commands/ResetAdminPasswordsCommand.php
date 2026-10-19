<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Resets the password of every administrator account `admins:create` set up
 * to the shared `PASSWORD` below, so the clinic can hand the administrators
 * one known password instead of the random one in their invite email.
 *
 * The accounts come from `CreateAdminAccountsCommand::ACCOUNTS`; an email
 * with no account yet is skipped, which makes the command safe to re-run.
 */
#[Signature('admins:reset-password
    {--dry-run : Show whose password would be reset without changing anything}')]
#[Description('Reset the administrator accounts\' passwords to the shared default')]
class ResetAdminPasswordsCommand extends Command
{
    public const PASSWORD = 'Cats123*';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $reset = 0;
        $missing = 0;

        foreach (CreateAdminAccountsCommand::ACCOUNTS as $account) {
            $email = Str::lower(trim($account['email']));
            $label = "{$account['first_name']} {$account['last_name']} <{$email}>";
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $this->line("Skipped {$label}: no account.");
                $missing++;

                continue;
            }

            if (! $dryRun) {
                $this->resetPassword($user);
            }

            $this->info(($dryRun ? 'Would reset' : 'Reset')." password for {$label}.");
            $reset++;
        }

        $verb = $dryRun ? 'Would reset' : 'Reset';
        $this->newLine();
        $this->info("{$verb} {$reset}, skipped {$missing} without an account.");

        return self::SUCCESS;
    }

    private function resetPassword(User $user): void
    {
        $user->forceFill(['password' => self::PASSWORD])->save();

        AuditLogger::log('Reset admin password', 'Users', "Reset password of admin account {$user->email} via admins:reset-password");
    }
}
