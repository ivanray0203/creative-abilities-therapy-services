<?php

use App\Models\Application;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Invoice;
use App\Models\PendingDriveUpload;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\GoogleDrive\FallbackDriveStorage;
use App\Services\GoogleDrive\LocalDriveStorage;
use App\Services\StoredDocumentReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * A stand-in for GoogleDriveService that records uploads, or fails them
 * while `$failing` is set.
 */
class FakeRemoteDrive implements DriveStorage
{
    /** @var array<int, array{name: string, type: string, folder: string, contents: string}> */
    public array $uploads = [];

    /** @var array<int, string> */
    public array $deleted = [];

    public function __construct(public bool $failing = false) {}

    public function upload(UploadedFile $file, string $targetType, string $targetName): array
    {
        if ($this->failing) {
            throw new RuntimeException('invalid_grant: Token has been expired or revoked.');
        }

        $this->uploads[] = [
            'name' => $file->getClientOriginalName(),
            'type' => $targetType,
            'folder' => $targetName,
            'contents' => (string) file_get_contents($file->getRealPath()),
        ];
        $id = 'drive-'.count($this->uploads);

        return [
            'drive_file_id' => $id,
            'drive_file_url' => "https://drive.google.com/uc?id={$id}&export=download",
            'drive_web_view' => "https://drive.google.com/file/d/{$id}/view",
        ];
    }

    public function get(string $fileId): ?string
    {
        return "remote:{$fileId}";
    }

    public function delete(?string $fileId): void
    {
        $this->deleted[] = (string) $fileId;
    }
}

/** Binds the fallback storage the way AppServiceProvider does once Drive is configured. */
function useFallbackDrive(?FakeRemoteDrive $remote): void
{
    app()->instance(DriveStorage::class, new FallbackDriveStorage($remote));
}

