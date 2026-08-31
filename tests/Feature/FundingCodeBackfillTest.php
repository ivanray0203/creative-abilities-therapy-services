<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Intake;
use App\Models\ServiceContract;

/**
 * Phase 21 — filling `funding_code` in on the contracts issued before the
 * column existed, from the funding source already recorded on the availed
 * service or the child's intake.
 *
 * The migration has already run against an empty database by the time these
 * start, so each test builds the pre-code shape and runs `up()` again.
 *
 * @see database/migrations/2026_08_30_111505_backfill_funding_code_on_service_contracts.php
 */
function runFundingCodeBackfill(): void
{
    $migration = require database_path('migrations/2026_08_30_111505_backfill_funding_code_on_service_contracts.php');

    $migration->up();
}

/**
 * A contract with no funding code, on a child whose intake was approved under
 * `$intakeSource` and whose availed service records `$serviceSource`.
 */
function contractAwaitingCode(?string $intakeSource, ?string $serviceSource = null): ServiceContract
{
    $therapist = therapistUser();
    $intake = Intake::factory()->create(['funding_source' => $intakeSource]);
    $client = Client::factory()->create([
        'original_intake_id' => $intake->id,
        'primary_therapist_id' => $therapist->id,
    ]);

    $service = ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        'funding_source' => $serviceSource,
    ]);

    return ServiceContract::factory()->for($service, 'clientService')->create([
        'therapist_id' => $therapist->id,
        'funding_code' => null,
    ]);
}

test('an intake funding source becomes the sheet code', function (string $source, string $expected) {
    $contract = contractAwaitingCode($source);

    runFundingCodeBackfill();

    expect($contract->refresh()->funding_code)->toBe($expected);
})->with([
    ['SS-FSCD', 'SS'],
    ['BDS-FSCD', 'BDS'],
    ['Counselling-FSCD', 'Counselling'],
    ['private', 'Private'],
]);

test('the availed service funding source wins over the intake', function () {
    // A child approved under one stream can avail a service funded by
    // another, and the service-level record is the more specific of the two.
    $contract = contractAwaitingCode('SS-FSCD', 'BDS-FSCD');

    runFundingCodeBackfill();

    expect($contract->refresh()->funding_code)->toBe('BDS');
});

test('the intake is used when the service records nothing usable', function (?string $serviceSource) {
    $contract = contractAwaitingCode('SS-FSCD', $serviceSource);

    runFundingCodeBackfill();

    expect($contract->refresh()->funding_code)->toBe('SS');
})->with([null, '', 'Grant #4102']);

test('a funding source the sheet has no code for is left blank', function (?string $source) {
    // Insurance is the real case: the office's sheet has never coded it, and a
    // guess would put a category in front of an admin that they do not use.
    $contract = contractAwaitingCode($source);

    runFundingCodeBackfill();

    expect($contract->refresh()->funding_code)->toBeNull();
})->with(['Insurance', null]);

test('a code already on a contract is never overwritten', function () {
    $contract = contractAwaitingCode('SS-FSCD');
    $contract->update(['funding_code' => 'BDS/split']);

    runFundingCodeBackfill();

    // Splits are an admin's judgement about how one child's hours are shared,
    // recorded nowhere the migration can read.
    expect($contract->refresh()->funding_code)->toBe('BDS/split');
});

test('the backfill can be run twice without changing its answer', function () {
    $contract = contractAwaitingCode('BDS-FSCD');

    runFundingCodeBackfill();
    runFundingCodeBackfill();

    expect($contract->refresh()->funding_code)->toBe('BDS');
});

test('a backfilled code reaches the hour tracking sheet', function () {
    $contract = contractAwaitingCode('SS-FSCD');
    $contract->update(['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);

    runFundingCodeBackfill();

    $this->actingAs($contract->therapist)
        ->get('/therapist/hour-tracking?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.sections.0.rows.0.funding_code', 'SS')
        );
});

test('the admin client page sends the funding codes the validator accepts', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())
        ->get("/admin/clients/{$client->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // Sent down rather than hard-coded in the modal: a stale copy
            // would blank a code simply by opening the form.
            ->where('fundingCodes', ServiceContract::FUNDING_CODES)
        );
});

/**
 * An availed service ready to have a contract issued against it, on a child
 * approved under `$intakeSource`.
 */
function serviceAwaitingContract(?string $intakeSource, ?string $serviceSource = null): ClientService
{
    $therapist = therapistUser();
    $intake = Intake::factory()->create(['funding_source' => $intakeSource]);
    $client = Client::factory()->create([
        'original_intake_id' => $intake->id,
        'primary_therapist_id' => $therapist->id,
    ]);

    return ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        'funding_source' => $serviceSource,
    ]);
}

/** @return array<string, mixed> */
function newContractPayload(array $overrides = []): array
{
    return [
        'allotted_hours' => 40,
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
        ...$overrides,
    ];
}

test('the issue contract form opens on the funder already on record', function (
    ?string $intakeSource,
    ?string $serviceSource,
    ?string $expected,
) {
    $service = serviceAwaitingContract($intakeSource, $serviceSource);

    $this->actingAs(adminUser())
        ->get("/admin/clients/{$service->client_id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('client.client_services.0.default_funding_code', $expected)
        );
})->with([
    'from the intake' => ['BDS-FSCD', null, 'BDS'],
    'the service wins' => ['SS-FSCD', 'Counselling-FSCD', 'Counselling'],
    'nothing the sheet codes' => ['Insurance', null, null],
    'nothing at all' => [null, null, null],
]);

test('what the admin submits is what is saved, suggestion or not', function () {
    $service = serviceAwaitingContract('SS-FSCD');

    // The form arrives pre-selected, so the server takes the posted value at
    // face value — including a blank, which is a choice the admin can make.
    $this->actingAs(adminUser())
        ->post(
            "/admin/clients/{$service->client_id}/services/{$service->id}/contracts",
            newContractPayload(['funding_code' => 'BDS/split']),
        )
        ->assertSessionHasNoErrors();

    expect($service->contracts()->first()->funding_code)->toBe('BDS/split');
});

test('a contract issued with the suggestion cleared keeps no code', function () {
    $service = serviceAwaitingContract('SS-FSCD');

    $this->actingAs(adminUser())
        ->post(
            "/admin/clients/{$service->client_id}/services/{$service->id}/contracts",
            newContractPayload(['funding_code' => null]),
        )
        ->assertSessionHasNoErrors();

    expect($service->contracts()->first()->funding_code)->toBeNull();
});

test('editing a contract to clear its funding code leaves it cleared', function () {
    $service = serviceAwaitingContract('SS-FSCD');
    $admin = adminUser();
    $base = "/admin/clients/{$service->client_id}/services/{$service->id}/contracts";

    $this->actingAs($admin)
        ->post($base, newContractPayload(['funding_code' => 'SS']))
        ->assertSessionHasNoErrors();

    $contract = $service->contracts()->first();

    $this->actingAs($admin)
        ->put("{$base}/{$contract->id}", newContractPayload(['funding_code' => null]))
        ->assertSessionHasNoErrors();

    expect($contract->refresh()->funding_code)->toBeNull();
});
