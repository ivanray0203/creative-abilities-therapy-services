<?php

use App\Console\Commands\CreateTherapistAccountsCommand;
use App\Mail\StaffInviteMail;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Runs the command against a hand-picked directory instead of the real
 * static list, so each test controls exactly which rows are processed.
 *
 * @param  array<int, array<string, string>>  $rows
 */
function runTherapistsCreateWith(array $rows, bool $dryRun = false): int
{
    $command = new class($rows) extends CreateTherapistAccountsCommand
    {
        protected $signature = 'therapists:create {--dry-run}';

        /** @param array<int, array<string, string>> $rows */
        public function __construct(private readonly array $rows)
        {
            parent::__construct();
        }

        protected function directory(): array
        {
            return $this->rows;
        }
    };

    $command->setLaravel(app());
    Artisan::registerCommand($command);

    return Artisan::call('therapists:create', $dryRun ? ['--dry-run' => true] : []);
}

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function directoryRow(array $overrides = []): array
{
    return [
        'name' => 'Tsedal Tewolde',
        'profession' => 'Behavioural Aide',
        'address' => '127 Templewood Rd NE',
        'city_province' => 'Calgary, Alberta',
        'postal_code' => 'T1Y 4B1',
        'phone' => '403 402 9953',
        'personal_email' => 'tsedaltewolde@gmail.com',
        'work_email' => '-',
        'start_date' => '2024-06-01',
        'end_date' => '2027-05-31',
        ...$overrides,
    ];
}

test('it creates a therapist user and team member per entry and emails the temporary password', function () {
    Mail::fake();

    $exitCode = runTherapistsCreateWith([
        directoryRow(),
        directoryRow([
            'name' => 'Frances Garrido',
            'profession' => 'Behavioural Consultant',
            'personal_email' => 'fad.garrido@gmail.com',
            'work_email' => 'Frances.Garrido@creativeabilitiestherapyservices.ca',
        ]),
        directoryRow([
            'name' => 'Hailey Shaw',
            'profession' => 'Occupational Therapist',
            'city_province' => 'Chestermere AB',
            'personal_email' => ' hcj.shaw@gmail.com ',
            'work_email' => '',
        ]),
    ]);

    expect($exitCode)->toBe(0);
    expect(User::query()->where('role', 'therapist')->count())->toBe(3);
    expect(TeamMember::query()->count())->toBe(3);

    $aide = User::query()->where('email', 'tsedaltewolde@gmail.com')->firstOrFail();
    expect($aide->first_name)->toBe('Tsedal')
        ->and($aide->last_name)->toBe('Tewolde')
        ->and($aide->is_active)->toBeTrue()
        ->and($aide->teamMember->position)->toBe('Behavioural & Developmental Aide')
        ->and($aide->teamMember->isAide())->toBeTrue()
        ->and($aide->teamMember->employment_status)->toBe('active')
        ->and($aide->teamMember->hire_date->toDateString())->toBe('2024-06-01')
        ->and($aide->teamMember->city)->toBe('Calgary')
        ->and($aide->teamMember->province)->toBe('Alberta')
        ->and($aide->teamMember->zip_code)->toBe('T1Y 4B1')
        ->and($aide->teamMember->secondary_email)->toBeNull()
        ->and($aide->teamMember->additional_notes)->toBe('Contract: 2024-06-01 to 2027-05-31');

    $consultant = User::query()->where('email', 'frances.garrido@creativeabilitiestherapyservices.ca')->firstOrFail();
    expect($consultant->teamMember->position)->toBe('Behavioural Consultant/Therapist (BC)')
        ->and($consultant->teamMember->secondary_email)->toBe('fad.garrido@gmail.com');

    $ot = User::query()->where('email', 'hcj.shaw@gmail.com')->firstOrFail();
    expect($ot->teamMember->position)->toBe('Occupational Therapist (OT)')
        ->and($ot->teamMember->city)->toBe('Chestermere')
        ->and($ot->teamMember->province)->toBe('Alberta');

    Mail::assertQueued(StaffInviteMail::class, 3);
    Mail::assertQueued(StaffInviteMail::class, function (StaffInviteMail $mail) use ($aide) {
        return $mail->hasTo('tsedaltewolde@gmail.com')
            && $mail->email === 'tsedaltewolde@gmail.com'
            && $mail->firstName === 'Tsedal'
            && strlen($mail->password) === 10
            && Hash::check($mail->password, $aide->password);
    });
});

test('it skips existing accounts, unknown professions and entries without an email', function () {
    Mail::fake();

    User::factory()->therapist()->create(['email' => 'existing@example.com']);

    $exitCode = runTherapistsCreateWith([
        directoryRow(['name' => 'Already Here', 'profession' => 'Psychologist', 'personal_email' => 'existing@example.com']),
        directoryRow(['name' => 'No Email', 'profession' => 'Psychologist', 'personal_email' => '-', 'work_email' => '']),
        directoryRow(['name' => 'Odd Job', 'profession' => 'Astronaut', 'personal_email' => 'odd@example.com']),
        directoryRow(['name' => 'Brand New', 'profession' => 'Physiotherapist', 'personal_email' => 'new@example.com']),
    ]);
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('existing@example.com already has an account')
        ->and($output)->toContain('Skipped No Email: no email address')
        ->and($output)->toContain('unknown profession "Astronaut"')
        ->and($output)->toContain('Created 1, skipped 1 existing, 2 could not be processed');

    expect(User::query()->count())->toBe(2);
    expect(TeamMember::query()->count())->toBe(1);

    Mail::assertQueued(StaffInviteMail::class, 1);
    Mail::assertNotQueued(StaffInviteMail::class, fn (StaffInviteMail $mail) => $mail->hasTo('existing@example.com'));
});

test('a dry run reports without creating anything or sending mail', function () {
    Mail::fake();

    runTherapistsCreateWith([directoryRow()], dryRun: true);

    expect(Artisan::output())->toContain('Would create Tsedal Tewolde <tsedaltewolde@gmail.com>');
    expect(User::query()->count())->toBe(0);
    Mail::assertNothingOutgoing();
});

test('every entry in the built-in directory resolves to an email and a known position', function () {
    Mail::fake();

    $exitCode = Artisan::call('therapists:create', ['--dry-run' => true]);
    $total = count(CreateTherapistAccountsCommand::DIRECTORY);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain("Would create {$total}, skipped 0 existing, 0 could not be processed");

    expect(User::query()->count())->toBe(0);
    Mail::assertNothingOutgoing();
});
