<?php

use App\Models\Application;
use App\Models\ClientDocument;
use App\Models\Complaint;
use App\Models\IntakeDocument;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\GoogleDrive\LocalDriveStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    app()->instance(DriveStorage::class, new LocalDriveStorage);
});

/**
 * Stores a file the way an upload does and returns the columns a record keeps.
 *
 * @return array{drive_file_id: string, drive_file_url: string, drive_web_view: string}
 */
function storedFile(string $contents = '%PDF-1.4 stored document', string $name = 'report.pdf'): array
{
    return app(DriveStorage::class)->upload(UploadedFile::fake()->createWithContent($name, $contents), 'client', 'test');
}

test('an admin views an intake document in the app', function () {
    $document = IntakeDocument::factory()->create([...storedFile(), 'name' => 'Assessment']);

    $response = $this->actingAs(adminUser())->get(route('files.intake-documents.show', $document));

    $response->assertOk();
    expect($response->getContent())->toBe('%PDF-1.4 stored document')
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toBe('inline; filename="assessment.pdf"')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
});

test('download=1 sends the file as an attachment', function () {
    $document = IntakeDocument::factory()->create([...storedFile(), 'name' => 'Assessment']);

    $this->actingAs(adminUser())->get(route('files.intake-documents.show', [$document, 'download' => 1]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="assessment.pdf"');
});

test('a file the browser cannot render shows a download prompt instead of the file', function () {
    $document = IntakeDocument::factory()->create([...storedFile('<html><script>alert(1)</script></html>', 'page.html'), 'name' => 'Page']);

    $response = $this->actingAs(adminUser())->get(route('files.intake-documents.show', $document));

    $response->assertOk()
        ->assertSee("can't be previewed", false)
        ->assertDontSee('<script>alert(1)</script>', false);

    $this->actingAs(adminUser())->get(route('files.intake-documents.show', [$document, 'download' => 1]))
        ->assertHeader('Content-Disposition', 'attachment; filename="page.html"');
});

test('guests and non-admins cannot open intake documents', function () {
    $document = IntakeDocument::factory()->create(storedFile());

    $this->get(route('files.intake-documents.show', $document))->assertRedirect();
    $this->actingAs(therapistUser())->get(route('files.intake-documents.show', $document))->assertNotFound();
});

test('a team member opens only their own documents', function () {
    $owner = therapistUser();
    $document = ClientDocument::factory()->create([...storedFile(), 'user_id' => $owner->id, 'title' => 'First Aid']);

    $this->actingAs($owner)->get(route('files.client-documents.show', $document))->assertOk();
    $this->actingAs(therapistUser())->get(route('files.client-documents.show', $document))->assertNotFound();
    $this->actingAs(adminUser())->get(route('files.client-documents.show', $document))->assertOk();
});

test('a complaint attachment follows the complaints list scoping', function () {
    $therapist = therapistUser();
    $complaint = Complaint::factory()->create([
        ...storedFile(),
        'therapist_id' => $therapist->id,
        'complained_by' => 'therapist',
    ]);

    $this->actingAs($therapist)->get(route('files.complaints.show', $complaint))->assertOk();
    $this->actingAs(therapistUser())->get(route('files.complaints.show', $complaint))->assertNotFound();
    $this->actingAs(clientWithUser()->user)->get(route('files.complaints.show', $complaint))->assertNotFound();
    $this->actingAs(adminUser())->get(route('files.complaints.show', $complaint))->assertOk();
});

test('an admin views an application resume, and a missing file is a 404', function () {
    $application = Application::factory()->create([
        'resume' => storedFile('%PDF-1.4 resume')['drive_file_url'],
        'cover_letter' => null,
    ]);

    $response = $this->actingAs(adminUser())->get(route('files.applications.show', [$application, 'resume']));
    $response->assertOk();
    expect($response->getContent())->toBe('%PDF-1.4 resume');

    $this->actingAs(adminUser())->get(route('files.applications.show', [$application, 'cover-letter']))->assertNotFound();
    $this->actingAs(therapistUser())->get(route('files.applications.show', [$application, 'resume']))->assertNotFound();
});

test('a file on record that cannot be fetched explains why instead of a bare 404', function () {
    $stored = storedFile();
    Storage::disk('public')->delete($stored['drive_file_id']);
    $document = IntakeDocument::factory()->create($stored);

    $this->actingAs(adminUser())->get(route('files.intake-documents.show', $document))
        ->assertStatus(503)
        ->assertSee('Reconnect Google Drive');
});
