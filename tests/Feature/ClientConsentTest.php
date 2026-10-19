<?php

use App\Mail\ClientConsentMail;
use App\Mail\ClientConsentSignedAdminNotification;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\User;
use App\Services\ClientConsentService;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Storage::fake('public');
});

function consentUrl(Client $client, string $route = 'public.consent.show'): string
{
    return app(ClientConsentService::class)->signedUrl($client, $route);
}

/**
 * An intake with one pending review per service, each assigned to its own
 * therapist.
 *
 * @param  array<int, string>  $services
 * @return array{0: Intake, 1: array<string, User>}
 */
function intakeAwaitingReviews(array $services): array
{
    $intake = Intake::factory()->create([
        'services_needed' => $services,
        'primary_parent_email' => 'parent@example.test',
    ]);

    $therapists = [];

    foreach ($services as $service) {
        $therapists[$service] = therapistUser();

        IntakeTherapistApproval::factory()->create([
            'intake_id' => $intake->id,
            'therapist_id' => $therapists[$service]->id,
            'service' => $service,
            'status' => 'pending',
        ]);
    }

    return [$intake, $therapists];
}

test('the consent link goes out once every service on the intake is approved', function () {
    [$intake, $therapists] = intakeAwaitingReviews(['Occupational Therapy', 'Speech and Language Therapy']);

    $this->actingAs($therapists['Occupational Therapy'])
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Occupational Therapy'])
        ->assertSessionHasNoErrors();

    // One service is still pending, so nothing goes out yet.
    Mail::assertNotQueued(ClientConsentMail::class);
    expect(Client::first()->consent_sent_at)->toBeNull();

    $this->actingAs($therapists['Speech and Language Therapy'])
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Speech and Language Therapy'])
        ->assertSessionHasNoErrors();

    $client = Client::first();
    expect($client->consent_sent_at)->not->toBeNull();

    Mail::assertQueued(ClientConsentMail::class, function (ClientConsentMail $mail) use ($client): bool {
        return $mail->hasTo('parent@example.test')
            && $mail->client->is($client)
            && str_contains($mail->uploadUrl, "/consent/{$client->id}")
            && count($mail->attachments()) === 1;
    });
});

test('a rejected service holds the consent back', function () {
    [$intake, $therapists] = intakeAwaitingReviews(['Occupational Therapy', 'Counselling']);

    $this->actingAs($therapists['Counselling'])
        ->post("/therapist/intake/{$intake->id}/therapist-reject", ['service' => 'Counselling', 'notes' => 'Full caseload'])
        ->assertSessionHasNoErrors();

    $this->actingAs($therapists['Occupational Therapy'])
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Occupational Therapy'])
        ->assertSessionHasNoErrors();

    Mail::assertNotQueued(ClientConsentMail::class);
    expect(Client::first()->consent_sent_at)->toBeNull();
});

test('the consent page needs a valid signature', function () {
    $client = Client::factory()->create();
    $other = Client::factory()->create();

    $this->get("/consent/{$client->id}")->assertForbidden();

    $borrowed = str_replace("/consent/{$other->id}", "/consent/{$client->id}", consentUrl($other));
    $this->get($borrowed)->assertForbidden();

    $this->get(consentUrl($client))->assertOk();
});

