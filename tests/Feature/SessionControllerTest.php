<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;

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
        'duration' => '45 minutes',
        'notes' => 'First session',
    ])->assertSessionHasNoErrors();

    $session = ScheduleSession::first();
    expect($session)->not->toBeNull();
    expect($session->status)->toBe('scheduled');
    expect($session->scheduled_start->format('Y-m-d H:i'))->toBe('2026-08-10 14:30');
    expect($session->scheduled_end->format('Y-m-d H:i'))->toBe('2026-08-10 15:15');
});

test('a therapist creating a session is always scheduled as themselves, ignoring a spoofed therapist_id', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();
    $otherTherapist = therapistUser();

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'therapist_id' => $otherTherapist->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => '30 minutes',
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
        'duration' => '60 minutes',
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

    $matching = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'service_id' => $service->id,
        'linked_client_service_id' => $clientService->id,
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
    $matching = ScheduleSession::factory()->create(['client_id' => $client->id, 'linked_client_service_id' => $clientService->id]);
    ScheduleSession::factory()->create();

    $response = $this->actingAs(adminUser())->get("/admin/clients/{$client->id}/services/{$clientService->id}/sessions");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/clients/service-sessions')
        ->has('sessions.data', 1)
        ->where('sessions.data.0.id', $matching->id)
    );
});
