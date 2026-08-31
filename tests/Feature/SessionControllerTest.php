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

test('creating a session computes scheduled_start and scheduled_end from date, start_time, and end_time', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();

    $this->actingAs(adminUser())->post('/admin/sessions', [
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'location' => 'Clinic',
        'date' => '2026-08-10',
        'start_time' => '14:30',
        'end_time' => '15:15',
        'notes' => 'First session',
    ])->assertSessionHasNoErrors();

    $session = ScheduleSession::first();
    expect($session)->not->toBeNull();
    expect($session->status)->toBe('scheduled');
    expect($session->scheduled_start->format('Y-m-d H:i'))->toBe('2026-08-10 14:30');
    expect($session->scheduled_end->format('Y-m-d H:i'))->toBe('2026-08-10 15:15');
    // `duration` is no longer posted, but is still derived and stored for
    // ClientProgress and the session emails.
    expect($session->duration)->toBe(45);
});

test('the end time must come after the start time', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post('/admin/sessions', [
        'client_id' => $client->id,
        'therapist_id' => therapistUser()->id,
        'date' => '2026-08-10',
        'start_time' => '14:30',
        'end_time' => '13:30',
    ])->assertSessionHasErrors('end_time');

    expect(ScheduleSession::count())->toBe(0);
});

test('the booked window still has to fall between 5 minutes and 8 hours', function (string $endTime) {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post('/admin/sessions', [
        'client_id' => $client->id,
        'therapist_id' => therapistUser()->id,
        'date' => '2026-08-10',
        'start_time' => '08:00',
        'end_time' => $endTime,
    ])->assertSessionHasErrors('end_time');

    expect(ScheduleSession::count())->toBe(0);
})->with([
    'under five minutes' => '08:03',
    'over eight hours' => '16:30',
]);

test('a therapist creating a session is always scheduled as themselves, ignoring a spoofed therapist_id', function () {
    $therapist = therapistUser();
    $otherTherapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    contractedService($client, $therapist);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'therapist_id' => $otherTherapist->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
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
        'end_time' => '11:00',
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

test('a therapist only sees clients holding a contracted service of their own', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();

    $client = Client::factory()->create(['primary_therapist_id' => $therapistA->id]);
    $client->careTeam()->attach($therapistB->id);

    // Assigned to therapist A, but admin has authorized nothing against it.
    ClientService::factory()->for($client)->create(['therapist_id' => $therapistA->id]);

    contractedService($client, $therapistB);

    $this->actingAs($therapistA)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 0));

    $this->actingAs($therapistB)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.id', $client->id)
        );
});

test('a booked service stays on offer until its contract hours run out, and cancelling gives them back', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $clientService = contractedService($client, $therapist, contract: ['allotted_hours' => 2]);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 1));

    // One hour of the two. Under the old rule this alone retired the
    // service; under a contract it has half its pool left.
    $session = ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients', 1));

    ScheduleSession::factory()->linkedTo($clientService)->create([
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
    $clientService = contractedService($client, $therapist);
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

test('a therapist cannot schedule for a caseload client with no contracted service of theirs', function () {
    $therapist = therapistUser();
    $otherTherapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    ClientService::factory()->for($client)->create(['therapist_id' => $otherTherapist->id]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
    ])->assertSessionHasErrors('client_id');

    expect(ScheduleSession::count())->toBe(0);
});

test('a therapist session takes its service from the availed service it is linked to', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = ServiceOffering::factory()->create();
    $clientService = contractedService($client, $therapist, ['service_id' => $service->id]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_services' => [['client_service_id' => $clientService->id]],
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
    ])->assertSessionHasNoErrors();

    expect(ScheduleSession::first()->service_id)->toBe($service->id);
});

