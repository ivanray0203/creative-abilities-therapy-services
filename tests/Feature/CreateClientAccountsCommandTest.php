<?php

use App\Console\Commands\CreateClientAccountsCommand;
use App\Mail\WelcomeClientAccountMail;
use App\Models\BillingAccount;
use App\Models\Client;
use App\Models\Intake;
use App\Models\User;
use App\Services\IntakeApprovalService;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Runs the command against a hand-picked directory instead of the real
 * static list, so each test controls exactly which rows are processed.
 *
 * @param  array<int, array<string, string>>  $rows
 */
function runClientsCreateWith(array $rows, bool $dryRun = false): int
{
    $command = new class($rows, app(IntakeApprovalService::class), app(ReferenceNumberGenerator::class)) extends CreateClientAccountsCommand
    {
        protected $signature = 'clients:create {--dry-run}';

        /** @param array<int, array<string, string>> $rows */
        public function __construct(
            private readonly array $rows,
            IntakeApprovalService $approvalService,
            ReferenceNumberGenerator $referenceNumbers,
        ) {
            parent::__construct($approvalService, $referenceNumbers);
        }

        protected function directory(): array
        {
            return $this->rows;
        }
    };

    $command->setLaravel(app());
    Artisan::registerCommand($command);

    return Artisan::call('clients:create', $dryRun ? ['--dry-run' => true] : []);
}

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function clientDirectoryRow(array $overrides = []): array
{
    return [
        'parent_email' => 'a_affan@outlook.com',
        'child_name' => 'Maryam Abbas',
        'parent_name' => 'Asmaa Affan',
        'address' => '4719 Rundlewood Road NE',
        'city_province' => 'Calgary, Alberta',
        'postal_code' => 'T1Y 2N7',
        'fscd_file' => '52805',
        ...$overrides,
    ];
}

test('it creates an approved intake, a client with billing, and a parent account per entry', function () {
    Mail::fake();

    $exitCode = runClientsCreateWith([
        clientDirectoryRow(),
        clientDirectoryRow([
            'parent_email' => 'Joel.A.Shank@gmail.com',
            'child_name' => 'Austin Shank',
            'parent_name' => 'Joel Shank',
            'address' => '2-235056 Range Road 254',
            'city_province' => 'Wheatland County AB',
            'postal_code' => 'T1P 0R4',
            'fscd_file' => 'Private',
        ]),
    ]);

    expect($exitCode)->toBe(0);
    expect(Intake::query()->count())->toBe(2);
    expect(Client::query()->count())->toBe(2);
    expect(BillingAccount::query()->count())->toBe(2);
    expect(User::query()->where('role', 'client')->count())->toBe(2);

    $intake = Intake::query()->where('primary_parent_email', 'a_affan@outlook.com')->firstOrFail();
    expect($intake->child_first_name)->toBe('Maryam')
        ->and($intake->child_last_name)->toBe('Abbas')
        ->and($intake->status)->toBe('approved')
        ->and($intake->reviewed)->toBeTrue()
        ->and($intake->approved_as_client)->toBeTrue()
        ->and($intake->funding_source)->toBe('BDS-FSCD')
        ->and($intake->funding_number)->toBe('52805')
        ->and($intake->primary_parent_name)->toBe('Asmaa Affan')
        ->and($intake->city)->toBe('Calgary')
        ->and($intake->state_province)->toBe('Alberta')
        ->and($intake->postal_code)->toBe('T1Y 2N7')
        ->and($intake->reference_number)->toStartWith('INT-');

    $client = Client::query()->where('original_intake_id', $intake->id)->firstOrFail();
    $parent = User::query()->where('email', 'a_affan@outlook.com')->firstOrFail();
    expect($client->user_id)->toBe($parent->id)
        ->and($client->status)->toBe('active')
        ->and($parent->first_name)->toBe('Asmaa')
        ->and($parent->last_name)->toBe('Affan')
        ->and($parent->is_active)->toBeTrue();

    $private = Intake::query()->where('primary_parent_email', 'joel.a.shank@gmail.com')->firstOrFail();
    expect($private->funding_source)->toBe('private')
        ->and($private->funding_number)->toBeNull()
        ->and($private->city)->toBe('Wheatland County')
        ->and($private->state_province)->toBe('Alberta');

    Mail::assertQueued(WelcomeClientAccountMail::class, 2);
    Mail::assertQueued(WelcomeClientAccountMail::class, function (WelcomeClientAccountMail $mail) use ($parent) {
        return $mail->hasTo('a_affan@outlook.com')
            && $mail->email === 'a_affan@outlook.com'
            && $mail->firstName === 'Asmaa'
            && strlen($mail->password) === 10
            && Hash::check($mail->password, $parent->password);
    });
});

test('siblings share one parent account and the parent is emailed only once', function () {
    Mail::fake();

    $exitCode = runClientsCreateWith([
        clientDirectoryRow(['parent_email' => 'farran.harkonen@gmail.com', 'child_name' => 'Aurora Harkonen', 'parent_name' => 'Farran Harkonen', 'fscd_file' => '54009']),
        clientDirectoryRow(['parent_email' => 'farran.harkonen@gmail.com ', 'child_name' => 'Isabelle Harkonen', 'parent_name' => 'Farran Harkonen', 'fscd_file' => '70229']),
    ]);

    expect($exitCode)->toBe(0);
    expect(User::query()->where('role', 'client')->count())->toBe(1);
    expect(Client::query()->count())->toBe(2);

    $parent = User::query()->where('email', 'farran.harkonen@gmail.com')->firstOrFail();
    expect($parent->clientProfiles()->count())->toBe(2);

    Mail::assertQueued(WelcomeClientAccountMail::class, 1);
});

test('it skips children already on file and entries without a parent email', function () {
    Mail::fake();

    Intake::factory()->create([
        'primary_parent_email' => 'a_affan@outlook.com',
        'child_first_name' => 'Maryam',
        'child_last_name' => 'Abbas',
    ]);

    $exitCode = runClientsCreateWith([
        clientDirectoryRow(),
        clientDirectoryRow(['parent_email' => '', 'child_name' => 'No Parent Email']),
        clientDirectoryRow(['parent_email' => 'new@example.com', 'child_name' => 'Brand New', 'parent_name' => 'New Parent']),
    ]);
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Skipped Maryam Abbas: already a client under a_affan@outlook.com')
        ->and($output)->toContain('Skipped No Parent Email: no parent email address')
        ->and($output)->toContain('Created 1, skipped 1 existing, 1 could not be processed');

    expect(Intake::query()->count())->toBe(2);
    expect(Client::query()->count())->toBe(1);
    expect(User::query()->count())->toBe(1);

    Mail::assertQueued(WelcomeClientAccountMail::class, 1);
});

test('a dry run reports without creating anything or sending mail', function () {
    Mail::fake();

    runClientsCreateWith([clientDirectoryRow()], dryRun: true);

    expect(Artisan::output())->toContain('Would create Maryam Abbas <a_affan@outlook.com> (FSCD #52805)');
    expect(Intake::query()->count())->toBe(0);
    expect(User::query()->count())->toBe(0);
    Mail::assertNothingOutgoing();
});

test('every entry in the built-in directory resolves to a parent email and child name', function () {
    Mail::fake();

    $exitCode = Artisan::call('clients:create', ['--dry-run' => true]);
    $total = count(CreateClientAccountsCommand::DIRECTORY);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain("Would create {$total}, skipped 0 existing, 0 could not be processed");

    expect(Intake::query()->count())->toBe(0);
    Mail::assertNothingOutgoing();
});
