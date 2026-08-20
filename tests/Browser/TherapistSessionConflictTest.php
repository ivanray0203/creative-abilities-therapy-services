<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Intake;
use App\Models\ScheduleSession;

/**
 * A therapist's own double-booking is reported against `therapist_id`, but
 * their form has no Therapist field — the rejection used to leave the page
 * looking as though the Save button had done nothing at all.
 */
it('tells a therapist why their double-booking was rejected', function () {
    $therapist = therapistUser();
    $intake = Intake::factory()->create([
        'child_first_name' => 'Rowan',
        'child_last_name' => 'Vale',
    ]);
    $client = Client::factory()->create([
        'primary_therapist_id' => $therapist->id,
        'original_intake_id' => $intake->id,
    ]);
    ClientService::factory()->for($client)->create(['therapist_id' => $therapist->id]);
    ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 09:00:00',
        'scheduled_end' => '2026-09-01 10:00:00',
        'status' => 'scheduled',
    ]);

    $this->actingAs($therapist);

    $page = visit('/therapist/sessions/create');

    $page->click('#session-client')
        ->click('Rowan Vale')
        ->type('#session-date', '2026-09-01')
        ->type('#session-start-time', '09:30')
        ->type('#session-end-time', '10:30')
        ->click('Schedule Session')
        ->assertSee('You already have a session booked from 9:00 AM to 10:00 AM.');

    expect(ScheduleSession::count())->toBe(1);
});
