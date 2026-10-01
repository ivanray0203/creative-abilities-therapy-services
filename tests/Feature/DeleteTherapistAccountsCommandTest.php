<?php

use App\Console\Commands\CreateTherapistAccountsCommand;
use App\Console\Commands\DeleteTherapistAccountsCommand;
use App\Models\BillingItem;
use App\Models\SystemLog;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/**
 * Registers the command against a hand-picked list instead of the real
 * static one, so each test controls exactly which accounts are targeted.
 *
 * @param  array<int, array{name: string, email: string}>  $therapists
 */
function registerTherapistsDeleteWith(array $therapists): void
{
    $command = new class($therapists) extends DeleteTherapistAccountsCommand
    {
        protected $signature = 'therapists:delete {--dry-run} {--force}';

        /** @param array<int, array{name: string, email: string}> $therapists */
        public function __construct(private readonly array $therapists)
        {
            parent::__construct();
        }

        protected function therapists(): array
        {
            return $this->therapists;
        }
    };

    $command->setLaravel(app());
    Artisan::registerCommand($command);
}

function therapistWithTeamMember(string $email): User
{
    $user = User::factory()->therapist()->create(['email' => $email]);
    TeamMember::factory()->create(['user_id' => $user->id]);

    return $user;
}

test('it deletes the listed therapists with their team members and leaves everyone else', function () {
    $departed = therapistWithTeamMember('departed@example.com');
    $staying = therapistWithTeamMember('staying@example.com');
    BillingItem::factory()->count(2)->create(['therapist_id' => $departed->id]);

    registerTherapistsDeleteWith([
        ['name' => 'Departed Person', 'email' => ' Departed@example.com '],
        ['name' => 'Never Created', 'email' => 'missing@example.com'],
    ]);

    $exitCode = Artisan::call('therapists:delete', ['--force' => true]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Will delete Departed Person <departed@example.com> (also removes: 2 billing items)')
        ->and($output)->toContain('Skipped Never Created: no therapist account for missing@example.com')
        ->and($output)->toContain('Deleted 1, 1 had no therapist account');

    expect(User::query()->whereKey($departed->id)->exists())->toBeFalse()
        ->and(TeamMember::query()->where('user_id', $departed->id)->exists())->toBeFalse()
        ->and(BillingItem::query()->where('therapist_id', $departed->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($staying->id)->exists())->toBeTrue()
        ->and(TeamMember::query()->where('user_id', $staying->id)->exists())->toBeTrue();

    $log = SystemLog::query()->where('action', 'Deleted team member')->sole();
    expect($log->details['detail'])->toContain('departed@example.com');
});

test('it never deletes an admin or client that shares a listed email', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $client = User::factory()->client()->create(['email' => 'client@example.com']);

    registerTherapistsDeleteWith([
        ['name' => 'An Admin', 'email' => 'admin@example.com'],
        ['name' => 'A Client', 'email' => 'client@example.com'],
    ]);

    Artisan::call('therapists:delete', ['--force' => true]);

    expect(Artisan::output())->toContain('Deleted 0, 2 had no therapist account');
    expect(User::query()->whereKey([$admin->id, $client->id])->count())->toBe(2);
});

test('a dry run reports without deleting anything', function () {
    $departed = therapistWithTeamMember('departed@example.com');

    registerTherapistsDeleteWith([['name' => 'Departed Person', 'email' => 'departed@example.com']]);

    Artisan::call('therapists:delete', ['--dry-run' => true]);

    expect(Artisan::output())
        ->toContain('Would delete Departed Person <departed@example.com>')
        ->toContain('Would delete 1, 0 had no therapist account');
    expect(User::query()->whereKey($departed->id)->exists())->toBeTrue();
    expect(TeamMember::query()->count())->toBe(1);
});

test('it deletes nothing when the confirmation is declined', function () {
    $departed = therapistWithTeamMember('departed@example.com');

    registerTherapistsDeleteWith([['name' => 'Departed Person', 'email' => 'departed@example.com']]);

    $this->artisan('therapists:delete')
        ->expectsConfirmation('Delete 1 therapist account(s)? This cannot be undone.', 'no')
        ->expectsOutputToContain('Nothing deleted.')
        ->assertSuccessful();

    expect(User::query()->whereKey($departed->id)->exists())->toBeTrue();
});

test('every departed contractor is an entry of the contractor directory', function () {
    $directory = collect(CreateTherapistAccountsCommand::DIRECTORY)
        ->flatMap(fn (array $row): array => [$row['work_email'], $row['personal_email']]);

    foreach (DeleteTherapistAccountsCommand::DEPARTED as $entry) {
        expect($directory)->toContain($entry['email']);
    }
});
