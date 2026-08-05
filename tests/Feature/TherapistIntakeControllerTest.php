<?php

use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\TeamMember;
use App\Support\ScheduleMatcher;

test('a therapist can only view an intake reviewed by or assigned to them', function () {
    $therapist = therapistUser();
    $intruder = therapistUser();
    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create(['intake_id' => $intake->id, 'therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->get("/therapist/intake/{$intake->id}")->assertOk();
    $this->actingAs($intruder)->get("/therapist/intake/{$intake->id}")->assertNotFound();
});

test('the intake review page reports capacity, specialization matches, and needsDecision', function () {
    $therapist = therapistUser();
    TeamMember::factory()->create([
        'user_id' => $therapist->id,
        'maximum_caseload' => 10,
        'specializations' => ['Speech Therapy'],
    ]);
    $intake = Intake::factory()->create(['services_needed' => ['Speech Therapy', 'Occupational Therapy']]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);
    IntakeTherapistApprovalHistory::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'status' => 'sent',
    ]);

    $response = $this->actingAs($therapist)->get("/therapist/intake/{$intake->id}");

    $response->assertInertia(fn ($page) => $page
        ->component('therapist/intake/show')
        ->where('capacity.current', 0)
        ->where('capacity.maximum', 10)
        ->where('specializationMatches.Speech Therapy', true)
        ->where('specializationMatches.Occupational Therapy', false)
        ->has('pendingReviews', 1)
        ->where('pendingReviews.0.service', null)
    );
});

test('a therapist assigned to only some of an intake\'s services only sees those services', function () {
    $therapist = therapistUser();
    TeamMember::factory()->create([
        'user_id' => $therapist->id,
        'specializations' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);
    $otherTherapist = therapistUser();
    $intake = Intake::factory()->create([
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy', 'Physiotherapy'],
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'service' => 'Speech and Language Therapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $otherTherapist->id,
        'service' => 'Physiotherapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApprovalHistory::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'sent',
    ]);
    IntakeTherapistApprovalHistory::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'service' => 'Speech and Language Therapy',
        'status' => 'sent',
    ]);

    $response = $this->actingAs($therapist)->get("/therapist/intake/{$intake->id}");

    $response->assertInertia(fn ($page) => $page
        ->component('therapist/intake/show')
        ->where('myServices', ['Occupational Therapy', 'Speech and Language Therapy'])
        ->has('specializationMatches.Occupational Therapy')
        ->has('specializationMatches.Speech and Language Therapy')
        ->missing('specializationMatches.Physiotherapy')
        ->has('pendingReviews', 2)
    );
});

test('a non-assigned therapist cannot approve or reject an intake via the therapist routes', function () {
    $assigned = therapistUser();
    $intruder = therapistUser();
    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $assigned->id,
        'status' => 'pending',
    ]);

    $this->actingAs($intruder)->post("/therapist/intake/{$intake->id}/therapist-approve")->assertForbidden();
    $this->actingAs($intruder)->post("/therapist/intake/{$intake->id}/therapist-reject", ['notes' => 'no'])->assertForbidden();
});

test('the assigned therapist can approve an intake via the therapist routes', function () {
    $therapist = therapistUser();
    TeamMember::factory()->create(['user_id' => $therapist->id, 'client' => []]);
    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $this->actingAs($therapist)
        ->post("/therapist/intake/{$intake->id}/therapist-approve")
        ->assertSessionHasNoErrors();

    expect($intake->refresh()->approved_as_client)->toBeTrue();
});

test('ScheduleMatcher reports none, partial, and full overlap levels', function () {
    $availability = [
        ['week_day' => 'Monday', 'time_from' => '08:00', 'time_to' => '12:00'],
    ];

    $none = ScheduleMatcher::match(['Tuesday'], ['Mornings (8am-11am)'], $availability);
    expect($none['matchLevel'])->toBe('none');

    $partial = ScheduleMatcher::match(['Monday'], ['Mornings (8am-11am)', 'Evenings (4pm-7pm)'], $availability);
    expect($partial['matchLevel'])->toBe('partial');
    expect($partial['matched_days'])->toBe(['Monday']);

    $full = ScheduleMatcher::match(['Monday'], ['Mornings (8am-11am)'], $availability);
    expect($full['matchLevel'])->toBe('full');
});
