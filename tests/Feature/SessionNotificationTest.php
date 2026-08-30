<?php

use App\Mail\SessionActivityAdminNotification;
use App\Mail\SessionCancelledMail;
use App\Mail\SessionRescheduledMail;
use App\Mail\SessionScheduledMail;
use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * The mailables are ShouldQueue, so these assert `assertQueued` rather than
 * `assertSent` — matching InvoiceControllerTest.
 */
test('a therapist scheduling a session emails both the client and the admins', function () {
    Mail::fake();

    $admin = adminUser();
    $therapist = therapistUser();
    $client = clientWithUser();
    $client->update(['primary_therapist_id' => $therapist->id]);
    contractedService($client, $therapist);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(SessionScheduledMail::class, fn ($mail) => $mail->hasTo($client->user->email));
    Mail::assertQueued(
        SessionActivityAdminNotification::class,
        fn ($mail) => $mail->hasTo($admin->email) && $mail->action === 'scheduled',
    );
});

test('the client email falls back to the intake parent email when there is no portal account', function () {
    Mail::fake();

    adminUser();
    $therapist = therapistUser();
    $client = Client::factory()->create(['user_id' => null, 'primary_therapist_id' => $therapist->id]);
    $client->originalIntake->update(['primary_parent_email' => 'parent@example.com']);
    contractedService($client, $therapist);

    $this->actingAs($therapist)->post('/therapist/sessions', [
        'client_id' => $client->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(SessionScheduledMail::class, fn ($mail) => $mail->hasTo('parent@example.com'));
});

test('moving a session emails a reschedule notice carrying the previous start', function () {
    Mail::fake();

    $admin = adminUser();
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 10:00:00',
    ]);

    $this->actingAs(adminUser())->put("/admin/sessions/{$session->id}", [
        'client_id' => $session->client_id,
        'therapist_id' => $session->therapist_id,
        'date' => '2026-09-02',
        'start_time' => '14:00',
        'end_time' => '15:00',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(SessionRescheduledMail::class, fn ($mail) => $mail->hasTo($client->user->email)
        && $mail->previousStart?->format('Y-m-d H:i') === '2026-09-01 10:00');
    Mail::assertQueued(
        SessionActivityAdminNotification::class,
        fn ($mail) => $mail->hasTo($admin->email) && $mail->action === 'rescheduled',
    );
});

test('an edit that leaves the start time and therapist alone sends nothing', function () {
    Mail::fake();

    adminUser();
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 10:00:00',
        'duration' => 60,
    ]);

    $this->actingAs(adminUser())->put("/admin/sessions/{$session->id}", [
        'client_id' => $session->client_id,
        'therapist_id' => $session->therapist_id,
        'date' => '2026-09-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'notes' => 'Updated the notes only',
    ])->assertSessionHasNoErrors();

    Mail::assertNothingQueued();
});

test('handing a session to a different therapist counts as a reschedule', function () {
    Mail::fake();

    adminUser();
    $client = clientWithUser();
    $newTherapist = therapistUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 10:00:00',
        'duration' => 60,
    ]);

    $this->actingAs(adminUser())->put("/admin/sessions/{$session->id}", [
        'client_id' => $session->client_id,
        'therapist_id' => $newTherapist->id,
        'date' => '2026-09-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(SessionRescheduledMail::class);
});

test('cancelling a session emails the client and the admins with the reason', function () {
    Mail::fake();

    $admin = adminUser();
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id]);

    $this->actingAs(adminUser())->post("/admin/sessions/{$session->id}/cancel", [
        'cancel_reason' => 'Therapist unavailable',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(SessionCancelledMail::class, fn ($mail) => $mail->hasTo($client->user->email)
        && $mail->session->cancel_reason === 'Therapist unavailable');
    Mail::assertQueued(
        SessionActivityAdminNotification::class,
        fn ($mail) => $mail->hasTo($admin->email) && $mail->action === 'cancelled',
    );
});

test('admins who switched off session reminders are skipped, and inactive admins never receive them', function () {
    Mail::fake();

    $optedOut = User::factory()->admin()->create(['session_reminders' => false]);
    $inactive = User::factory()->admin()->create(['is_active' => false]);
    $subscribed = adminUser();
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id]);

    $this->actingAs(adminUser())->post("/admin/sessions/{$session->id}/cancel", [
        'cancel_reason' => 'Therapist unavailable',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(
        SessionActivityAdminNotification::class,
        fn ($mail) => $mail->hasTo($subscribed->email)
            && ! $mail->hasTo($optedOut->email)
            && ! $mail->hasTo($inactive->email),
    );
});

/**
 * Mail::fake() never renders the markdown views, so the assertions above
 * would still pass against a broken template. These force a real render.
 */
test('every session mailable renders', function () {
    $client = clientWithUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'scheduled_start' => '2026-09-01 10:00:00',
        'duration' => 60,
        'location' => 'Clinic',
        'cancel_reason' => 'Therapist unavailable',
    ]);
    $previousStart = now()->setDateTime(2026, 8, 30, 9, 0);

    expect((new SessionScheduledMail($session))->render())->toContain('Scheduled');
    expect((new SessionRescheduledMail($session, $previousStart))->render())->toContain('Rescheduled');
    expect((new SessionCancelledMail($session))->render())->toContain('Therapist unavailable');

    foreach (['scheduled', 'rescheduled', 'cancelled'] as $action) {
        expect((new SessionActivityAdminNotification($session, $action, $previousStart))->render())
            ->toContain($client->displayName());
    }
});

test('a session mailable renders when the client, therapist, and service are all missing', function () {
    $session = ScheduleSession::factory()->create([
        'client_id' => null,
        'therapist_id' => null,
        'service_id' => null,
        'location' => null,
        'duration' => null,
    ]);

    expect((new SessionScheduledMail($session))->render())->toContain('To be confirmed');
    expect((new SessionActivityAdminNotification($session, 'scheduled'))->render())->toContain('Unknown client');
});

test('a client with neither a portal account nor an intake email still notifies the admins', function () {
    Mail::fake();

    $admin = adminUser();
    $client = Client::factory()->create(['user_id' => null]);
    $client->originalIntake->update(['primary_parent_email' => null]);
    $session = ScheduleSession::factory()->create(['client_id' => $client->id]);

    $this->actingAs(adminUser())->post("/admin/sessions/{$session->id}/cancel", [
        'cancel_reason' => 'No contact on file',
    ])->assertSessionHasNoErrors();

    Mail::assertNotQueued(SessionCancelledMail::class);
    Mail::assertQueued(SessionActivityAdminNotification::class, fn ($mail) => $mail->hasTo($admin->email));
});
