<?php

use App\Jobs\CreateMailcowMailbox;
use App\Mail\CareerApplicationAdminNotification;
use App\Mail\CareerApplicationSubmittedConfirmationMail;
use App\Mail\ContactFormNotification;
use App\Mail\IntakeAssignedToTherapistMail;
use App\Mail\IntakeSubmittedAdminNotification;
use App\Mail\IntakeSubmittedConfirmationMail;
use App\Mail\InvoiceResendMail;
use App\Mail\LoginCredentialsMail;
use App\Mail\OfferLetterMail;
use App\Mail\StaffInviteMail;
use App\Mail\WelcomeClientAccountMail;
use App\Models\Application;
use App\Models\ConsentClause;
use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('approving an intake for a brand new parent sends a welcome email with the generated password', function () {
    Mail::fake();

    $intake = Intake::factory()->create([
        'status' => 'approved',
        'primary_parent_email' => 'new.parent@example.com',
        'primary_parent_name' => 'Alex Rivera',
    ]);

    $this->actingAs(adminUser())->post("/admin/intake/{$intake->id}/approve")->assertSessionHasNoErrors();

    Mail::assertQueued(WelcomeClientAccountMail::class, fn ($mail) => $mail->email === 'new.parent@example.com');
});

test('sending an intake to a therapist emails the therapist', function () {
    Mail::fake();

    $intake = Intake::factory()->create(['status' => 'pending']);
    $therapist = therapistUser();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/send-to-therapist", ['therapist_id' => $therapist->id])
        ->assertSessionHasNoErrors();

    Mail::assertQueued(IntakeAssignedToTherapistMail::class, fn ($mail) => $mail->hasTo($therapist->email));
});

test('hiring an applicant sends an offer letter and, for a new account, login credentials', function () {
    Mail::fake();

    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'new.hire@example.com',
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
        'position_applied' => 'Occupational Therapist',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
        'hourly_rate' => 55,
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(OfferLetterMail::class, fn ($mail) => $mail->hasTo('new.hire@example.com') && $mail->position === 'Occupational Therapist');
    Mail::assertQueued(LoginCredentialsMail::class, fn ($mail) => $mail->hasTo('new.hire@example.com'));
});

test('OfferLetterMail survives real queue JSON serialization with its binary PDF attachment', function () {
    $application = Application::factory()->create([
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
        'position_applied' => 'Occupational Therapist',
    ]);

    $pdfBytes = app(PdfService::class)->offerLetter($application);
    $mail = new OfferLetterMail('Jamie', 'Rivera', 'Occupational Therapist', $pdfBytes);

    // Mirrors Illuminate\Queue\Queue::createPayload(), which json_encode()s the
    // serialized job — this is the exact step that threw InvalidPayloadException
    // when $pdfContents held raw (non-base64) binary PDF bytes.
    $payload = json_encode(serialize($mail));

    expect($payload)->not->toBeFalse();
});

test('hiring an applicant provisions a Mailcow mailbox when Mailcow is configured', function () {
    config(['services.mailcow.api_key' => 'test-key', 'services.mailcow.default_domain' => 'example.com']);
    Mail::fake();
    Bus::fake();

    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'jordan.lee@example.com',
        'first_name' => 'Jordan',
        'last_name' => 'Lee',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
        'hourly_rate' => 40,
    ])->assertSessionHasNoErrors();

    Bus::assertDispatched(CreateMailcowMailbox::class);
});

test('creating an admin user sends a staff invite email', function () {
    Mail::fake();

    $this->actingAs(adminUser())->post('/admin/administrator/users', [
        'first_name' => 'Taylor',
        'last_name' => 'Morgan',
        'email' => 'taylor.morgan@example.com',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(StaffInviteMail::class, fn ($mail) => $mail->hasTo('taylor.morgan@example.com'));
});

test('resending an invoice emails the client', function () {
    Mail::fake();

    $client = clientWithUser();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);

    $this->actingAs(adminUser())->post("/admin/invoices/{$invoice->id}/resend")->assertSessionHasNoErrors();

    Mail::assertQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($client->user->email));
});