test('one session covering several availed services splits its hours between their contracts', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $first = contractedService($client, $therapist);
    $second = contractedService($client, $therapist);
    contractedService($client, $therapist);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_services' => [
            ['client_service_id' => $first->id],
            ['client_service_id' => $second->id],
        ],
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ])->assertSessionHasNoErrors();

    $session = ScheduleSession::first();
    expect($session->clientServices->pluck('id')->sort()->values()->all())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all())
        // An hour across two services, so half each, with no figure posted.
        ->and((float) $session->clientServices->firstWhere('id', $first->id)->pivot->hours)->toBe(0.5)
        ->and((float) $session->clientServices->firstWhere('id', $second->id)->pivot->hours)->toBe(0.5)
        ->and($first->contracts()->first()->remainingHours())->toBe(39.5);

    // All three still have hours, so all three stay on offer — the old rule
    // would have retired the two just booked.
    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->has('clients.0.client_services', 3)
        );
});

test('a therapist may set the split themselves, and it has to add up to the visit', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $first = contractedService($client, $therapist);
    $second = contractedService($client, $therapist);

    $post = fn (float $firstHours, float $secondHours) => $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_services' => [
            ['client_service_id' => $first->id, 'hours' => $firstHours],
            ['client_service_id' => $second->id, 'hours' => $secondHours],
        ],
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);

    $post(1.5, 0.25)->assertSessionHasErrors('linked_client_services');
    expect(ScheduleSession::count())->toBe(0);

    $post(1.5, 0.5)->assertSessionHasNoErrors();

    $session = ScheduleSession::first();
    expect((float) $session->clientServices->firstWhere('id', $first->id)->pivot->hours)->toBe(1.5)
        ->and((float) $session->clientServices->firstWhere('id', $second->id)->pivot->hours)->toBe(0.5);
});

test('a service can be booked repeatedly until its contract empties, then is refused', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $clientService = contractedService($client, $therapist, contract: ['allotted_hours' => 1.5]);

    $book = fn (int $daysAhead, string $from, string $to) => $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_services' => [['client_service_id' => $clientService->id]],
        'date' => now()->addDays($daysAhead)->toDateString(),
        'start_time' => $from,
        'end_time' => $to,
    ]);

    $book(1, '09:00', '10:00')->assertSessionHasNoErrors();
    // Exactly to the allotment, which is allowed.
    $book(2, '09:00', '09:30')->assertSessionHasNoErrors();

    $book(3, '09:00', '09:30')
        ->assertSessionHasErrors('linked_client_services');

    expect(ScheduleSession::count())->toBe(2)
        ->and($clientService->contracts()->first()->remainingHours())->toBe(0.0);
});

test('a session dated outside its contract period is refused', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $clientService = contractedService($client, $therapist, contract: [
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'linked_client_services' => [['client_service_id' => $clientService->id]],
        'date' => now()->addMonth()->startOfMonth()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ])->assertSessionHasErrors('linked_client_services');

    expect(ScheduleSession::count())->toBe(0);
});

test('rescheduling keeps the services the session already covers and can drop one', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $first = contractedService($client, $therapist);
    $second = contractedService($client, $therapist);

    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);
    $session->clientServices()->sync([
        $first->id => ['hours' => 0.5, 'service_contract_id' => $first->contracts()->first()->id],
        $second->id => ['hours' => 0.5, 'service_contract_id' => $second->contracts()->first()->id],
    ]);

    $this->actingAs($therapist)->put("/therapist/sessions/{$session->id}", [
        'client_id' => $client->id,
        'linked_client_services' => [['client_service_id' => $first->id]],
        'date' => now()->addDays(3)->toDateString(),
        'start_time' => '11:00',
        'end_time' => '12:00',
    ])->assertSessionHasNoErrors();

    expect($session->fresh()->clientServices->pluck('id')->all())->toBe([$first->id])
        // The whole hour now sits on the one service left.
        ->and((float) $session->fresh()->clientServices->first()->pivot->hours)->toBe(1.0)
        // Dropped from the session, the second service gets its half hour back.
        ->and($second->contracts()->first()->remainingHours())->toBe(40.0);

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertInertia(fn ($page) => $page->has('clients.0.client_services', 2));
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
