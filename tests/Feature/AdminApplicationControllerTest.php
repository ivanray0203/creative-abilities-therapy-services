<?php

use App\Mail\CareerApplicationDeclinedMail;
use App\Mail\CareerApplicationInterviewRescheduledMail;
use App\Mail\CareerApplicationInterviewScheduledMail;
use App\Mail\CareerApplicationUnderReviewMail;
use App\Mail\OfferLetterMail;
use App\Models\Application;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('the application index shows stats, search, and status filter', function () {
    Application::factory()->create(['application_status' => 'pending']);
    Application::factory()->create(['application_status' => 'hired', 'first_name' => 'Zoe']);

    $response = $this->actingAs(adminUser())->get('/admin/applications');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/applications/index')
        ->where('stats.total', 2)
        ->where('stats.pending', 1)
        ->where('stats.hired', 1)
    );

    $filtered = $this->actingAs(adminUser())->get('/admin/applications?search=Zoe');
    $filtered->assertInertia(fn ($page) => $page->has('applications.data', 1));
});

test('an invalid status transition is rejected', function () {
    $application = Application::factory()->create(['application_status' => 'pending']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors('application_status');

    expect($application->refresh()->application_status)->toBe('pending');
});

test('a candidate cannot be hired straight from reviewing', function () {
    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors('application_status');

    expect($application->refresh()->application_status)->toBe('reviewing');
});

test('a candidate cannot be hired until they have signed the offer', function () {
    $application = Application::factory()->offerSent()->create();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors('application_status');

    $application->refresh();
    expect($application->application_status)->toBe('offer_sent');
    expect($application->hired)->toBeFalse();
    expect(User::where('email', $application->email)->exists())->toBeFalse();
});

test('sending an offer stamps the deadline, files the letter and emails a signing link', function () {
    Mail::fake();
    Storage::fake('public');

    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'candidate@example.com',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'offer_sent',
        'hourly_rate' => 20.28,
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('offer_sent');
    expect((float) $application->hourly_rate)->toBe(20.28);
    expect($application->offer_sent_at)->not->toBeNull();
    expect($application->offer_letter)->not->toBeNull();
    expect($application->signed_offer_letter)->toBeNull();
    // Five calendar days, per config('cats.offer.acceptance_days').
    expect($application->offer_expires_at->toDateString())
        ->toBe(now()->addDays(5)->toDateString());

    Mail::assertQueued(
        OfferLetterMail::class,
        fn (OfferLetterMail $mail): bool => $mail->hasTo('candidate@example.com')
            && str_contains($mail->signingUrl, '/offer/'.$application->id)
            && str_contains($mail->signingUrl, 'signature=')
    );
});

test('the hourly rate is required when the offer goes out', function () {
    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'offer_sent',
    ])->assertSessionHasErrors('hourly_rate');
});

test('scheduling an interview requires and stores the interview fields', function () {
    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
    ])->assertSessionHasErrors('interview_date');

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => now()->addDays(3)->toDateString(),
        'interview_time' => '14:00',
        'interview_platform' => 'video',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('interview_scheduled');
    expect($application->interview_platform)->toBe('video');
});

test('hiring an applicant creates a therapist user and a linked team member', function () {
    $application = Application::factory()->offerSigned()->create([
        'email' => 'new.hire@example.com',
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
        'hourly_rate' => 55,
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('hired');
    expect($application->hired)->toBeTrue();
    expect((float) $application->hourly_rate)->toBe(55.0);
    expect($application->hire_date)->not->toBeNull();

    $user = User::where('email', 'new.hire@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('therapist');

    $teamMember = TeamMember::where('user_id', $user->id)->first();
    expect($teamMember)->not->toBeNull();
    expect($teamMember->application_id)->toBe($application->id);
    expect((float) $teamMember->hourly_rate)->toBe(55.0);
});

test('hiring an applicant whose email already belongs to a user reuses that user', function () {
    $existing = User::factory()->create(['email' => 'already.here@example.com', 'role' => 'client']);
    $application = Application::factory()->offerSigned()->create([
        'email' => 'already.here@example.com',
        'hourly_rate' => 50,
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'already.here@example.com')->count())->toBe(1);
    expect(TeamMember::where('user_id', $existing->id)->exists())->toBeTrue();
});

test('an application cannot be hired twice', function () {
    $application = Application::factory()->offerSigned()->create(['hired' => true]);

    // hired is terminal, so the status-transition guard already blocks
    // this — but exercise the service guard directly too via a forced
    // offer_sent state to prove double-hire protection independent of the
    // transition table.
    $application->forceFill(['application_status' => 'offer_sent'])->save();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors();
});

test('declining an application sets the declined flag', function () {
    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'declined',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('declined');
    expect($application->declined)->toBeTrue();
});

test('candidate rating must be between 1 and 5', function () {
    $application = Application::factory()->create();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/rating", [
        'candidate_rating' => 6,
    ])->assertSessionHasErrors('candidate_rating');

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/rating", [
        'candidate_rating' => 4,
    ])->assertSessionHasNoErrors();

    expect($application->refresh()->candidate_rating)->toBe(4);
});

