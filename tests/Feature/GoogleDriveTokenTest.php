<?php

use App\Services\GoogleDrive\GoogleAccountClient;
use App\Services\GoogleDrive\GoogleDriveService;
use Illuminate\Http\UploadedFile;

function driveTokenFile(array|string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'drive-token-');
    file_put_contents($path, is_string($contents) ? $contents : json_encode($contents));

    return $path;
}

function driveServiceWithToken(string $tokenPath): GoogleDriveService
{
    return new GoogleDriveService(new GoogleAccountClient('test-client-id', 'test-client-secret', $tokenPath), 'root-folder-id');
}

function samplePdfUpload(): UploadedFile
{
    return UploadedFile::fake()->create('offer-letter.pdf', 1, 'application/pdf');
}

test('a stored token that is not a token says to reconnect instead of throwing Invalid token format', function () {
    // Exactly what a refused refresh used to leave behind on disk.
    $tokenPath = driveTokenFile([
        'error' => 'invalid_grant',
        'error_description' => 'Token has been expired or revoked.',
        'refresh_token' => 'a-revoked-refresh-token',
    ]);

    expect(fn () => driveServiceWithToken($tokenPath)->upload(samplePdfUpload(), 'applications', '7_Test Candidate'))
        ->toThrow(RuntimeException::class, 'reconnect via the admin Google Drive settings');

    unlink($tokenPath);
});

test('an unreadable token file is refused rather than handed to the Google client', function () {
    foreach (['', 'not json at all', '{"access_token": null}'] as $contents) {
        $tokenPath = driveTokenFile($contents);

        expect(fn () => driveServiceWithToken($tokenPath)->upload(samplePdfUpload(), 'applications', '7_Test Candidate'))
            ->toThrow(RuntimeException::class, 'is not a usable token');

        unlink($tokenPath);
    }
});

test('a refused token is left on disk exactly as it was, so the refresh token survives a reconnect', function () {
    $payload = [
        'error' => 'invalid_grant',
        'error_description' => 'Token has been expired or revoked.',
        'refresh_token' => 'a-revoked-refresh-token',
    ];

    $tokenPath = driveTokenFile($payload);
    $before = file_get_contents($tokenPath);

    try {
        driveServiceWithToken($tokenPath)->upload(samplePdfUpload(), 'applications', '7_Test Candidate');
    } catch (RuntimeException) {
        // The throw is the subject of the test above.
    }

    expect(file_get_contents($tokenPath))->toBe($before);

    unlink($tokenPath);
});