/** Holds a file the way an upload does while Drive is unavailable, aged past the sync grace period. */
function holdFile(string $contents = 'held pdf', string $name = 'consent.pdf', string $folder = '7_Jane Doe'): array
{
    $uploaded = app(DriveStorage::class)->upload(UploadedFile::fake()->createWithContent($name, $contents), 'client', $folder);
    PendingDriveUpload::query()->update(['created_at' => now()->subMinutes(5)]);

    return $uploaded;
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

test('without Drive credentials uploads keep going to the public disk', function () {
    config(['services.google_drive.client_id' => null]);

    expect(app(DriveStorage::class))->toBeInstanceOf(LocalDriveStorage::class);
});

test('with Drive configured but not connected, the fallback holds uploads', function () {
    config([
        'services.google_drive.client_id' => 'client-id',
        'services.google_drive.client_secret' => 'secret',
        'services.google_drive.shared_drive_id' => 'root',
        'services.google_drive.token_path' => storage_path('framework/testing/missing-token.json'),
    ]);

    $storage = app(DriveStorage::class);

    expect($storage)->toBeInstanceOf(FallbackDriveStorage::class)
        ->and($storage->remote())->toBeNull();
});

test('an upload while Drive is not connected is held privately and the record still saves', function () {
    useFallbackDrive(null);
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post("/admin/clients/{$client->id}/documents", [
        'type' => 'Assessment',
        'file' => UploadedFile::fake()->create('assessment.pdf', 20, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $document = ClientDocument::query()->sole();
    $pending = PendingDriveUpload::query()->sole();

    expect($document->drive_file_id)->toBe("pending:{$pending->uuid}")
        ->and($document->drive_web_view)->toBe(route('stored-files.show', $pending))
        ->and($pending->original_name)->toBe('assessment.pdf')
        ->and($pending->target_type)->toBe('client');

    Storage::disk('local')->assertExists($pending->path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('an upload Drive rejects is held instead of failing', function () {
    useFallbackDrive(new FakeRemoteDrive(failing: true));

    $uploaded = holdFile();

    expect($uploaded['drive_file_id'])->toStartWith('pending:')
        ->and(PendingDriveUpload::query()->count())->toBe(1);
});

test('a held file is shown to signed-in users only', function () {
    useFallbackDrive(null);
    holdFile('held pdf body', 'report.pdf');
    $pending = PendingDriveUpload::query()->sole();

    $this->get(route('stored-files.show', $pending))->assertRedirect();

    $response = $this->actingAs(adminUser())->get(route('stored-files.show', $pending));
    $response->assertOk();
    expect($response->streamedContent())->toBe('held pdf body')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline');
});

test('the sync does nothing while Drive is not connected', function () {
    useFallbackDrive(null);
    holdFile();

    Artisan::call('drive:sync-pending');

    expect(Artisan::output())->toContain('Google Drive is not connected; 1 upload(s) waiting.')
        ->and(PendingDriveUpload::query()->unsynced()->count())->toBe(1);
});

test('the sync moves held files into their Drive folder and repoints every record', function () {
    useFallbackDrive(null);
    $client = Client::factory()->create();

    $documentFile = holdFile('document body', 'assessment.pdf', '7_Jane Doe');
    $document = ClientDocument::factory()->for($client)->create([...$documentFile]);

    $resumeFile = holdFile('resume body', 'resume.pdf', '3_Sam Lee');
    $application = Application::factory()->create([
        'resume' => $resumeFile['drive_file_url'],
        'resume_drive_file_id' => $resumeFile['drive_file_id'],
    ]);

    $invoiceFile = holdFile('invoice body', 'invoice.pdf', 'client-invoices');
    $invoice = Invoice::factory()->create(['not_signed_invoice' => $invoiceFile['drive_web_view']]);

    $remote = new FakeRemoteDrive;
    useFallbackDrive($remote);

    Artisan::call('drive:sync-pending');

    expect(Artisan::output())->toContain('Moved 3 upload(s) to Google Drive; 0 failed, 0 waiting to retry, 0 still on the server.');
    expect($remote->uploads[0])->toBe(['name' => 'assessment.pdf', 'type' => 'client', 'folder' => '7_Jane Doe', 'contents' => 'document body']);

    expect($document->refresh()->drive_file_id)->toBe('drive-1')
        ->and($document->drive_file_url)->toBe('https://drive.google.com/uc?id=drive-1&export=download')
        ->and($document->drive_web_view)->toBe('https://drive.google.com/file/d/drive-1/view')
        ->and($application->refresh()->resume_drive_file_id)->toBe('drive-2')
        ->and($application->resume)->toBe('https://drive.google.com/uc?id=drive-2&export=download')
        ->and($invoice->refresh()->not_signed_invoice)->toBe('https://drive.google.com/file/d/drive-3/view');

    expect(Storage::disk('local')->allFiles())->toBe([]);

    // A link handed out while the file was held now leads to Drive.
    $pending = PendingDriveUpload::query()->where('drive_file_id', 'drive-1')->sole();
    $this->actingAs(adminUser())->get(route('stored-files.show', $pending))
        ->assertRedirect('https://drive.google.com/file/d/drive-1/view');
});

test('a fresh upload is left alone until its record has had time to save', function () {
    useFallbackDrive(null);
    app(DriveStorage::class)->upload(UploadedFile::fake()->create('fresh.pdf'), 'client', 'x');

    $remote = new FakeRemoteDrive;
    useFallbackDrive($remote);
    Artisan::call('drive:sync-pending');

    expect($remote->uploads)->toBe([])
        ->and(PendingDriveUpload::query()->unsynced()->count())->toBe(1);
});

test('failed moves back off and three failures in a row end the run', function () {
    useFallbackDrive(null);
    foreach (range(1, 4) as $i) {
        holdFile("file {$i}", "file{$i}.pdf");
    }

    useFallbackDrive(new FakeRemoteDrive(failing: true));
    Artisan::call('drive:sync-pending');

    expect(Artisan::output())->toContain('Stopped after 3 failures in a row');
    expect(PendingDriveUpload::query()->where('attempts', 1)->count())->toBe(3)
        ->and(PendingDriveUpload::query()->where('attempts', 0)->count())->toBe(1);

    // The failed ones wait out their backoff; --force retries them now.
    $remote = new FakeRemoteDrive;
    useFallbackDrive($remote);
    Artisan::call('drive:sync-pending');
    expect(Artisan::output())->toContain('Moved 1 upload(s) to Google Drive; 0 failed, 3 waiting to retry');

    Artisan::call('drive:sync-pending', ['--force' => true]);
    expect(PendingDriveUpload::query()->unsynced()->count())->toBe(0)
        ->and($remote->uploads)->toHaveCount(4);
});

test('deleting a held file removes it from the server', function () {
    useFallbackDrive(null);
    $uploaded = holdFile();
    $pending = PendingDriveUpload::query()->sole();

    app(DriveStorage::class)->delete($uploaded['drive_file_id']);

    Storage::disk('local')->assertMissing($pending->path);
    expect(PendingDriveUpload::query()->count())->toBe(0);
});

test('deleting a moved file deletes it on Drive', function () {
    $remote = new FakeRemoteDrive;
    useFallbackDrive($remote);
    $pending = PendingDriveUpload::factory()->synced()->create(['drive_file_id' => 'drive-9']);

    app(DriveStorage::class)->delete($pending->fileId());

    expect($remote->deleted)->toBe(['drive-9']);
});

test('generated PDFs held on the server can still be read back', function () {
    useFallbackDrive(null);
    $uploaded = holdFile('%PDF invoice');

    expect(app(StoredDocumentReader::class)->contents($uploaded['drive_web_view']))->toBe('%PDF invoice');
});

test('files left on the public disk are made private and queued for Drive', function () {
    useFallbackDrive(null);
    $client = Client::factory()->create();
    $path = 'drive/CATS/client/7-jane-doe/'.str_repeat('a', 40).'.pdf';
    Storage::disk('public')->put($path, 'legacy body');
    Storage::disk('public')->put('drive/CATS/client/orphan/'.str_repeat('b', 40).'.pdf', 'orphan');

    $url = Storage::disk('public')->url($path);
    $document = ClientDocument::factory()->for($client)->create([
        'title' => 'Old Assessment',
        'drive_file_id' => $path,
        'drive_file_url' => $url,
        'drive_web_view' => $url,
    ]);

    Artisan::call('drive:adopt-local-files', ['--dry-run' => true]);
    expect(Artisan::output())->toContain('Would queue 1 file(s) for Google Drive; 0 missing; 1 file(s) no record points at were left in place.');
    Storage::disk('public')->assertExists($path);

    Artisan::call('drive:adopt-local-files');

    $pending = PendingDriveUpload::query()->sole();
    expect($pending->original_name)->toBe('Old Assessment.pdf')
        ->and([$pending->target_type, $pending->target_name])->toBe(['client', '7-jane-doe'])
        ->and($document->refresh()->drive_file_id)->toBe($pending->fileId())
        ->and($document->drive_web_view)->toBe($pending->url());

    Storage::disk('public')->assertMissing($path);
    expect(Storage::disk('local')->get($pending->path))->toBe('legacy body');

    PendingDriveUpload::query()->update(['created_at' => now()->subMinutes(5)]);
    $remote = new FakeRemoteDrive;
    useFallbackDrive($remote);
    Artisan::call('drive:sync-pending');

    expect($document->refresh()->drive_file_id)->toBe('drive-1')
        ->and($remote->uploads[0]['name'])->toBe('Old Assessment.pdf');
});

test('adopting refuses when Drive is not configured', function () {
    app()->instance(DriveStorage::class, new LocalDriveStorage);

    expect(Artisan::call('drive:adopt-local-files'))->toBe(1);
});
