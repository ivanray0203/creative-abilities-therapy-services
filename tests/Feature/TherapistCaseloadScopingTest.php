<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;

/**
 * Phase 17, related bug A — the session form used to offer every client in
 * the system to any therapist, and accepted whatever `client_id` was posted.
 *
 * @see tasks/17-multi-child-client-model.md
 */
test('the session form only offers clients on the therapist\'s own caseload', function () {
    $therapist = therapistUser();

    $primary = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $assigned = Client::factory()->create(['assigned_therapist_id' => $therapist->id]);
    $careTeam = Client::factory()->create();
    $careTeam->careTeam()->attach($therapist->id);

    foreach ([$primary, $assigned, $careTeam] as $client) {
        ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);
    }

    Client::factory()->create(); // another therapist's client

    $this->actingAs($therapist)->get('/therapist/sessions/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sessions/create')
            ->has('clients', 3)
            ->where(
                'clients',
                fn ($clients) => collect($clients)->pluck('id')->sort()->values()->all()
                    === collect([$primary->id, $assigned->id, $careTeam->id])->sort()->values()->all(),
            )
        );
});

test('an admin still sees every client in the session form', function () {
    Client::factory()->count(3)->create();

    $this->actingAs(adminUser())->get('/admin/sessions/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clients', 3));
});

test('a therapist cannot schedule for a client outside their caseload', function () {
    $therapist = therapistUser();
    $stranger = Client::factory()->create();

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $stranger->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasErrors('client_id');

    expect(ScheduleSession::count())->toBe(0);
});

test('a therapist cannot reschedule a session onto a client outside their caseload', function () {
    $therapist = therapistUser();
    $own = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $stranger = Client::factory()->create();

    $session = ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'client_id' => $own->id,
    ]);

    $this->actingAs($therapist)->put("/therapist/sessions/{$session->id}", [
        'client_id' => $stranger->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasErrors('client_id');

    expect($session->refresh()->client_id)->toBe($own->id);
});

test('a therapist can schedule for a client on their caseload', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration' => 30,
    ])->assertSessionHasNoErrors();

    expect(ScheduleSession::first()->client_id)->toBe($client->id);
});
