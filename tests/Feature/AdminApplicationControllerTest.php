<?php

use App\Mail\CareerApplicationDeclinedMail;
use App\Mail\CareerApplicationHiredMail;
use App\Mail\CareerApplicationInterviewRescheduledMail;
use App\Mail\CareerApplicationInterviewScheduledMail;
use App\Mail\CareerApplicationUnderReviewMail;
use App\Mail\LoginCredentialsMail;
use App\Mail\OfferLetterMail;
use App\Models\Application;
use App\Models\Career;
use App\Models\ClientDocument;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Interviews\MeetingLinkGenerator;
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

test('a candidate cannot start onboarding until they have signed the offer', function () {
    $application = Application::factory()->offerSent()->create();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'onboarding',
    ])->assertSessionHasErrors('application_status');

    $application->refresh();
    expect($application->application_status)->toBe('offer_sent');
    expect(User::where('email', $application->email)->exists())->toBeFalse();
});

test('a candidate cannot be hired straight from a signed offer without onboarding', function () {
    $application = Application::factory()->offerSigned()->create();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors('application_status');

    expect($application->refresh()->application_status)->toBe('offer_sent');
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

test('starting onboarding creates a therapist account and emails credentials with the required documents', function () {
    Mail::fake();

    $career = Career::factory()->create([
        'position' => 'Occupational Therapist',
        'required_documents' => ['Police Information Check with Vulnerable Sector Check', 'Professional License'],
    ]);
    $application = Application::factory()->offerSigned()->create([
        'email' => 'new.hire@example.com',
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
        'hourly_rate' => 55,
        'position_id' => $career->id,
        'position_applied' => $career->position,
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'onboarding',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('onboarding');
    expect($application->hired)->toBeFalse();
    expect($application->onboarding_started_at)->not->toBeNull();

    $user = User::where('email', 'new.hire@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('therapist');
    expect($user->isOnboarding())->toBeTrue();

    $teamMember = TeamMember::where('user_id', $user->id)->first();
    expect($teamMember)->not->toBeNull();
    expect($teamMember->application_id)->toBe($application->id);
    expect($teamMember->employment_status)->toBe('onboarding');
    expect((float) $teamMember->hourly_rate)->toBe(55.0);

    Mail::assertQueued(
        LoginCredentialsMail::class,
        fn (LoginCredentialsMail $mail): bool => $mail->hasTo('new.hire@example.com')
            && $mail->password !== null
            && $mail->requiredDocuments === ['Police Information Check with Vulnerable Sector Check', 'Professional License']
    );
});

test('starting onboarding for an email that already belongs to a user reuses that user', function () {
    Mail::fake();

    $existing = User::factory()->create(['email' => 'already.here@example.com', 'role' => 'client']);
    $application = Application::factory()->offerSigned()->create([
        'email' => 'already.here@example.com',
        'hourly_rate' => 50,
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'onboarding',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'already.here@example.com')->count())->toBe(1);
    expect(TeamMember::where('user_id', $existing->id)->value('employment_status'))->toBe('onboarding');

    Mail::assertQueued(
        LoginCredentialsMail::class,
        fn (LoginCredentialsMail $mail): bool => $mail->password === null,
    );
});

test('a candidate cannot be hired while required documents are missing', function () {
    Mail::fake();

    [$application] = onboardingCandidate(['Professional License', 'Police Check']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors('application_status');

    $application->refresh();
    expect($application->application_status)->toBe('onboarding');
    expect($application->hired)->toBeFalse();
    Mail::assertNothingQueued();
});

test('the application page lists the onboarding document checklist', function () {
    [$application, $user] = onboardingCandidate(['Professional License', 'Police Check']);
    ClientDocument::factory()->create(['client_id' => null, 'user_id' => $user->id, 'doc_type' => 'Police Check']);

    $this->actingAs(adminUser())->get("/admin/applications/{$application->id}")
        ->assertInertia(fn ($page) => $page
            ->component('admin/applications/show')
            ->where('onboarding.required_documents', ['Professional License', 'Police Check'])
            ->where('onboarding.missing_documents', ['Professional License'])
            ->has('onboarding.documents', 1)
        );
});

test('hiring an onboarded candidate activates their team member and emails them', function () {
    Mail::fake();

    [$application, $user] = onboardingCandidate(['Professional License']);
    ClientDocument::factory()->create(['client_id' => null, 'user_id' => $user->id, 'doc_type' => 'Professional License']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('hired');
    expect($application->hired)->toBeTrue();
    expect($application->hire_date)->not->toBeNull();
    expect(TeamMember::where('user_id', $user->id)->value('employment_status'))->toBe('active');
    expect($user->fresh()->isOnboarding())->toBeFalse();

    Mail::assertQueued(
        CareerApplicationHiredMail::class,
        fn (CareerApplicationHiredMail $mail): bool => $mail->hasTo($application->email),
    );
    Mail::assertNotQueued(LoginCredentialsMail::class);
});

test('an application cannot be hired twice', function () {
    [$application] = onboardingCandidate([]);
    $application->forceFill(['hired' => true])->save();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasErrors();
});

test('an onboarding therapist can only reach their profile until they are hired', function () {
    [$application, $user] = onboardingCandidate([]);

    $this->actingAs($user)->get('/therapist')->assertRedirect('/therapist/profile');
    $this->actingAs($user)->get('/therapist/clients')->assertRedirect('/therapist/profile');

    $this->actingAs($user)->get('/therapist/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('therapist/profile')
            ->where('auth.is_onboarding', true)
        );

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user->fresh())->get('/therapist')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.is_onboarding', false));
});

/**
 * A candidate mid-onboarding: signed offer, portal account, team member held
 * at `onboarding`, and a position that requires the given documents.
 *
 * @param  array<int, string>  $requiredDocuments
 * @return array{0: Application, 1: User}
 */
function onboardingCandidate(array $requiredDocuments): array
{
    $career = Career::factory()->create(['required_documents' => $requiredDocuments]);
    $application = Application::factory()->onboarding()->create([
        'position_id' => $career->id,
        'position_applied' => $career->position,
        'hourly_rate' => 50,
    ]);
    $user = User::factory()->therapist()->create(['email' => $application->email]);
    TeamMember::factory()->onboarding()->create([
        'user_id' => $user->id,
        'application_id' => $application->id,
        'position' => $career->position,
    ]);

    return [$application, $user->load('teamMember')];
}

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

/**
 * Stands in for Google Calendar: hands out a predictable Meet link and
 * records what was booked and cancelled.
 */
final class FakeMeetingLinkGenerator implements MeetingLinkGenerator
{
    /** @var array<int, int> */
    public array $scheduled = [];

    /** @var array<int, int> */
    public array $cancelled = [];

    public function __construct(private readonly bool $available = true) {}

    public function schedule(Application $application): ?array
    {
        $this->scheduled[] = $application->id;

        if (! $this->available) {
            return null;
        }

        $eventId = $application->interview_calendar_event_id ?? 'evt-'.$application->id;

        return ['meeting_link' => "https://meet.google.com/abc-{$eventId}", 'event_id' => $eventId];
    }

    public function cancel(Application $application): void
    {
        if ($application->interview_calendar_event_id !== null) {
            $this->cancelled[] = $application->id;
        }
    }
}

function fakeMeetings(bool $available = true): FakeMeetingLinkGenerator
{
    $fake = new FakeMeetingLinkGenerator($available);
    app()->instance(MeetingLinkGenerator::class, $fake);

    return $fake;
}

test('scheduling a video interview generates a Google Meet link and emails it to the candidate', function () {
    Mail::fake();
    $meetings = fakeMeetings();

    $application = Application::factory()->create(['application_status' => 'reviewing', 'email' => 'candidate@example.com']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-02',
        'interview_time' => '14:00',
        'interview_platform' => 'video',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->interview_meeting_link)->toBe("https://meet.google.com/abc-evt-{$application->id}");
    expect($application->interview_calendar_event_id)->toBe("evt-{$application->id}");
    expect($meetings->scheduled)->toBe([$application->id]);

    Mail::assertQueued(
        CareerApplicationInterviewScheduledMail::class,
        fn (CareerApplicationInterviewScheduledMail $mail): bool => $mail->application->interview_meeting_link === "https://meet.google.com/abc-evt-{$application->id}",
    );
});

test('rescheduling a video interview keeps the same calendar event and link', function () {
    Mail::fake();
    $meetings = fakeMeetings();

    $application = Application::factory()->create([
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-02',
        'interview_time' => '14:00',
        'interview_platform' => 'video',
        'interview_meeting_link' => 'https://meet.google.com/abc-evt-old',
        'interview_calendar_event_id' => 'evt-old',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-04',
        'interview_time' => '10:00',
        'interview_platform' => 'video',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->interview_calendar_event_id)->toBe('evt-old');
    expect($application->interview_meeting_link)->toBe('https://meet.google.com/abc-evt-old');
    expect($meetings->scheduled)->toBe([$application->id]);
    expect($meetings->cancelled)->toBe([]);
});

test('switching a video interview to a phone call removes the Meet link', function () {
    Mail::fake();
    $meetings = fakeMeetings();

    $application = Application::factory()->create([
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-02',
        'interview_platform' => 'video',
        'interview_meeting_link' => 'https://meet.google.com/abc-evt-old',
        'interview_calendar_event_id' => 'evt-old',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-03',
        'interview_platform' => 'phone',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->interview_meeting_link)->toBeNull();
    expect($application->interview_calendar_event_id)->toBeNull();
    expect($meetings->cancelled)->toBe([$application->id]);
    expect($meetings->scheduled)->toBe([]);
});

test('declining a candidate with a booked video interview cancels the calendar event', function () {
    Mail::fake();
    $meetings = fakeMeetings();

    $application = Application::factory()->create([
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-02',
        'interview_platform' => 'video',
        'interview_meeting_link' => 'https://meet.google.com/abc-evt-old',
        'interview_calendar_event_id' => 'evt-old',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'declined',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('declined');
    expect($application->interview_meeting_link)->toBeNull();
    expect($meetings->cancelled)->toBe([$application->id]);
});

test('a video interview is still booked when no Meet link can be generated', function () {
    Mail::fake();
    fakeMeetings(available: false);

    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => '2026-09-02',
        'interview_platform' => 'video',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('interview_scheduled');
    expect($application->interview_meeting_link)->toBeNull();

    Mail::assertQueued(CareerApplicationInterviewScheduledMail::class);
});
