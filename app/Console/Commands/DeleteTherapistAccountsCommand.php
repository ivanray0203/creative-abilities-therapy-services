<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deletes the therapist portal accounts of contractors who have left the
 * clinic — the rows highlighted yellow on the "Contractor Directory" sheet.
 *
 * The departed contractors are the static `DEPARTED` list below, keyed by the
 * login email `therapists:create` gave each of them, so the command needs no
 * input file. An email with no therapist account is skipped, which makes the
 * command safe to re-run.
 *
 * Deleting the user takes the team-member record with it, along with every
 * row that cascades from the therapist (see `CASCADED_RECORDS`); those are
 * counted and listed before anything is removed.
 */
#[Signature('therapists:delete
    {--dry-run : Show what would be deleted without deleting anything}
    {--force : Delete without asking for confirmation}')]
#[Description('Delete the therapist accounts (user + team member) of the departed contractors highlighted in the contractor directory')]
class DeleteTherapistAccountsCommand extends Command
{
    /**
     * The contractors highlighted yellow in the directory, with the email
     * their account logs in with (work email when the sheet has one).
     *
     * @var array<int, array{name: string, email: string}>
     */
    public const DEPARTED = [
        ['name' => 'Sarah Jane Racelis', 'email' => 'jhean_a@yahoo.com'],
        ['name' => 'Abby Sawchuk', 'email' => 'abby.sawchuk@gmail.com'],
        ['name' => 'Elorie Mae Tagu-e', 'email' => 'elorietague@gmail.com'],
        ['name' => 'Sumandeep Kaur Thind', 'email' => 'sumandeepthind@yahoo.ca'],
        ['name' => 'Merry Chris Sultan', 'email' => 'chrisreris@gmail.com'],
        ['name' => 'Diane Sordilla', 'email' => 'diane.jsordilla@gmail.com'],
        ['name' => 'Ezralie Ibarra', 'email' => 'ezralie.ibarra@creativeabilitiestherapyservices.ca'],
    ];

    /**
     * Records the database deletes along with the therapist, as label →
     * [table, columns pointing at the user]. Everything else that references
     * a therapist (sessions, invoices, clients) is kept and simply unassigned.
     *
     * @var array<string, array{0: string, 1: array<int, string>}>
     */
    private const CASCADED_RECORDS = [
        'client services' => ['client_services', ['therapist_id']],
        'billing items' => ['billing_items', ['therapist_id']],
        'timesheets' => ['timesheets', ['therapist_id']],
        'timesheet entries' => ['timesheet_entries', ['therapist_id']],
        'intake approvals' => ['intake_therapist_approvals', ['therapist_id']],
        'messages' => ['messages', ['sender_id', 'recipient_id']],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $users = [];
        $missing = 0;

        foreach ($this->therapists() as $entry) {
            $email = Str::lower(trim($entry['email']));
            $user = User::query()->where('email', $email)->role('therapist')->first();

            if ($user === null) {
                $this->line("Skipped {$entry['name']}: no therapist account for {$email}.");
                $missing++;

                continue;
            }

            $users[] = $user;
            $this->warn(($dryRun ? 'Would delete' : 'Will delete')." {$entry['name']} <{$email}>{$this->cascadeSummary($user)}.");
        }

        $count = count($users);

        if (! $dryRun && $count > 0) {
            if (! $this->option('force') && ! $this->confirm("Delete {$count} therapist account(s)? This cannot be undone.")) {
                $this->info('Nothing deleted.');

                return self::SUCCESS;
            }

            foreach ($users as $user) {
                $this->deleteTherapist($user);
            }
        }

        $verb = $dryRun ? 'Would delete' : 'Deleted';
        $this->newLine();
        $this->info("{$verb} {$count}, {$missing} had no therapist account.");

        return self::SUCCESS;
    }

    private function deleteTherapist(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->delete();

            AuditLogger::log('Deleted team member', 'Users', "Deleted therapist account {$user->email} via therapists:delete", 'warning');
        });
    }

    /**
     * The departed contractors to process; a seam so tests can run against
     * their own list.
     *
     * @return array<int, array{name: string, email: string}>
     */
    protected function therapists(): array
    {
        return self::DEPARTED;
    }

    /**
     * A " (also removes: 2 client services, 5 billing items)" suffix, or an
     * empty string when nothing cascades from the therapist.
     */
    private function cascadeSummary(User $user): string
    {
        $parts = [];

        foreach (self::CASCADED_RECORDS as $label => [$table, $columns]) {
            $count = DB::table($table)
                ->where(function ($query) use ($columns, $user): void {
                    foreach ($columns as $column) {
                        $query->orWhere($column, $user->id);
                    }
                })
                ->count();

            if ($count > 0) {
                $parts[] = "{$count} {$label}";
            }
        }

        return $parts === [] ? '' : ' (also removes: '.implode(', ', $parts).')';
    }
}
