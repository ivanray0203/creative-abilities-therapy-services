<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use App\Models\User;

/**
 * Phase 20 — the rollout migration. Without it, the new gate would take
 * effect the moment it deploys and every therapist would find their whole
 * caseload unbookable until an admin had worked through it by hand.
 *
 * The migration has already run against an empty database by the time these
 * start, so each test builds the pre-contract shape and runs `up()` again.
 *
 * @see tasks/20-service-contracts-and-hour-budgets.md
 */
function runBackfill(): void
{
    $migration = require database_path('migrations/2026_08_28_130636_backfill_service_contracts.php');

    $migration->up();
}

/** An availed service as it looked before contracts existed. */
function legacyService(User $therapist, ?Client $client = null): ClientService
{
    $client ??= Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    return ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        'start_date' => now()->subMonths(3)->toDateString(),
    ]);
}

test('every pre-existing availed service comes out of the backfill bookable', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    legacyService($therapist, $client);

    // The gate is closed to it beforehand.
    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 0));

    runBackfill();

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 1));
});

test('the backfill fills the ledger from what each session had already booked', function () {
    $therapist = therapistUser();
    $service = legacyService($therapist);

    $session = ScheduleSession::factory()->create([
        'client_id' => $service->client_id,
        'therapist_id' => $therapist->id,
        'scheduled_start' => now()->subMonth(),
        'duration' => 90,
        'status' => 'completed',
    ]);
    // The pre-contract link shape: no hours, no contract.
    $session->clientServices()->attach($service->id);

    runBackfill();

    $contract = $service->contracts()->first();

    expect((float) $session->fresh()->clientServices->first()->pivot->hours)->toBe(1.5)
        ->and($session->fresh()->clientServices->first()->pivot->service_contract_id)->toBe($contract->id)
        ->and($contract->usedHours())->toBe(1.5);
});

test('a session covering two services has its hours split between them', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $first = legacyService($therapist, $client);
    $second = legacyService($therapist, $client);

    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'scheduled_start' => now()->subMonth(),
        'duration' => 120,
        'status' => 'completed',
    ]);
    $session->clientServices()->attach([$first->id, $second->id]);

    runBackfill();

    expect($first->contracts()->first()->usedHours())->toBe(1.0)
        ->and($second->contracts()->first()->usedHours())->toBe(1.0);
});

test('the backfilled allotment leaves room above what was already delivered', function () {
    $therapist = therapistUser();
    $service = legacyService($therapist);

    // 55 hours of history, which should round up past the 40-hour floor.
    foreach (range(1, 11) as $week) {
        $session = ScheduleSession::factory()->create([
            'client_id' => $service->client_id,
            'therapist_id' => $therapist->id,
            'scheduled_start' => now()->subWeeks($week),
            'duration' => 300,
            'status' => 'completed',
        ]);
        $session->clientServices()->attach($service->id);
    }

    runBackfill();

    $contract = $service->contracts()->first();

    expect($contract->usedHours())->toBe(55.0)
        ->and((float) $contract->allotted_hours)->toBe(60.0)
        ->and($contract->remainingHours())->toBe(5.0);
});

test('a service with no sessions still gets a contract, at the floor', function () {
    $therapist = therapistUser();
    $service = legacyService($therapist);

    runBackfill();

    $contract = $service->contracts()->first();

    expect($contract)->not->toBeNull()
        ->and((float) $contract->allotted_hours)->toBe(40.0)
        ->and($contract->status)->toBe(ServiceContract::STATUS_ACTIVE)
        ->and($contract->notes)->toBe('Backfilled at contract rollout.')
        ->and($contract->period_start->toDateString())->toBe(now()->subMonths(3)->toDateString());
});

test('the period is stretched to cover a session booked beyond the first year', function () {
    $therapist = therapistUser();
    $service = legacyService($therapist);

    $session = ScheduleSession::factory()->create([
        'client_id' => $service->client_id,
        'therapist_id' => $therapist->id,
        // Past `start_date` + one year, which the default window would miss.
        'scheduled_start' => now()->subMonths(3)->addMonths(15),
        'duration' => 60,
        'status' => 'scheduled',
    ]);
    $session->clientServices()->attach($service->id);

    runBackfill();

    expect($service->contracts()->first()->coversDate($session->scheduled_start))->toBeTrue();
});
