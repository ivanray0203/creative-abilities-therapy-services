<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;

/**
 * Phase 20 — `contracts:sweep` keeps the cached status honest. The booking
 * gate never reads it, so nothing breaks while it is stale; lists and badges
 * do, so it should not stay stale for long.
 *
 * @see tasks/20-service-contracts-and-hour-budgets.md
 */
test('the sweep marks a contract expired once its period has passed', function () {
    $contract = ServiceContract::factory()->create([
        'period_start' => now()->subMonths(2)->startOfMonth()->toDateString(),
        'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        'status' => ServiceContract::STATUS_ACTIVE,
    ]);

    $this->artisan('contracts:sweep')->assertSuccessful();

    expect($contract->refresh()->status)->toBe(ServiceContract::STATUS_EXPIRED);
});

test('the sweep marks a contract exhausted once its hours are gone', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    $contract = ServiceContract::factory()->for($service, 'clientService')->create(['allotted_hours' => 2]);

    ScheduleSession::factory()->linkedTo($service, 2.0, $contract)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'completed',
    ]);

    $this->artisan('contracts:sweep')->assertSuccessful();

    expect($contract->refresh()->status)->toBe(ServiceContract::STATUS_EXHAUSTED);
});

test('the sweep leaves a live contract and a cancelled one alone', function () {
    $live = ServiceContract::factory()->create();
    $cancelled = ServiceContract::factory()->expired()->create([
        'status' => ServiceContract::STATUS_CANCELLED,
    ]);

    $this->artisan('contracts:sweep')->assertSuccessful();

    // Cancelling is an admin's decision, so an expired period does not
    // overwrite it.
    expect($live->refresh()->status)->toBe(ServiceContract::STATUS_ACTIVE)
        ->and($cancelled->refresh()->status)->toBe(ServiceContract::STATUS_CANCELLED);
});

test('a contract whose hours came back is marked active again', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    $contract = ServiceContract::factory()->for($service, 'clientService')->create([
        'allotted_hours' => 2,
        'status' => ServiceContract::STATUS_EXHAUSTED,
    ]);

    $session = ScheduleSession::factory()->linkedTo($service, 2.0, $contract)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    $session->update(['status' => 'cancelled']);

    $this->artisan('contracts:sweep')->assertSuccessful();

    expect($contract->refresh()->status)->toBe(ServiceContract::STATUS_ACTIVE);
});
