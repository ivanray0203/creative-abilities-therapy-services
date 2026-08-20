<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\User;

/**
 * Phase 18 — nothing previously stopped a therapist or a child being booked
 * into two overlapping sessions.
 *
 * @see tasks/18-scheduling-conflicts-review-queue-authorization.md
 */

/**
 * An existing 09:00–10:00 booking to schedule against.
 *
 * @return array{0: User, 1: Client, 2: ScheduleSession}
 */
function bookedNineToTen(): array
{
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    // Left unbooked, so the child stays offerable in the therapist's client
    // picker and the conflict rules are what's actually under test here.
    ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);

    $session = ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 09:00:00',
        'scheduled_end' => '2026-09-01 10:00:00',
        'status' => 'scheduled',
    ]);

    return [$therapist, $client, $session];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function sessionPayload(Client $client, User $therapist, array $overrides = []): array
{
    return array_merge([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'date' => '2026-09-01',
        'start_time' => '09:30',
        'end_time' => '10:30',
    ], $overrides);
}

test('a therapist cannot be booked into an overlapping session', function () {
    [$therapist, $client] = bookedNineToTen();
    $otherClient = Client::factory()->create();

    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($otherClient, $therapist))
        ->assertSessionHasErrors('therapist_id');

    expect(ScheduleSession::count())->toBe(1);
});

test('a client cannot be booked into an overlapping session', function () {
    [, $client] = bookedNineToTen();
    $otherTherapist = therapistUser();

    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($client, $otherTherapist))
        ->assertSessionHasErrors('client_id');

    expect(ScheduleSession::count())->toBe(1);
});

test('back-to-back sessions are allowed', function () {
    [$therapist, $client] = bookedNineToTen();

    // Starts exactly when the 09:00–10:00 booking ends.
    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($client, $therapist, ['start_time' => '10:00']))
        ->assertSessionHasNoErrors();

    expect(ScheduleSession::count())->toBe(2);
});

test('a session that merely surrounds the existing one still conflicts', function () {
    [$therapist, $client] = bookedNineToTen();

    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($client, $therapist, [
            'start_time' => '08:30',
            'end_time' => '10:30',
        ]))
        ->assertSessionHasErrors('therapist_id');
});

test('a cancelled session frees its slot', function () {
    [$therapist, $client, $session] = bookedNineToTen();
    $session->update(['status' => 'cancelled']);

    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($client, $therapist))
        ->assertSessionHasNoErrors();
});

test('a no-show session frees its slot', function () {
    [$therapist, $client, $session] = bookedNineToTen();
    $session->update(['status' => 'no_show']);

    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($client, $therapist))
        ->assertSessionHasNoErrors();
});

test('saving a session without moving it does not conflict with itself', function () {
    [$therapist, $client, $session] = bookedNineToTen();

    $this->actingAs(adminUser())
        ->put("/admin/sessions/{$session->id}", sessionPayload($client, $therapist, [
            'start_time' => '09:00',
            'notes' => 'Unchanged time, new note',
        ]))
        ->assertSessionHasNoErrors();

    expect($session->refresh()->notes)->toBe('Unchanged time, new note');
});

test('rescheduling onto another booking conflicts', function () {
    [$therapist, $client, $session] = bookedNineToTen();

    $later = ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 14:00:00',
        'scheduled_end' => '2026-09-01 15:00:00',
        'status' => 'scheduled',
    ]);

    $this->actingAs(adminUser())
        ->put("/admin/sessions/{$session->id}", sessionPayload($client, $therapist, [
            'start_time' => '14:30',
            'end_time' => '15:30',
        ]))
        ->assertSessionHasErrors('therapist_id');

    expect($later->refresh()->scheduled_start->format('H:i'))->toBe('14:00');
});

test('a therapist scheduling for themselves is checked against their own diary', function () {
    // Therapists never send a usable therapist_id — the controller forces
    // their own — so the conflict check must resolve it the same way.
    [$therapist, $client] = bookedNineToTen();

    $this->actingAs($therapist)
        ->post('/therapist/sessions', [
            'client_id' => $client->id,
            'therapist_id' => therapistUser()->id, // spoofed, ignored
            'date' => '2026-09-01',
            'start_time' => '09:30',
            'end_time' => '10:30',
        ])
        ->assertSessionHasErrors('therapist_id');

    expect(ScheduleSession::count())->toBe(1);
});

test('the clash is worded at whoever is booking', function () {
    [$therapist, $client] = bookedNineToTen();

    $clash = [
        'client_id' => $client->id,
        'date' => '2026-09-01',
        'start_time' => '09:30',
        'end_time' => '10:30',
    ];

    // The therapist reads about their own diary, not a third party's.
    $this->actingAs($therapist)
        ->post('/therapist/sessions', $clash)
        ->assertSessionHasErrors(['therapist_id' => 'You already have a session booked from 9:00 AM to 10:00 AM.']);

    $this->actingAs(adminUser())
        ->post('/admin/sessions', [...$clash, 'therapist_id' => $therapist->id])
        ->assertSessionHasErrors(['therapist_id' => 'This therapist already has a session booked from 9:00 AM to 10:00 AM.']);
});

test('a different therapist and child at the same time is fine', function () {
    bookedNineToTen();

    $otherTherapist = therapistUser();
    $otherClient = Client::factory()->create(['primary_therapist_id' => $otherTherapist->id]);

    $this->actingAs(adminUser())
        ->post('/admin/sessions', sessionPayload($otherClient, $otherTherapist))
        ->assertSessionHasNoErrors();

    expect(ScheduleSession::count())->toBe(2);
});
