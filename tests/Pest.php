<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ServiceContract;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->browser()->timeout(8000);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Shared across Admin*ControllerTest feature suites (intake, client, ...).
 */
function adminUser(): User
{
    return User::factory()->admin()->create();
}

function therapistUser(): User
{
    return User::factory()->therapist()->create();
}

/**
 * A therapist holding an aide position: they log hours on a time sheet
 * rather than billing services, so Billing and Invoices are closed to them
 * and Hours and Timesheets are not.
 */
function aideUser(string $position = 'Behavioural & Developmental Aide'): User
{
    $user = User::factory()->therapist()->create();
    TeamMember::factory()->create(['user_id' => $user->id, 'position' => $position]);

    return $user->load('teamMember');
}

/**
 * A 1x1 PNG, the smallest thing that clears the signature validator
 * (App\Rules\PngSignature). Shared by every document a signature lands on.
 */
function signaturePng(): string
{
    return 'data:image/png;base64,'.base64_encode(base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));
}

/**
 * An availed service carrying the admin authorization Phase 20 requires
 * before anything can be scheduled against it: forty hours across the
 * current month, held by the given therapist.
 *
 * Pass `$contract` to change the pool — `['allotted_hours' => 2]` for a
 * service that runs out after one visit, or a factory state for one that has
 * expired.
 *
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $contract
 */
function contractedService(Client $client, User $therapist, array $attributes = [], array $contract = []): ClientService
{
    $service = ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        ...$attributes,
    ]);

    ServiceContract::factory()->for($service, 'clientService')->create([
        'therapist_id' => $therapist->id,
        ...$contract,
    ]);

    return $service->refresh();
}

/**
 * A Client with its own portal login (`user_id` set), for tests that act as
 * the client role. Access the login via `$client->user`.
 */
function clientWithUser(): Client
{
    $user = User::factory()->client()->create();

    return Client::factory()->create(['user_id' => $user->id])->load('user');
}
