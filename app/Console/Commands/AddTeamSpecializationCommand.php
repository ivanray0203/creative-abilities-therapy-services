<?php

namespace App\Console\Commands;

use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Adds one or more specializations to a team member, found by their login
 * email — for records the Team edit form can't save yet because other
 * required details are still missing.
 *
 * Specializations are matched against service names (that is how a service
 * is handed to a therapist), so only an existing service's exact name is
 * accepted. Existing specializations are kept and duplicates skipped, which
 * makes the command safe to re-run.
 */
#[Signature('team:add-specialization
    {email : The team member\'s login email}
    {specialization* : One or more service names, e.g. "Behavioural Aide Services"}
    {--dry-run : Show what would change without writing anything}')]
#[Description('Add specializations to a team member by email')]
class AddTeamSpecializationCommand extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user has the email {$email}.");

            return self::FAILURE;
        }

        $teamMember = $user->teamMember()->first();

        if ($teamMember === null) {
            $this->error("{$user->full_name} has no team member profile to add specializations to.");

            return self::FAILURE;
        }

        /** @var array<int, string> $requested */
        $requested = array_values(array_unique(array_map('trim', (array) $this->argument('specialization'))));
        $serviceNames = ServiceOffering::query()->pluck('name')->all();
        $unknown = array_values(array_diff($requested, $serviceNames));

        if ($unknown !== []) {
            $this->error('Not a service name: '.implode(', ', $unknown).'.');
            $this->line('Valid names: '.implode(', ', $serviceNames).'.');

            return self::FAILURE;
        }

        $current = $teamMember->specializations ?? [];
        $added = array_values(array_diff($requested, $current));

        if ($added === []) {
            $this->info("{$user->full_name} already has: ".implode(', ', $requested).'.');

            return self::SUCCESS;
        }

        $updated = [...$current, ...$added];

        if ($this->option('dry-run')) {
            $this->info("Would add to {$user->full_name}: ".implode(', ', $added).'.');
            $this->line('Specializations would be: '.implode(', ', $updated).'.');

            return self::SUCCESS;
        }

        $teamMember->update(['specializations' => $updated]);

        AuditLogger::log('Added specializations', 'Users', 'Added '.implode(', ', $added)." to {$user->email} via team:add-specialization");

        $this->info("Added to {$user->full_name}: ".implode(', ', $added).'.');
        $this->line('Specializations are now: '.implode(', ', $updated).'.');

        return self::SUCCESS;
    }
}
