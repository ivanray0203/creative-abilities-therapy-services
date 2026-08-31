<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use App\Models\ServiceOffering;
use App\Models\User;

/**
 * Phase 20 — admin's authorization of an availed service, and the gate it
 * puts in front of scheduling.
 *
 * @see tasks/20-service-contracts-and-hour-budgets.md
 */
function availedService(?Client $client = null, ?User $therapist = null): ClientService
{
    $therapist ??= therapistUser();
    $client ??= Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    return ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        'service_id' => ServiceOffering::factory(),
    ]);
}

function contractPayload(array $overrides = []): array
{
    return [
        'allotted_hours' => 40,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        ...$overrides,
    ];
}

test('an admin issues a contract against one availed service', function () {
    $service = availedService();

    $this->actingAs(adminUser())
        ->post("/admin/clients/{$service->client_id}/services/{$service->id}/contracts", contractPayload())
        ->assertSessionHasNoErrors();

    $contract = ServiceContract::first();

    expect($contract->client_service_id)->toBe($service->id)
        ->and($contract->therapist_id)->toBe($service->therapist_id)
        ->and((float) $contract->allotted_hours)->toBe(40.0)
        ->and($contract->remainingHours())->toBe(40.0)
        ->and($contract->contract_number)->toStartWith('CON-'.now()->year.'-');
});

test('a therapist cannot issue a contract for themselves', function () {
    $therapist = therapistUser();
    $service = availedService(therapist: $therapist);

    // The admin route group redirects a therapist to their own home before
    // the form request is ever reached, which is the app's existing shape.
    $this->actingAs($therapist)
        ->post("/admin/clients/{$service->client_id}/services/{$service->id}/contracts", contractPayload())
        ->assertRedirect('/therapist');

    expect(ServiceContract::count())->toBe(0);
});

test('a contract cannot overlap another live one on the same service', function () {
    $service = availedService();
    $admin = adminUser();
    $url = "/admin/clients/{$service->client_id}/services/{$service->id}/contracts";

    $this->actingAs($admin)->post($url, contractPayload())->assertSessionHasNoErrors();

    // Shares the last day of the month with the contract just issued.
    $this->actingAs($admin)->post($url, contractPayload([
        'period_start' => now()->endOfMonth()->toDateString(),
        'period_end' => now()->addMonth()->endOfMonth()->toDateString(),
    ]))->assertSessionHasErrors('period_end');

    // Starting the day after is fine.
    $this->actingAs($admin)->post($url, contractPayload([
        'period_start' => now()->addMonth()->startOfMonth()->toDateString(),
        'period_end' => now()->addMonth()->endOfMonth()->toDateString(),
    ]))->assertSessionHasNoErrors();

    expect(ServiceContract::count())->toBe(2);
});

test('a cancelled contract does not block the period it used to cover', function () {
    $service = availedService();
    $contract = ServiceContract::factory()->for($service, 'clientService')->cancelled()->create();

    $this->actingAs(adminUser())
        ->post("/admin/clients/{$service->client_id}/services/{$service->id}/contracts", contractPayload())
        ->assertSessionHasNoErrors();

    expect(ServiceContract::count())->toBe(2)
        ->and($contract->refresh()->status)->toBe(ServiceContract::STATUS_CANCELLED);
});

test('a contract cannot end before it starts', function () {
    $service = availedService();

    $this->actingAs(adminUser())
        ->post("/admin/clients/{$service->client_id}/services/{$service->id}/contracts", contractPayload([
            'period_start' => now()->endOfMonth()->toDateString(),
            'period_end' => now()->startOfMonth()->toDateString(),
        ]))
        ->assertSessionHasErrors(['period_end' => 'A contract cannot end before it starts.']);
});

test('an allotment cannot be cut below the hours already drawn', function () {
    $therapist = therapistUser();
    $service = availedService(therapist: $therapist);
    $contract = ServiceContract::factory()->for($service, 'clientService')->create(['allotted_hours' => 40]);

    ScheduleSession::factory()->linkedTo($service, 6.0, $contract)->create([
        'client_id' => $service->client_id,
        'therapist_id' => $therapist->id,
        'status' => 'completed',
    ]);

    $url = "/admin/clients/{$service->client_id}/services/{$service->id}/contracts/{$contract->id}";

    $this->actingAs(adminUser())->put($url, contractPayload(['allotted_hours' => 4]))
        ->assertSessionHasErrors('allotted_hours');

    // Down to exactly what has been delivered is allowed.
    $this->actingAs(adminUser())->put($url, contractPayload(['allotted_hours' => 6]))
        ->assertSessionHasNoErrors();

    expect((float) $contract->refresh()->allotted_hours)->toBe(6.0)
        ->and($contract->remainingHours())->toBe(0.0);
});

test('a contract with sessions against it can be cancelled but not deleted', function () {
    $therapist = therapistUser();
    $service = availedService(therapist: $therapist);
    $contract = ServiceContract::factory()->for($service, 'clientService')->create();

    ScheduleSession::factory()->linkedTo($service, 1.0, $contract)->create([
        'client_id' => $service->client_id,
        'therapist_id' => $therapist->id,
    ]);

    $url = "/admin/clients/{$service->client_id}/services/{$service->id}/contracts/{$contract->id}";

    $this->actingAs(adminUser())->delete($url)->assertSessionHas('error');
    expect(ServiceContract::count())->toBe(1);

    $this->actingAs(adminUser())->post("{$url}/cancel", ['notes' => 'Funding withdrawn'])
        ->assertSessionHas('success');

    expect($contract->refresh()->status)->toBe(ServiceContract::STATUS_CANCELLED)
        ->and($contract->notes)->toBe('Funding withdrawn');
});

