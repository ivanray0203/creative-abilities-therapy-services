<?php

use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;

test('the admin calendar carries every therapist\'s sessions, not just one', function () {
    $first = therapistUser();
    $second = therapistUser();

    ScheduleSession::factory()->create(['therapist_id' => $first->id]);
    ScheduleSession::factory()->create(['therapist_id' => $second->id]);

    $this->actingAs(adminUser())->get('/admin/calendar')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/calendar')
            ->has('sessions', 2)
            ->has('therapists', 2)
        );
});

test('each session is flattened into the shape the calendar grid renders', function () {
    $therapist = therapistUser();
    $service = ServiceOffering::factory()->create(['name' => 'Speech Therapy']);
    $client = Client::factory()->create();

    ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'client_id' => $client->id,
        'service_id' => $service->id,
        'scheduled_start' => '2026-09-01 14:30:00',
        'scheduled_end' => '2026-09-01 15:30:00',
        'location' => 'Clinic Room 3',
        'status' => 'scheduled',
    ]);

    $this->actingAs(adminUser())->get('/admin/calendar')
        ->assertInertia(fn ($page) => $page
            ->where('sessions.0.date', '2026-09-01')
            ->where('sessions.0.time', '14:30')
            ->where('sessions.0.endTime', '15:30')
            ->where('sessions.0.client', $client->displayName())
            ->where('sessions.0.type', 'Speech Therapy')
            ->where('sessions.0.location', 'Clinic Room 3')
            ->where('sessions.0.status', 'scheduled')
            ->where('sessions.0.therapistId', $therapist->id)
            ->where('sessions.0.therapist', "{$therapist->first_name} {$therapist->last_name}")
        );
});

test('the therapist filter only lists therapists who actually hold a session', function () {
    $booked = therapistUser();
    therapistUser();

    ScheduleSession::factory()->create(['therapist_id' => $booked->id]);

    $this->actingAs(adminUser())->get('/admin/calendar')
        ->assertInertia(fn ($page) => $page
            ->has('therapists', 1)
            ->where('therapists.0.id', $booked->id)
        );
});

test('a session with no scheduled start is left out rather than rendered on a blank date', function () {
    ScheduleSession::factory()->create(['scheduled_start' => null]);
    ScheduleSession::factory()->create();

    $this->actingAs(adminUser())->get('/admin/calendar')
        ->assertInertia(fn ($page) => $page->has('sessions', 1));
});

test('sessions arrive in chronological order', function () {
    ScheduleSession::factory()->create(['scheduled_start' => '2026-09-03 09:00:00']);
    ScheduleSession::factory()->create(['scheduled_start' => '2026-09-01 09:00:00']);

    $this->actingAs(adminUser())->get('/admin/calendar')
        ->assertInertia(fn ($page) => $page
            ->where('sessions.0.date', '2026-09-01')
            ->where('sessions.1.date', '2026-09-03')
        );
});

test('a therapist cannot reach the clinic-wide calendar', function () {
    $this->actingAs(therapistUser())->get('/admin/calendar')->assertRedirect('/therapist');
});

test('a session whose client or therapist has gone missing still renders a label', function () {
    ScheduleSession::factory()->create([
        'client_id' => null,
        'therapist_id' => null,
        'service_id' => null,
        'service_name' => null,
    ]);

    $this->actingAs(adminUser())->get('/admin/calendar')
        ->assertInertia(fn ($page) => $page
            ->where('sessions.0.client', 'Unknown client')
            ->where('sessions.0.therapist', 'Unassigned')
            ->where('sessions.0.type', 'Session')
        );
});
