<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\User;

test('admin sees every session, therapist only sees their own', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();
    ScheduleSession::factory()->create(['therapist_id' => $therapistA->id]);
    ScheduleSession::factory()->create(['therapist_id' => $therapistB->id]);

    $adminResponse = $this->actingAs(adminUser())->get('/admin/sessions');
    $adminResponse->assertOk();
    $adminResponse->assertInertia(fn ($page) => $page
        ->component('sessions/index')
        ->where('isAdmin', true)
        ->has('sessions.data', 2)
    );

    $therapistResponse = $this->actingAs($therapistA)->get('/therapist/calendar');
    $therapistResponse->assertOk();
    $therapistResponse->assertInertia(fn ($page) => $page
        ->component('therapist/calendar')
        ->has('sessions', 1)
        ->where('sessions.0.therapist_id', $therapistA->id)
    );
});

test('quick filters and stats scope correctly', function () {
    $therapist = therapistUser();
    ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'scheduled_start' => now(), 'scheduled_end' => now()->addHour()]);
    ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'status' => 'disputed', 'scheduled_start' => now()->addDays(3)]);

    $response = $this->actingAs(adminUser())->get('/admin/sessions?quick=disputed');

    $response->assertInertia(fn ($page) => $page
        ->has('sessions.data', 1)
        ->where('stats.total', 2)
        ->where('stats.disputed', 1)
    );
});

test('creating a session computes scheduled_start and scheduled_end from date, start_time, and duration', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();

    $this->actingAs(adminUser())->post('/admin/sessions', [
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'location' => 'Clinic',
        'date' => '2026-08-10',
        'start_time' => '14:30',
        'duration' => 45,
        'notes' => 'First session',
    ])->assertSessionHasNoErrors();

    $session = ScheduleSession::first();
    expect($session)->not->toBeNull();
    expect($session->status)->toBe('scheduled');
    expect($session->scheduled_start->format('Y-m-d H:i'))->toBe('2026-08-10 14:30');
    expect($session->scheduled_end->format('Y-m-d H:i'))->toBe('2026-08-10 15:15');
});

test('a therapist creating a session is always scheduled as themselves, ignoring a spoofed therapist_id', function () {
    $therapist = therapistUser();
    $otherTherapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'therapist_id' => $otherTherapist->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/therapist/sessions');

    expect(ScheduleSession::first()->therapist_id)->toBe($therapist->id);
});

test('a therapist can view their own session list', function () {
    $therapist = therapistUser();
    ScheduleSession::factory()->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->get('/therapist/sessions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sessions/index')
            ->where('isAdmin', false)
            ->has('sessions.data', 1)
        );
});

test('rescheduling a session updates the computed times', function () {
    $session = ScheduleSession::factory()->create();

    $this->actingAs(adminUser())->put("/admin/sessions/{$session->id}", [
        'client_id' => $session->client_id,
        'therapist_id' => $session->therapist_id,
        'date' => '2026-09-01',
        'start_time' => '10:00',
        'duration' => 60,
    ])->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->scheduled_start->format('Y-m-d H:i'))->toBe('2026-09-01 10:00');
});

test('a therapist cannot edit, cancel, or delete another therapist\'s session', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $session = ScheduleSession::factory()->create(['therapist_id' => $owner->id]);

    $this->actingAs($intruder)->get("/therapist/sessions/{$session->id}/edit")->assertNotFound();
    $this->actingAs($intruder)->post("/therapist/sessions/{$session->id}/cancel", ['cancel_reason' => 'nope'])->assertNotFound();
    $this->actingAs($intruder)->delete("/therapist/sessions/{$session->id}")->assertNotFound();
});

test('cancelling a session records the reason', function () {
    $session = ScheduleSession::factory()->create();

    $this->actingAs(adminUser())->post("/admin/sessions/{$session->id}/cancel", [
        'cancel_reason' => 'Client is sick',
    ])->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->status)->toBe('cancelled');
    expect($session->cancel_reason)->toBe('Client is sick');
});

test('disputing a session updates its status and creates a linked complaint', function () {
    $session = ScheduleSession::factory()->create();

    $this->actingAs(adminUser())->post("/admin/sessions/{$session->id}/dispute", [
        'dispute_reason' => 'Session did not happen as scheduled',
    ])->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->status)->toBe('disputed');
    expect($session->dispute_reason)->toBe('Session did not happen as scheduled');

    $complaint = Complaint::where('session_id', $session->id)->first();
    expect($complaint)->not->toBeNull();
    expect($complaint->type)->toBe('disputes');
    expect($complaint->client_id)->toBe($session->client_id);
});

test('a session can be deleted', function () {
    $session = ScheduleSession::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/sessions/{$session->id}")->assertSessionHasNoErrors();

    expect(ScheduleSession::find($session->id))->toBeNull();
});

