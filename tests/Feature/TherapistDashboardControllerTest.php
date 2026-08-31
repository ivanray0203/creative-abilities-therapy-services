<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\ScheduleSession;

test('the dashboard reports today\'s session count and the live active-clients count', function () {
    $therapist = therapistUser();
    ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'scheduled_start' => now()->setTime(9, 0)]);
    ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'scheduled_start' => now()->addDays(2)]);
    Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    $response = $this->actingAs($therapist)->get('/therapist');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('therapist/dashboard')
        ->where('stats.todays_sessions', 1)
        ->where('stats.active_clients', 2)
    );
});

test('the dashboard reports completed sessions this week and hours logged this month', function () {
    // Mid-month on purpose: the seeded sessions hang off the start of the
    // week, which falls into the previous month whenever the run lands on
    // the last day or two of one.
    $this->travelTo('2026-08-12 09:00:00');

    $therapist = therapistUser();

    // Completed this week, 1.5 hours — counted in both stats.
    ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'status' => 'completed',
        'scheduled_start' => now()->startOfWeek()->addDay()->setTime(9, 0),
        'scheduled_end' => now()->startOfWeek()->addDay()->setTime(10, 30),
    ]);

    // Completed this month but a different week — counted only in hours.
    ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'status' => 'completed',
        'scheduled_start' => now()->startOfMonth()->setTime(9, 0),
        'scheduled_end' => now()->startOfMonth()->setTime(10, 0),
    ]);

    // Scheduled this week but not completed — excluded from both.
    ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
        'scheduled_start' => now()->startOfWeek()->addDay()->setTime(11, 0),
        'scheduled_end' => now()->startOfWeek()->addDay()->setTime(12, 0),
    ]);

    $response = $this->actingAs($therapist)->get('/therapist');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.completed_this_week', 1)
        ->where('stats.hours_this_month', 2.5)
    );
});

test('pending reviews only include intakes whose latest history entry was sent to this therapist', function () {
    $therapist = therapistUser();

    $sentIntake = Intake::factory()->create();
    $sentReview = IntakeTherapistApproval::factory()->create([
        'intake_id' => $sentIntake->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);
    IntakeTherapistApprovalHistory::factory()->create([
        'intake_id' => $sentIntake->id,
        'therapist_id' => $therapist->id,
        'status' => 'sent',
    ]);

    $notSentIntake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $notSentIntake->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $otherTherapistIntake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $otherTherapistIntake->id,
        'therapist_id' => therapistUser()->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($therapist)->get('/therapist');

    $response->assertInertia(fn ($page) => $page
        ->has('pendingReviews', 1)
        ->where('pendingReviews.0.id', $sentIntake->id)
    );
});

test('an in-progress session is shared as the active session on every therapist page', function () {
    $therapist = therapistUser();
    $active = ScheduleSession::factory()->create(['therapist_id' => $therapist->id, 'status' => 'inprogress']);

    $response = $this->actingAs($therapist)->get('/therapist');

    $response->assertInertia(fn ($page) => $page
        ->where('activeSession.id', $active->id)
    );
});

test('the calendar page returns only this therapist\'s sessions', function () {
    $therapist = therapistUser();
    $own = ScheduleSession::factory()->create(['therapist_id' => $therapist->id]);
    ScheduleSession::factory()->create(['therapist_id' => therapistUser()->id]);

    $response = $this->actingAs($therapist)->get('/therapist/calendar');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('therapist/calendar')
        ->has('sessions', 1)
        ->where('sessions.0.id', $own->id)
    );
});
