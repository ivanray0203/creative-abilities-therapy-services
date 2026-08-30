<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use App\Models\User;

/**
 * Phase 20 — the hours side of a contract: drawn at booking for the length
 * the session was booked for, released on cancel or no-show. What the clock
 * says while the session runs never moves the balance.
 *
 * @see tasks/20-service-contracts-and-hour-budgets.md
 */

/**
 * Book a session through the therapist form so the draw goes through the
 * ledger rather than being written straight to the pivot.
 *
 * @param  array<int, int>  $clientServiceIds
 */
function bookSession(User $therapist, Client $client, array $clientServiceIds, string $from, string $to, int $daysAhead = 1): ScheduleSession
{
    test()->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_services' => array_map(
            fn (int $id): array => ['client_service_id' => $id],
            $clientServiceIds,
        ),
        'date' => now()->addDays($daysAhead)->toDateString(),
        'start_time' => $from,
        'end_time' => $to,
    ])->assertSessionHasNoErrors();

    return ScheduleSession::query()->latest('id')->first();
}

test('an even split puts the rounding remainder on the last service', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    $services = collect(range(1, 3))->map(fn (): ClientService => contractedService($client, $therapist));

    $session = bookSession($therapist, $client, $services->pluck('id')->all(), '09:00', '11:00');

    $hours = $session->clientServices->sortBy('id')
        ->map(fn ($service): float => (float) $service->pivot->hours)
        ->values()
        ->all();

    // Two hours over three services: 0.67 + 0.67 + 0.66, which still totals
    // exactly two rather than 2.01.
    expect($hours)->toBe([0.67, 0.67, 0.66])
        ->and(array_sum($hours))->toBe(2.0);
});

test('ending a session short still draws the hours it was booked for', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 10]);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '11:00');

    expect($service->contracts()->first()->remainingHours())->toBe(8.0);

    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/start");
    $this->travel(90)->minutes();
    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/end");

    // The contract pays for booked time, so finishing early hands nothing
    // back. An admin who wants the half hour returned shortens the session.
    expect((float) $session->fresh()->clientServices->first()->pivot->hours)->toBe(2.0)
        ->and($service->contracts()->first()->remainingHours())->toBe(8.0);
});

test('a session that runs over draws no more than it was booked for', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 1]);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '10:00');

    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/start");
    $this->travel(120)->minutes();
    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/end");

    $contract = $service->contracts()->first();

    // An overrun cannot overdraw the pool: the balance never goes negative,
    // and the hour that was authorized is the hour that comes off.
    expect((float) $session->fresh()->clientServices->first()->pivot->hours)->toBe(1.0)
        ->and($contract->remainingHours())->toBe(0.0)
        ->and($contract->derivedStatus())->toBe(ServiceContract::STATUS_EXHAUSTED);
});

test('the clock running does not move the balance at all', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 10]);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '11:00');

    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/start");
    $this->travel(37)->minutes();
    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/end");

    // `elapsed_time` is still recorded for timesheets and for the session
    // history; it simply has no say over the contract.
    expect($session->fresh()->elapsed_time)->toBe('00:37:00')
        ->and($service->contracts()->first()->remainingHours())->toBe(8.0);
});

test('a session ended without ever being started keeps the hours it booked', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 10]);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '11:00');

    $this->actingAs($therapist)->post("/therapist/sessions/{$session->id}/end");

    expect((float) $session->fresh()->clientServices->first()->pivot->hours)->toBe(2.0)
        ->and($service->contracts()->first()->remainingHours())->toBe(8.0);
});

test('cancelling, missing or deleting a session all return its hours', function (string $action) {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 10]);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '11:00');

    expect($service->contracts()->first()->remainingHours())->toBe(8.0);

    match ($action) {
        'cancelled' => $this->actingAs($therapist)
            ->post("/therapist/sessions/{$session->id}/cancel", ['cancel_reason' => 'Child unwell'])
            ->assertSessionHasNoErrors(),
        'no_show' => $session->update(['status' => 'no_show']),
        'deleted' => $this->actingAs($therapist)->delete("/therapist/sessions/{$session->id}"),
    };

    expect($service->contracts()->first()->remainingHours())->toBe(10.0);
})->with(['cancelled', 'no_show', 'deleted']);

test('a cancelled session keeps its ledger row so the history still reads', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '10:00');

    $this->actingAs($therapist)
        ->post("/therapist/sessions/{$session->id}/cancel", ['cancel_reason' => 'Child unwell']);

    expect($session->fresh()->clientServices)->toHaveCount(1)
        ->and((float) $session->fresh()->clientServices->first()->pivot->hours)->toBe(1.0);
});

test('each service draws only from its own contract', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $first = contractedService($client, $therapist, contract: ['allotted_hours' => 10]);
    $second = contractedService($client, $therapist, contract: ['allotted_hours' => 4]);

    bookSession($therapist, $client, [$first->id, $second->id], '09:00', '11:00');

    expect($first->contracts()->first()->remainingHours())->toBe(9.0)
        ->and($second->contracts()->first()->remainingHours())->toBe(3.0);
});

test('editing a session does not fail the balance against its own hours', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    // Exactly one session's worth: an edit that ignored its own draw would
    // see nothing left and refuse to save.
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 2]);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '11:00');

    $this->actingAs($therapist)->put("/therapist/sessions/{$session->id}", [
        'client_id' => $client->id,
        'linked_client_services' => [['client_service_id' => $service->id]],
        'date' => now()->addDays(2)->toDateString(),
        'start_time' => '13:00',
        'end_time' => '15:00',
    ])->assertSessionHasNoErrors();

    expect($service->contracts()->first()->remainingHours())->toBe(0.0)
        ->and($session->fresh()->scheduled_start->format('H:i'))->toBe('13:00');
});

test('a session records which contract it drew from', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist);

    $session = bookSession($therapist, $client, [$service->id], '09:00', '10:00');

    expect($session->clientServices->first()->pivot->service_contract_id)
        ->toBe($service->contracts()->first()->id);
});
