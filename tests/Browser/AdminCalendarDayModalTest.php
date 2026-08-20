<?php

use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;

/**
 * A month cell only has room for a session count, so the detail lives in a
 * modal opened by clicking the day.
 */
it('opens a modal listing every session booked on the clicked day', function () {
    $therapist = therapistUser();
    $service = ServiceOffering::factory()->create(['name' => 'Speech Therapy']);
    $client = Client::factory()->create();
    $other = Client::factory()->create();

    $today = now()->startOfDay();

    ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'service_id' => $service->id,
        'scheduled_start' => $today->copy()->setTime(9, 0),
        'scheduled_end' => $today->copy()->setTime(10, 0),
        'location' => 'Clinic Room 3',
        'status' => 'scheduled',
    ]);

    // A second booking on the same day proves the modal lists all of them.
    ScheduleSession::factory()->create([
        'client_id' => $other->id,
        'therapist_id' => $therapist->id,
        'service_id' => $service->id,
        'scheduled_start' => $today->copy()->setTime(13, 0),
        'scheduled_end' => $today->copy()->setTime(14, 0),
        'status' => 'scheduled',
    ]);

    $this->actingAs(adminUser());

    $page = visit('/admin/calendar')->click('Month View');

    $page->assertDontSee('Clinic Room 3')
        ->click('#month-day-'.$today->format('Y-m-d'))
        ->assertSee($client->displayName())
        ->assertSee($other->displayName())
        ->assertSee('09:00 – 10:00')
        ->assertSee('13:00 – 14:00')
        ->assertSee('Clinic Room 3')
        ->assertSee("{$therapist->first_name} {$therapist->last_name}")
        ->assertSee('2 sessions booked.')
        ->assertNoJavaScriptErrors();
});

it('says so rather than opening an empty modal when the day has no sessions', function () {
    $this->actingAs(adminUser());

    $empty = now()->startOfDay()->addDay();

    visit('/admin/calendar')
        ->click('Month View')
        ->click('#month-day-'.$empty->format('Y-m-d'))
        ->assertSee('No sessions booked on this day.')
        ->assertNoJavaScriptErrors();
});

it('leaves a session out of the modal once the therapist filter excludes it', function () {
    $shown = therapistUser();
    $hidden = therapistUser();
    $shownClient = Client::factory()->create();
    $hiddenClient = Client::factory()->create();

    $today = now()->startOfDay();

    ScheduleSession::factory()->create([
        'client_id' => $shownClient->id,
        'therapist_id' => $shown->id,
        'scheduled_start' => $today->copy()->setTime(9, 0),
        'scheduled_end' => $today->copy()->setTime(10, 0),
    ]);

    ScheduleSession::factory()->create([
        'client_id' => $hiddenClient->id,
        'therapist_id' => $hidden->id,
        'scheduled_start' => $today->copy()->setTime(11, 0),
        'scheduled_end' => $today->copy()->setTime(12, 0),
    ]);

    $this->actingAs(adminUser());

    visit('/admin/calendar')
        ->click('Month View')
        ->click('All Therapists')
        ->click("{$shown->first_name} {$shown->last_name}")
        ->click('#month-day-'.$today->format('Y-m-d'))
        ->assertSee($shownClient->displayName())
        ->assertDontSee($hiddenClient->displayName())
        ->assertSee('1 session booked.')
        ->assertNoJavaScriptErrors();
});
