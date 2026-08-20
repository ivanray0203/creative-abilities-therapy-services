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

/**
 * Phase 18 — before this index existed, the therapist dashboard was the only
 * route to an assigned review, and it applies an extra
 * `latestHistoryStatus() === 'sent'` filter. Anything that filter hid was
 * unreachable.
 *
 * @see tasks/18-scheduling-conflicts-review-queue-authorization.md
 */
test('the review index lists intakes assigned to this therapist only', function () {
    $therapist = therapistUser();
    $other = therapistUser();

    $mine = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $mine->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $theirs = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $theirs->id,
        'therapist_id' => $other->id,
        'status' => 'pending',
    ]);

    $this->actingAs($therapist)->get('/therapist/intake')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('therapist/intake/index')
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $mine->id)
        );
});

test('the review index shows assignments the dashboard sent-filter would hide', function () {
    $therapist = therapistUser();

    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    // No IntakeTherapistApprovalHistory row at all, so latestHistoryStatus()
    // is null and the dashboard block would drop this intake entirely.
    $this->actingAs($therapist)->get('/therapist/intake')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $intake->id)
        );
});

test('the review index separates awaiting-me from decided', function () {
    $therapist = therapistUser();

    $awaiting = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $awaiting->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $decided = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $decided->id,
        'therapist_id' => $therapist->id,
        'status' => 'approved',
    ]);

    $this->actingAs($therapist)->get('/therapist/intake?status=pending')
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $awaiting->id)
        );

    $this->actingAs($therapist)->get('/therapist/intake?status=decided')
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $decided->id)
        );

    $this->actingAs($therapist)->get('/therapist/intake?status=all')
        ->assertInertia(fn ($page) => $page->has('intakes.data', 2));
});

test('the review index can be searched by child name', function () {
    $therapist = therapistUser();

    $match = Intake::factory()->create(['child_first_name' => 'Zephyr']);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $match->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $miss = Intake::factory()->create(['child_first_name' => 'Aurora']);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $miss->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $this->actingAs($therapist)->get('/therapist/intake?search=Zephyr')
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $match->id)
        );
});

test('a client cannot reach the therapist review index', function () {
    $this->actingAs(clientWithUser()->user)
        ->get('/therapist/intake')
        ->assertRedirect('/client/calendar');
});
