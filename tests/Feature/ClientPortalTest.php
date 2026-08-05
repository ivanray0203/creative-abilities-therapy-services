<?php

use App\Models\Complaint;
use App\Models\ScheduleSession;

test('the calendar page returns only this client\'s sessions', function () {
    $client = clientWithUser();
    $own = ScheduleSession::factory()->create(['client_id' => $client->id]);
    ScheduleSession::factory()->create(['client_id' => clientWithUser()->id]);

    $response = $this->actingAs($client->user)->get('/client/calendar');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('client/calendar')
        ->has('sessions', 1)
        ->where('sessions.0.id', $own->id)
    );
});

test('a client can verify their own pending session', function () {
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id, 'status' => 'pending']);

    $this->actingAs($client->user)
        ->post("/client/sessions/{$session->id}/verify")
        ->assertSessionHasNoErrors();

    expect($session->refresh()->status)->toBe('confirmed');
});

test('a client cannot verify another client\'s session', function () {
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create(['status' => 'pending']);

    $this->actingAs($client->user)
        ->post("/client/sessions/{$session->id}/verify")
        ->assertNotFound();

    expect($session->refresh()->status)->toBe('pending');
});

test('a client disputing their own session updates its status and creates a linked complaint', function () {
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id, 'status' => 'pending']);

    $this->actingAs($client->user)
        ->post("/client/sessions/{$session->id}/dispute", ['dispute_reason' => 'Therapist never arrived'])
        ->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->status)->toBe('disputed');
    expect($session->dispute_reason)->toBe('Therapist never arrived');

    $complaint = Complaint::where('session_id', $session->id)->first();
    expect($complaint)->not->toBeNull();
    expect($complaint->type)->toBe('disputes');
    expect($complaint->complained_by)->toBe('client');
    expect($complaint->client_id)->toBe($client->id);
});

test('a client cannot dispute another client\'s session', function () {
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create(['status' => 'pending']);

    $this->actingAs($client->user)
        ->post("/client/sessions/{$session->id}/dispute", ['dispute_reason' => 'Not mine'])
        ->assertNotFound();

    expect($session->refresh()->status)->toBe('pending');
});

test('a client can view their profile with the email field left out of the editable payload', function () {
    $client = clientWithUser();
    $client->originalIntake->update(['primary_parent_email' => 'parent@example.com']);

    $response = $this->actingAs($client->user)->get('/client/profile');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('client/profile')
        ->where('client.original_intake.primary_parent_email', 'parent@example.com')
    );
});

test('updating the profile persists intake fields but cannot change the email', function () {
    $client = clientWithUser();
    $client->originalIntake->update(['primary_parent_email' => 'parent@example.com']);

    $this->actingAs($client->user)->put('/client/profile', [
        'child_first_name' => 'Riley',
        'child_last_name' => 'Doe',
        'primary_parent_phone' => '555-0100',
        'emergency_contact_name' => 'Jamie Doe',
        'emergency_contact_phone' => '555-0199',
        'primary_parent_email' => 'attacker@example.com',
    ])->assertSessionHasNoErrors();

    $intake = $client->originalIntake->refresh();
    expect($intake->child_first_name)->toBe('Riley');
    expect($intake->primary_parent_phone)->toBe('555-0100');
    expect($intake->primary_parent_email)->toBe('parent@example.com');
});