test('an admin can add and delete internal notes', function () {
    $application = Application::factory()->create(['internal_notes' => []]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/notes", [
        'note' => 'Strong candidate.',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->internal_notes)->toHaveCount(1);
    $noteId = $application->internal_notes[0]['id'];

    $this->actingAs(adminUser())->delete("/admin/applications/{$application->id}/notes/{$noteId}")
        ->assertSessionHasNoErrors();

    expect($application->refresh()->internal_notes)->toBe([]);
});

test('an admin can delete an application', function () {
    $application = Application::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/applications/{$application->id}")->assertSessionHasNoErrors();

    expect(Application::find($application->id))->toBeNull();
});

test('moving an application to reviewing emails the candidate', function () {
    Mail::fake();

    $application = Application::factory()->create([
        'application_status' => 'pending',
        'email' => 'candidate@example.com',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'reviewing',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(
        CareerApplicationUnderReviewMail::class,
        fn (CareerApplicationUnderReviewMail $mail): bool => $mail->hasTo('candidate@example.com')
    );
});

test('scheduling an interview emails the candidate the slot and format', function () {
    Mail::fake();

    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'candidate@example.com',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-02',
        'interview_time' => '14:00',
        'interview_platform' => 'video',
    ])->assertSessionHasNoErrors();

    $expected = Carbon::parse('2026-09-02')->format('l, j F Y').' at 2:00 PM';

    Mail::assertQueued(
        CareerApplicationInterviewScheduledMail::class,
        fn (CareerApplicationInterviewScheduledMail $mail): bool => $mail->hasTo('candidate@example.com')
            && $mail->application->interview_schedule === $expected
            && $mail->application->interview_platform_label === 'Video Call'
    );

    Mail::assertNotQueued(CareerApplicationInterviewRescheduledMail::class);
});

test('rescheduling an interview tells the candidate what the slot used to be', function () {
    Mail::fake();

    $application = Application::factory()->create([
        'application_status' => 'interview_scheduled',
        'email' => 'candidate@example.com',
        'interview_date' => '2026-09-02',
        'interview_time' => '14:00',
        'interview_platform' => 'video',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-04',
        'interview_time' => '10:30',
        'interview_platform' => 'phone',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(
        CareerApplicationInterviewRescheduledMail::class,
        fn (CareerApplicationInterviewRescheduledMail $mail): bool => $mail->hasTo('candidate@example.com')
            && str_contains((string) $mail->previousSchedule, '2:00 PM')
            && str_contains((string) $mail->application->interview_schedule, '10:30 AM')
    );

    Mail::assertNotQueued(CareerApplicationInterviewScheduledMail::class);
});

test('declining an application emails the candidate', function () {
    Mail::fake();

    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'candidate@example.com',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'declined',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(
        CareerApplicationDeclinedMail::class,
        fn (CareerApplicationDeclinedMail $mail): bool => $mail->hasTo('candidate@example.com')
    );
});

test('every candidate email renders', function () {
    $application = Application::factory()->create([
        'application_status' => 'interview_scheduled',
        'first_name' => 'Priya',
        'position_applied' => 'Speech-Language Pathologist',
        'interview_date' => '2026-09-02',
        'interview_time' => '14:00',
        'interview_platform' => 'video',
    ]);

    expect((new CareerApplicationUnderReviewMail($application))->render())
        ->toContain($application->reference_number);

    expect((new CareerApplicationInterviewScheduledMail($application))->render())
        ->toContain('Video Call')
        ->toContain('2:00 PM');

    expect((new CareerApplicationInterviewRescheduledMail($application, 'Tuesday, 1 September 2026 at 9:00 AM'))->render())
        ->toContain('9:00 AM')
        ->toContain('2:00 PM');

    expect((new CareerApplicationDeclinedMail($application))->render())
        ->toContain('Priya')
        ->toContain('Speech-Language Pathologist');
});

test('the interview labels serialize with the application', function () {
    $application = Application::factory()->create([
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-08-27',
        'interview_time' => '02:34',
        'interview_platform' => 'video',
    ]);

    $serialized = $application->toArray();

    expect($serialized['interview_schedule'])->toBe('Thursday, 27 August 2026 at 2:34 AM');
    expect($serialized['interview_platform_label'])->toBe('Video Call');

    // A booked date with no time still reads as a date, not midnight.
    $application->forceFill(['interview_time' => null, 'interview_platform' => 'in-person'])->save();

    expect($application->refresh()->interview_schedule)->toBe('Thursday, 27 August 2026');
    expect($application->interview_platform_label)->toBe('In-Person');

    // Nothing booked at all appends a null rather than an empty string.
    $blank = Application::factory()->create();

    expect($blank->toArray()['interview_schedule'])->toBeNull();
});
