<?php

use App\Models\Application;

/**
 * Moving resumes and cover letters filed with Drive's download link onto the
 * viewer link.
 *
 * The migration has already run against an empty database by the time these
 * start, so each test builds the old shape and runs `up()` again.
 *
 * @see database/migrations/2026_11_09_014720_repoint_application_documents_to_drive_web_view.php
 */
function runApplicationDocumentLinkBackfill(): void
{
    $migration = require database_path('migrations/2026_11_09_014720_repoint_application_documents_to_drive_web_view.php');

    $migration->up();
}

test('download links on resumes and cover letters become viewer links', function () {
    $application = Application::factory()->create([
        'resume' => 'https://drive.google.com/uc?id=resume_ID-1&export=download',
        'resume_drive_file_id' => 'resume_ID-1',
        'cover_letter' => 'https://drive.google.com/uc?id=cover_ID-2&export=download',
        'cover_letter_drive_file_id' => 'cover_ID-2',
    ]);

    runApplicationDocumentLinkBackfill();

    expect($application->refresh()->resume)->toBe('https://drive.google.com/file/d/resume_ID-1/view')
        ->and($application->cover_letter)->toBe('https://drive.google.com/file/d/cover_ID-2/view')
        ->and($application->resume_drive_file_id)->toBe('resume_ID-1')
        ->and($application->cover_letter_drive_file_id)->toBe('cover_ID-2');
});

test('an application filed before the id columns existed is still repointed', function () {
    $application = Application::factory()->create([
        'resume' => 'https://drive.google.com/uc?export=download&id=legacy-file-id',
        'resume_drive_file_id' => null,
    ]);

    runApplicationDocumentLinkBackfill();

    expect($application->refresh()->resume)->toBe('https://drive.google.com/file/d/legacy-file-id/view');
});

test('links that are not download links are left alone', function (?string $link) {
    $application = Application::factory()->create(['resume' => $link, 'cover_letter' => null]);

    runApplicationDocumentLinkBackfill();

    expect($application->refresh()->resume)->toBe($link)
        ->and($application->cover_letter)->toBeNull();
})->with([
    'viewer link' => 'https://drive.google.com/file/d/already-viewer/view?usp=drivesdk',
    'held on the server' => 'http://localhost/stored-files/9f2c7a10-1111-4222-8333-444455556666',
    'public disk' => 'http://localhost/storage/drive/CATS/applications/1-jane-doe/resume.pdf',
    'no file' => null,
]);
