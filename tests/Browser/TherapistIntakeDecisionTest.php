<?php

use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\TeamMember;

/**
 * The decision posts back to the same page, so without `preserveScroll` the
 * therapist is thrown to the top of a long intake the moment they submit —
 * away from the card they were just reading.
 */
it('leaves the therapist where they were after submitting a decision', function () {
    $therapist = therapistUser();
    TeamMember::factory()->create(['user_id' => $therapist->id, 'client' => []]);
    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApprovalHistory::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'sent',
    ]);

    $this->actingAs($therapist);

    // A short window guarantees the page scrolls at all.
    $page = visit("/therapist/intake/{$intake->id}")->resize(1280, 500);

    $page->click('Accept this Intake')
        ->click('Submit Decision')
        ->assertSee('Intake approved and client created.');

    expect($page->script('window.scrollY'))->toBeGreaterThan(0);
});