test('an unused contract can be deleted outright', function () {
    $service = availedService();
    $contract = ServiceContract::factory()->for($service, 'clientService')->create();

    $this->actingAs(adminUser())
        ->delete("/admin/clients/{$service->client_id}/services/{$service->id}/contracts/{$contract->id}")
        ->assertSessionHas('success');

    expect(ServiceContract::count())->toBe(0);
});

test('a contract cannot be reached through another client or another service', function () {
    $service = availedService();
    $contract = ServiceContract::factory()->for($service, 'clientService')->create();

    $stranger = Client::factory()->create();
    $otherService = availedService();

    $this->actingAs(adminUser())
        ->delete("/admin/clients/{$stranger->id}/services/{$service->id}/contracts/{$contract->id}")
        ->assertNotFound();

    $this->actingAs(adminUser())
        ->delete("/admin/clients/{$otherService->client_id}/services/{$otherService->id}/contracts/{$contract->id}")
        ->assertNotFound();

    expect(ServiceContract::count())->toBe(1);
});

test('cancelling a contract closes the service to new bookings', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 1));

    $service->contracts()->first()->update(['status' => ServiceContract::STATUS_CANCELLED]);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 0));
});

test('an expired contract is unbookable even with hours left on it', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 40]);

    $service->contracts()->first()->update([
        'period_start' => now()->subMonths(2)->startOfMonth()->toDateString(),
        'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
    ]);

    expect($service->contracts()->first()->remainingHours())->toBe(40.0);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 0));
});

test('a service cannot be moved to another therapist while a contract is live', function () {
    $therapist = therapistUser();
    $successor = therapistUser();
    $service = availedService(therapist: $therapist);
    ServiceContract::factory()->for($service, 'clientService')->create();

    $url = "/admin/clients/{$service->client_id}/services/{$service->id}";

    $this->actingAs(adminUser())->put($url, [
        'service_id' => $service->service_id,
        'therapist_id' => $successor->id,
    ])->assertSessionHas('error');

    expect($service->refresh()->therapist_id)->toBe($therapist->id);

    $service->contracts()->first()->update(['status' => ServiceContract::STATUS_CANCELLED]);

    $this->actingAs(adminUser())->put($url, [
        'service_id' => $service->service_id,
        'therapist_id' => $successor->id,
    ])->assertSessionHas('success');

    expect($service->refresh()->therapist_id)->toBe($successor->id);
});

test('the admin client page sends each contract with its live balance and status', function () {
    $therapist = therapistUser();
    $service = availedService(therapist: $therapist);

    $contract = ServiceContract::factory()->for($service, 'clientService')->create([
        'allotted_hours' => 10,
        // Ended yesterday, but the nightly sweep has not run yet, so the
        // column still says `active` while the truth is `expired`.
        'period_start' => now()->subMonth()->toDateString(),
        'period_end' => now()->subDay()->toDateString(),
        'status' => ServiceContract::STATUS_ACTIVE,
    ]);

    ScheduleSession::factory()->linkedTo($service, 2.5, $contract)->create([
        'client_id' => $service->client_id,
        'therapist_id' => $therapist->id,
        'status' => 'completed',
    ]);

    $this->actingAs(adminUser())->get("/admin/clients/{$service->client_id}")
        ->assertInertia(fn ($page) => $page
            ->has('client.client_services.0.contracts', 1)
            ->where('client.client_services.0.contracts.0.remaining_hours', 7.5)
            ->where('client.client_services.0.contracts.0.status', ServiceContract::STATUS_ACTIVE)
            ->where('client.client_services.0.contracts.0.derived_status', ServiceContract::STATUS_EXPIRED)
        );
});

test('the session form sends the covering contract balance with each service', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: ['allotted_hours' => 12]);

    ScheduleSession::factory()->linkedTo($service, 2.5, $service->contracts()->first())->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page
            ->where('clients.0.client_services.0.contract.allotted_hours', 12)
            ->where('clients.0.client_services.0.contract.remaining_hours', 9.5)
            // The raw contract rows were only a means to that summary and
            // have no business crossing the wire.
            ->missing('clients.0.client_services.0.contracts')
        );
});

test('the client page carries each service contract and what is left on it', function () {
    $therapist = therapistUser();
    $service = availedService(therapist: $therapist);

    $this->actingAs(adminUser())
        ->post("/admin/clients/{$service->client_id}/services/{$service->id}/contracts", contractPayload())
        ->assertSessionHasNoErrors();

    $availed = $service->client->refresh()->service_availed;

    expect($availed[0]['contracts'])->toHaveCount(1)
        ->and((float) $availed[0]['contracts'][0]['allotted_hours'])->toBe(40.0)
        ->and((float) $availed[0]['contracts'][0]['remaining_hours'])->toBe(40.0)
        ->and($availed[0]['contracts'][0]['status'])->toBe(ServiceContract::STATUS_ACTIVE);
});