test('submitting the contact form notifies all active admins', function () {
    Mail::fake();

    $activeAdmin = User::factory()->admin()->create();
    $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);

    $this->post('/contacts', [
        'name' => 'Sam Doe',
        'email' => 'sam@example.com',
        'subject' => 'Question',
        'message' => 'Hello there',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(ContactFormNotification::class, fn ($mail) => $mail->hasTo($activeAdmin->email)
        && ! $mail->hasTo($inactiveAdmin->email));
});

test('submitting a career application sends a confirmation and notifies admins subscribed to new applications', function () {
    Mail::fake();

    $subscribedAdmin = User::factory()->admin()->create(['new_applications' => true]);
    $unsubscribedAdmin = User::factory()->admin()->create(['new_applications' => false]);

    $this->post('/careers/apply', validCareerApplicationPayload())->assertSessionHasNoErrors();

    Mail::assertQueued(CareerApplicationSubmittedConfirmationMail::class, fn ($mail) => $mail->hasTo('jane.doe@example.com'));
    Mail::assertQueued(CareerApplicationAdminNotification::class, fn ($mail) => $mail->hasTo($subscribedAdmin->email)
        && ! $mail->hasTo($unsubscribedAdmin->email));
});

test('submitting an intake sends a confirmation and notifies admins subscribed to new intakes', function () {
    Mail::fake();

    $subscribedAdmin = User::factory()->admin()->create(['new_intake' => true]);
    $unsubscribedAdmin = User::factory()->admin()->create(['new_intake' => false]);
    $inactiveAdmin = User::factory()->admin()->create(['new_intake' => true, 'is_active' => false]);

    $payload = validIntakePayload();

    $this->post('/intake/apply', $payload)->assertSessionHasNoErrors();

    Mail::assertQueued(IntakeSubmittedConfirmationMail::class, fn ($mail) => $mail->hasTo($payload['primary_parent_email']));
    Mail::assertQueued(IntakeSubmittedAdminNotification::class, fn ($mail) => $mail->hasTo($subscribedAdmin->email)
        && ! $mail->hasTo($unsubscribedAdmin->email)
        && ! $mail->hasTo($inactiveAdmin->email));
});

test('an intake with accepted consents uploads a generated consent PDF as an intake document', function () {
    Storage::fake('public');
    Mail::fake();

    $document = ConsentDocument::factory()->create(['purpose' => 'intake', 'is_active' => true]);
    ConsentClause::factory()->create(['document_id' => $document->id, 'order' => 0, 'text_template' => 'Introduction text.']);

    $payload = validIntakePayload(['consent_ids' => [$document->id]]);

    $this->post('/intake/apply', $payload)->assertSessionHasNoErrors();

    $intake = Intake::latest('id')->first();
    $pdfDocument = $intake->documents()->where('type', 'Consent Forms')->first();

    expect($pdfDocument)->not->toBeNull();
    expect($pdfDocument->drive_file_id)->not->toBeNull();
    Storage::disk('public')->assertExists($pdfDocument->drive_file_id);
});

test('the Mailcow job succeeds on a successful API response', function () {
    Http::fake([
        '*/add/mailbox' => Http::response([['type' => 'success', 'msg' => ['mailbox_added']]], 200),
    ]);

    (new CreateMailcowMailbox(1, 'Jordan', 'Lee', 'secret-password'))->handle();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/add/mailbox')
        && $request['local_part'] === 'jordan.lee');
});

test('the Mailcow job does not retry when the mailbox already exists', function () {
    Http::fake([
        '*/add/mailbox' => Http::response([['type' => 'error', 'msg' => 'mailbox already exists']], 200),
    ]);

    (new CreateMailcowMailbox(1, 'Jordan', 'Lee', 'secret-password'))->handle();

    Http::assertSentCount(1);
});

test('the Mailcow job throws on a generic API error so the queue retries it', function () {
    Http::fake([
        '*/add/mailbox' => Http::response([['type' => 'error', 'msg' => 'internal error']], 200),
    ]);

    expect(fn () => (new CreateMailcowMailbox(1, 'Jordan', 'Lee', 'secret-password'))->handle())
        ->toThrow(RuntimeException::class);
});

test('PdfService generates a valid offer letter PDF', function () {
    $application = Application::factory()->make([
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
        'position_applied' => 'Speech-Language Pathologist',
        'hourly_rate' => 42.5,
    ]);

    $pdf = app(PdfService::class)->offerLetter($application);

    expect($pdf)->toStartWith('%PDF');
});

test('PdfService returns null for an intake with no accepted consents', function () {
    $intake = Intake::factory()->create(['consents' => []]);

    expect(app(PdfService::class)->consentPdfForIntake($intake))->toBeNull();
});
