<?php

use App\Console\Commands\AssignClientTherapistsCommand;
use App\Models\Client;
use App\Models\Intake;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

/**
 * Registers the command against a hand-picked caseload instead of the real
 * static one, so each test controls exactly which clients are targeted.
 *
 * @param  array<string, array<string, string>>  $caseload
 */
function registerAssignTherapistsWith(array $caseload): void
{
    $command = new class($caseload) extends AssignClientTherapistsCommand
    {
        protected $signature = 'clients:assign-therapists {--dry-run}';

        /** @param array<string, array<string, string>> $caseload */
        public function __construct(private readonly array $caseload)
        {
            parent::__construct();
        }

        protected function caseload(): array
        {
            return $this->caseload;
        }
    };

    $command->setLaravel(app());
    Artisan::registerCommand($command);
}

function caseloadClient(string $firstName, string $lastName): Client
{
    $intake = Intake::factory()->create(['child_first_name' => $firstName, 'child_last_name' => $lastName]);

    return Client::factory()->create(['original_intake_id' => $intake->id]);
}

function caseloadTherapist(string $firstName, string $lastName): User
{
    $user = User::factory()->therapist()->create(['first_name' => $firstName, 'last_name' => $lastName]);
    TeamMember::factory()->create(['user_id' => $user->id, 'client' => []]);

    return $user;
}

test('it puts every therapist on the care team and makes the first one primary', function () {
    $client = caseloadClient('Abubakarr', 'Jalloh');
    $consultant = caseloadTherapist('Angela', 'Cua');
    $aide = caseloadTherapist('Tsedal', 'Tewolde');

    registerAssignTherapistsWith([
        'Abubakarr Jalloh' => [
            'Angela Cua' => 'Behavioural Consultant',
            'Tsedal Tewolde' => 'Behavioural Aide',
        ],
    ]);

    expect(Artisan::call('clients:assign-therapists'))->toBe(0);

    $client->refresh();
    expect($client->primary_therapist_id)->toBe($consultant->id)
        ->and($client->assigned_therapist_id)->toBe($consultant->id)
        ->and($client->careTeam()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$consultant->id, $aide->id])->sort()->values()->all());

    expect(TeamMember::where('user_id', $aide->id)->first()->client)->toBe([$client->id]);
});

test('an existing primary therapist is kept', function () {
    $client = caseloadClient('Abel', 'Mwangi');
    $current = caseloadTherapist('Current', 'Primary');
    $client->assignTherapist($current);
    $therapist = caseloadTherapist('Deon', 'Kruger');

    registerAssignTherapistsWith(['Abel Mwangi' => ['Deon Kruger' => 'Occupational Therapist']]);

    Artisan::call('clients:assign-therapists');

    $client->refresh();
    expect($client->primary_therapist_id)->toBe($current->id)
        ->and($client->careTeam()->pluck('users.id')->all())->toContain($therapist->id, $current->id);
});

test('a client is found without the middle name the caseload leaves out', function () {
    $client = caseloadClient('Archer Dawson', 'Johansen');
    $therapist = caseloadTherapist('Zoha', 'Azam');

    registerAssignTherapistsWith(['Archer Johansen' => ['Zoha Azam' => 'Speech-Language Pathologist']]);

    Artisan::call('clients:assign-therapists');

    expect($client->refresh()->primary_therapist_id)->toBe($therapist->id);
});

test('spelling aliases connect the caseload to the right client and therapist', function () {
    $client = caseloadClient('Richtan', 'Huynh');
    $connie = caseloadTherapist('Hiu Hung', 'Lo');

    registerAssignTherapistsWith(['Richtan Hyunh' => ['Connie Lo' => 'Occupational Therapist']]);

    Artisan::call('clients:assign-therapists');

    expect($client->refresh()->primary_therapist_id)->toBe($connie->id);
});

test('two clients sharing a first and last name are skipped rather than guessed', function () {
    $first = caseloadClient('Zane Alesso', 'Rodriguez');
    $second = caseloadClient('Zane Michael', 'Rodriguez');
    caseloadTherapist('Zoha', 'Azam');

    registerAssignTherapistsWith(['Zane Rodriguez' => ['Zoha Azam' => 'Speech-Language Pathologist']]);

    Artisan::call('clients:assign-therapists');

    expect(Artisan::output())->toContain('2 clients share that name');
    expect($first->refresh()->primary_therapist_id)->toBeNull()
        ->and($second->refresh()->primary_therapist_id)->toBeNull();
});

test('missing clients and therapists are reported and skipped', function () {
    $client = caseloadClient('Mia', 'Santos');
    $therapist = caseloadTherapist('Zoha', 'Azam');

    registerAssignTherapistsWith([
        'Mia Santos' => [
            'Kamal Srinivasan' => 'Behavioural Aide',
            'Zoha Azam' => 'Speech-Language Pathologist',
        ],
        'Kobe Lee' => ['Zoha Azam' => 'Speech-Language Pathologist'],
    ]);

    expect(Artisan::call('clients:assign-therapists'))->toBe(0);
    $output = Artisan::output();

    expect($output)
        ->toContain('Clients not found (1): Kobe Lee')
        ->toContain('Therapists without an account (1): Kamal Srinivasan');

    // The therapist who does exist is still assigned, as primary.
    expect($client->refresh()->primary_therapist_id)->toBe($therapist->id);
});

test('a dry run changes nothing', function () {
    $client = caseloadClient('Abel', 'Mwangi');
    caseloadTherapist('Deon', 'Kruger');

    registerAssignTherapistsWith(['Abel Mwangi' => ['Deon Kruger' => 'Occupational Therapist']]);

    Artisan::call('clients:assign-therapists', ['--dry-run' => true]);

    expect(Artisan::output())->toContain('Would assign Deon Kruger (Occupational Therapist) to Abel Mwangi as primary therapist.');
    expect($client->refresh()->primary_therapist_id)->toBeNull()
        ->and($client->careTeam()->count())->toBe(0);
});

test('running it twice assigns nothing new the second time', function () {
    $client = caseloadClient('Abel', 'Mwangi');
    caseloadTherapist('Deon', 'Kruger');

    registerAssignTherapistsWith(['Abel Mwangi' => ['Deon Kruger' => 'Occupational Therapist']]);

    Artisan::call('clients:assign-therapists');
    Artisan::call('clients:assign-therapists');

    expect(Artisan::output())->toContain('Assigned 0 therapist(s), 0 as primary; 1 already assigned.');
    expect($client->careTeam()->count())->toBe(1);
});

test('the real caseload leaves out the PT placeholder and non-provider columns', function () {
    $names = collect(AssignClientTherapistsCommand::CASELOAD)->flatMap(fn (array $therapists): array => array_keys($therapists));
    $roles = collect(AssignClientTherapistsCommand::CASELOAD)->flatMap(fn (array $therapists): array => array_values($therapists));

    expect($names)->not->toContain('PT')
        ->and($names)->not->toContain('Bernard Lerit')
        ->and($roles->unique()->sort()->values()->all())->toBe([
            'Behavioural Aide',
            'Behavioural Consultant',
            'Occupational Therapist',
            'Physiotherapist',
            'Psychologist',
            'Speech-Language Pathologist',
        ]);
});
