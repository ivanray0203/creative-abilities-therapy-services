<?php

namespace App\Console\Commands;

use App\Models\Career;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Sets a team member's position (job title), found by their login email.
 *
 * The position decides what the therapist portal shows: a title ending in
 * "Aide" gets Hours and Timesheets instead of Billing and Invoices (see
 * TeamMember::isAide()). So a typo is not harmless, and only the titles
 * listed under Admin → Positions are accepted unless --allow-other is given.
 */
#[Signature('team:set-position
    {email : The team member\'s login email}
    {position : The job title, e.g. "Behavioural & Developmental Aide"}
    {--allow-other : Accept a title that is not listed under Admin → Positions}
    {--dry-run : Show what would change without writing anything}')]
#[Description('Set a team member\'s position by email')]
class SetTeamPositionCommand extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $position = trim((string) $this->argument('position'));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user has the email {$email}.");

            return self::FAILURE;
        }

        $teamMember = $user->teamMember()->first();

        if ($teamMember === null) {
            $this->error("{$user->full_name} has no team member profile to set a position on.");

            return self::FAILURE;
        }

        if ($position === '' || mb_strlen($position) > 255) {
            $this->error('The position must be between 1 and 255 characters.');

            return self::FAILURE;
        }

        $knownPositions = Career::query()->orderBy('position')->pluck('position')->unique()->values()->all();

        if (! in_array($position, $knownPositions, true) && ! $this->option('allow-other')) {
            $this->error("\"{$position}\" is not a position listed under Admin → Positions.");
            $this->line('Listed positions: '.implode(', ', $knownPositions).'.');
            $this->line('Pass --allow-other to use it anyway.');

            return self::FAILURE;
        }

        $previous = $teamMember->position;

        if ($previous === $position) {
            $this->info("{$user->full_name} already has the position {$position}.");

            return self::SUCCESS;
        }

        $teamMember->position = $position;
        $portal = $teamMember->isAide()
            ? 'an aide: Hours and Timesheets in the therapist portal'
            : 'not an aide: Billing and Invoices in the therapist portal';

        if ($this->option('dry-run')) {
            $this->info("Would change {$user->full_name}'s position from ".($previous ?: '(none)')." to {$position}.");
            $this->line("They would be {$portal}.");

            return self::SUCCESS;
        }

        $teamMember->save();

        AuditLogger::log('Set position', 'Users', "Set {$user->email}'s position to {$position} via team:set-position");

        $this->info("Changed {$user->full_name}'s position from ".($previous ?: '(none)')." to {$position}.");
        $this->line("They are now {$portal}.");

        return self::SUCCESS;
    }
}