test('by-user, by-user-and-service, by-client-service, and by-therapist endpoints scope correctly', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();
    $service = ServiceOffering::factory()->create();
    $clientService = ClientService::factory()->for($client)->create();

    $matching = ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'service_id' => $service->id,
    ]);
    ScheduleSession::factory()->create();

    $admin = adminUser();

    $this->actingAs($admin)->getJson("/admin/sessions/by-user?user_id={$client->id}&role=client")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $matching->id]);

    $this->actingAs($admin)->getJson("/admin/sessions/by-user-and-service?user_id={$client->id}&service_id={$service->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $matching->id]);

    $this->actingAs($admin)->getJson("/admin/sessions/by-client-service/{$clientService->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $matching->id]);

    $this->actingAs($admin)->getJson("/admin/sessions/therapist/{$therapist->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $matching->id]);
});

test('the client-service sessions page renders scoped and filtered sessions', function () {
    $client = Client::factory()->create();
    $clientService = ClientService::factory()->for($client)->create();
    $matching = ScheduleSession::factory()->linkedTo($clientService)->create(['client_id' => $client->id]);
    ScheduleSession::factory()->create();

    $response = $this->actingAs(adminUser())->get("/admin/clients/{$client->id}/services/{$clientService->id}/sessions");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/clients/service-sessions')
        ->has('sessions.data', 1)
        ->where('sessions.data.0.id', $matching->id)
    );
});

test('a therapist only sees clients whose availed services of theirs are still awaiting a booking', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();

    $client = Client::factory()->create(['primary_therapist_id' => $therapistA->id]);
    $client->careTeam()->attach($therapistB->id);

    $doneService = ClientService::factory()->for($client)->create(['therapist_id' => $therapistA->id]);
    ScheduleSession::factory()->linkedTo($doneService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapistA->id,
        'status' => 'completed',
    ]);

    ClientService::factory()->for($client)->create(['therapist_id' => $therapistB->id]);

    $this->actingAs($therapistA)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 0));

    $this->actingAs($therapistB)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.id', $client->id)
        );
});

test('a client drops off the picker once the therapist has scheduled their remaining service, and returns if it is cancelled', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $clientService = ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 1));

    $session = ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 0));

    $session->update(['status' => 'cancelled']);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 1));
});

test('the session being edited keeps its client and linked service selectable', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $clientService = ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);
    $session = ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($therapist)->get("/therapist/sessions/{$session->id}/edit")
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.id', $client->id)
            ->has('clients.0.client_services', 1)
            ->where('clients.0.client_services.0.id', $clientService->id)
        );
});

test('a therapist cannot schedule for a caseload client with no service of theirs left to book', function () {
    $therapist = therapistUser();
    $otherTherapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    ClientService::factory()->for($client)->create(['therapist_id' => $otherTherapist->id]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasErrors('client_id');

    expect(ScheduleSession::count())->toBe(0);
});

test('a therapist session takes its service from the availed service it is linked to', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = ServiceOffering::factory()->create();
    $clientService = ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        'service_id' => $service->id,
    ]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_service_ids' => [$clientService->id],
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasNoErrors();

    expect(ScheduleSession::first()->service_id)->toBe($service->id);
});

test('one session can cover several availed services, leaving only the unbooked ones on offer', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    [$first, $second, $third] = ClientService::factory()
        ->count(3)
        ->for($client)
        ->create(['therapist_id' => $therapist->id])
        ->all();

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_service_ids' => [$first->id, $second->id],
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasNoErrors();

    $session = ScheduleSession::first();
    expect($session->clientServices->pluck('id')->sort()->values()->all())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all());

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->has('clients.0.client_services', 1)
            ->where('clients.0.client_services.0.id', $third->id)
        );
});

test('an availed service already booked cannot be scheduled a second time', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $booked = ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);
    $free = ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    ScheduleSession::factory()->linkedTo($booked)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_service_ids' => [$free->id, $booked->id],
        'date' => now()->addDays(2)->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasErrors('linked_client_service_ids');

    expect(ScheduleSession::count())->toBe(1);
});

test('rescheduling keeps the services the session already covers and can drop one', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    [$first, $second] = ClientService::factory()
        ->count(2)
        ->for($client)
        ->create(['therapist_id' => $therapist->id])
        ->all();

    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);
    $session->clientServices()->sync([$first->id, $second->id]);

    $this->actingAs($therapist)->put("/therapist/sessions/{$session->id}", [
        'client_id' => $client->id,
        'linked_client_service_ids' => [$first->id],
        'date' => now()->addDays(3)->toDateString(),
        'start_time' => '11:00',
        'duration' => 60,
    ])->assertSessionHasNoErrors();

    expect($session->fresh()->clientServices->pluck('id')->all())->toBe([$first->id]);

    // Dropped from the session, the second service is bookable again.
    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page
            ->has('clients.0.client_services', 1)
            ->where('clients.0.client_services.0.id', $second->id)
        );
});

test('a session lists every availed service it covers, for both the therapist list and the client calendar', function () {
    $therapist = therapistUser();
    $clientUser = User::factory()->create(['role' => 'client']);
    $client = Client::factory()->create([
        'primary_therapist_id' => $therapist->id,
        'user_id' => $clientUser->id,
    ]);

    $speech = ServiceOffering::factory()->create(['name' => 'Speech Therapy']);
    $occupational = ServiceOffering::factory()->create(['name' => 'Occupational Therapy']);

    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
    ]);
    $session->clientServices()->sync([
        ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id, 'service_id' => $speech->id])->id,
        ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id, 'service_id' => $occupational->id])->id,
    ]);

    $this->actingAs($therapist)->get('/therapist/sessions')
        ->assertInertia(fn ($page) => $page
            ->has('sessions.data', 1)
            ->has('sessions.data.0.client_services', 2)
            ->where('sessions.data.0.client_services.0.service.name', 'Speech Therapy')
            ->where('sessions.data.0.client_services.1.service.name', 'Occupational Therapy')
        );

    $this->actingAs($clientUser)->get('/client/calendar')
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 1)
            ->has('sessions.0.client_services', 2)
        );
});
