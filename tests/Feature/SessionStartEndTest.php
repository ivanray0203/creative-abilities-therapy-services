<?php

use App\Models\ScheduleSession;

test('starting a session sets it in progress and records the start time', function () {
    $therapist = therapistUser();
    $session = ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'status' => 'scheduled']);

    $this->actingAs($therapist)
        ->post("/therapist/sessions/{$session->id}/start")
        ->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->status)->toBe('inprogress');
    expect($session->start_time)->not->toBeNull();
});

test('a therapist cannot start a second session while one is already in progress', function () {
    $therapist = therapistUser();
    ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'status' => 'inprogress']);
    $other = ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'status' => 'scheduled']);

    $this->actingAs($therapist)
        ->post("/therapist/sessions/{$other->id}/start")
        ->assertSessionHasErrors('session');

    expect($other->refresh()->status)->toBe('scheduled');
});

test('a therapist cannot start or end another therapist\'s session', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $session = ScheduleSession::factory()->create(['therapist_id' => $owner->id, 'status' => 'scheduled']);

    $this->actingAs($intruder)->post("/therapist/sessions/{$session->id}/start")->assertNotFound();
    $this->actingAs($intruder)->post("/therapist/sessions/{$session->id}/end")->assertNotFound();
});

test('ending a session computes elapsed time from the recorded start time and stores notes', function () {
    $therapist = therapistUser();
    $session = ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'status' => 'inprogress',
        'start_time' => now()->subMinutes(45),
    ]);

    $this->actingAs($therapist)
        ->post("/therapist/sessions/{$session->id}/end", ['notes' => 'Went well'])
        ->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->status)->toBe('pending');
    expect($session->end_time)->not->toBeNull();
    expect($session->elapsed_time)->toBe('00:45:00');
    expect($session->notes)->toBe('Went well');
});