test('a parent sees only what the consent page needs', function () {
    $intake = Intake::factory()->create([
        'child_first_name' => 'Mia',
        'child_last_name' => 'Santos',
        'primary_parent_name' => 'Ana Santos',
    ]);
    $client = Client::factory()->create(['original_intake_id' => $intake->id, 'consent_sent_at' => now()]);

    $this->get(consentUrl($client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/client-consent')
            ->where('consent.child_name', 'Mia Santos')
            ->where('consent.parent_name', 'Ana Santos')
            ->where('consent.signed_at', null)
            ->has('downloadUrl')
            ->has('uploadUrl')
            ->missing('client')
        );
});

test('a parent downloads the prefilled consent pdf', function () {
    $client = Client::factory()->create();

    $response = $this->get(consentUrl($client, 'public.consent.download'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('CATS_Consent.pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

test('the consent pdf renders for an intake with FSCD funding', function () {
    $intake = Intake::factory()->create([
        'funding_source' => 'BDS-FSCD',
        'funding_source_info' => [
            'FSCD_number' => '12345',
            'FSCD_case_worker_name' => 'Jane Worker',
            'FSCD_case_worker_email' => 'jane@example.test',
        ],
    ]);
    $client = Client::factory()->create(['original_intake_id' => $intake->id]);

    $html = view('pdf.client-consent', [
        'intake' => $intake,
        'childName' => 'Mia Santos',
        'parentName' => 'Ana Santos',
        'dateOfBirth' => now()->subYears(6),
        'fundingSource' => 'Behavioural/Developmental Support (BDS) - FSCD',
        'isFscd' => true,
        'fscdNumber' => '12345',
        'caseWorkerName' => 'Jane Worker',
        'caseWorkerEmail' => 'jane@example.test',
        'legalName' => 'Creative Abilities Therapy Services Inc.',
        'director' => 'Mary Ann Lerit',
    ])->render();

    expect($html)
        ->toContain('THERAPY CONSENT FORM')
        ->toContain('FAMILY SERVICE AGREEMENT')
        ->toContain('FUNDING SOURCE')
        ->toContain('I, <strong>Ana Santos</strong>')
        ->toContain('12345')
        ->toContain('Jane Worker');

    expect(app(PdfService::class)->clientConsent($client))->toStartWith('%PDF');
});

test('a parent uploads their signed consent and the admins are told', function () {
    $client = Client::factory()->create(['consent_sent_at' => now()]);
    adminUser()->forceFill(['new_intake' => true])->save();

    $this->post(consentUrl($client, 'public.consent.upload'), [
        'files' => [UploadedFile::fake()->create('signed-consent.pdf', 200, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->consent_signed_at)->not->toBeNull();

    $document = ClientDocument::query()->where('client_id', $client->id)->sole();
    expect($document->doc_type)->toBe(ClientConsentService::DOCUMENT_TYPE)
        ->and($document->upload_origin)->toBe('client')
        ->and($document->drive_file_id)->not->toBeNull();

    Mail::assertQueued(ClientConsentSignedAdminNotification::class);
});

test('photos of each signed page are filed as separate documents', function () {
    $client = Client::factory()->create(['consent_sent_at' => now()]);

    $this->post(consentUrl($client, 'public.consent.upload'), [
        'files' => [
            UploadedFile::fake()->image('page-1.jpg'),
            UploadedFile::fake()->image('page-2.jpg'),
        ],
    ])->assertSessionHasNoErrors();

    expect(ClientDocument::query()->where('client_id', $client->id)->pluck('title')->all())
        ->toHaveCount(2)
        ->each->toContain('(page ');
});

test('the signed consent must be a pdf or an image', function () {
    $client = Client::factory()->create(['consent_sent_at' => now()]);

    $this->post(consentUrl($client, 'public.consent.upload'), [
        'files' => [UploadedFile::fake()->create('consent.exe', 10, 'application/octet-stream')],
    ])->assertSessionHasErrors('files.0');

    $this->post(consentUrl($client, 'public.consent.upload'), [])->assertSessionHasErrors('files');

    expect($client->refresh()->consent_signed_at)->toBeNull();
});

test('a parent can replace a wrong upload and only the new copy is kept', function () {
    $client = Client::factory()->create(['consent_sent_at' => now()]);
    adminUser()->forceFill(['new_intake' => true])->save();

    $this->post(consentUrl($client, 'public.consent.upload'), [
        'files' => [UploadedFile::fake()->create('wrong-file.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $wrong = ClientDocument::query()->where('client_id', $client->id)->sole();

    // The signed page still offers an upload, for exactly this case.
    $this->get(consentUrl($client))
        ->assertInertia(fn ($page) => $page->whereNot('consent.signed_at', null)->whereNot('uploadUrl', null));

    $this->post(consentUrl($client, 'public.consent.upload'), [
        'files' => [UploadedFile::fake()->create('correct-file.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $documents = ClientDocument::query()->where('client_id', $client->id)->get();
    expect($documents)->toHaveCount(1)
        ->and($documents->first()->id)->not->toBe($wrong->id);
    Storage::disk('public')->assertMissing($wrong->drive_file_id);
    Storage::disk('public')->assertExists($documents->first()->drive_file_id);

    Mail::assertQueued(ClientConsentSignedAdminNotification::class, fn (ClientConsentSignedAdminNotification $mail): bool => $mail->replaced);
});

test('replacing an upload leaves other client documents alone', function () {
    $client = Client::factory()->create(['consent_sent_at' => now(), 'consent_signed_at' => now()]);
    $other = ClientDocument::factory()->create(['client_id' => $client->id, 'doc_type' => 'FSCD Agreement']);

    $this->post(consentUrl($client, 'public.consent.upload'), [
        'files' => [UploadedFile::fake()->create('correct-file.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    expect(ClientDocument::find($other->id))->not->toBeNull();
});

test('an admin can send or resend the consent link', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())
        ->post("/admin/clients/{$client->id}/consent")
        ->assertSessionHas('success');

    expect($client->refresh()->consent_sent_at)->not->toBeNull();
    Mail::assertQueued(ClientConsentMail::class, 1);

    $this->actingAs(adminUser())->post("/admin/clients/{$client->id}/consent");
    Mail::assertQueued(ClientConsentMail::class, 2);
});

test('an admin can send a re-upload link after the consent is signed', function () {
    $client = Client::factory()->create(['consent_signed_at' => now()]);

    $this->actingAs(adminUser())
        ->post("/admin/clients/{$client->id}/consent")
        ->assertSessionHas('success');

    Mail::assertQueued(ClientConsentMail::class, 1);
});

test('the consent emails render', function () {
    $client = Client::factory()->create(['consent_signed_at' => now()]);

    expect((new ClientConsentMail($client, 'https://example.test/consent'))->render())
        ->toContain('Upload Your Signed Consent');
    expect((new ClientConsentSignedAdminNotification($client))->render())
        ->toContain('Signed Consent Returned');
    expect((new ClientConsentSignedAdminNotification($client, replaced: true))->render())
        ->toContain('Signed Consent Replaced');
});
