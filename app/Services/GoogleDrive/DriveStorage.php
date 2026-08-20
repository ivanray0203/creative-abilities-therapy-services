<?php

namespace App\Services\GoogleDrive;

use Illuminate\Http\UploadedFile;

/**
 * Every document upload in the app (intake docs, client docs, complaints,
 * career application resumes/cover letters, team member documents, consent
 * PDFs) goes through this contract instead of ad hoc `Storage::disk()`
 * calls — see Phase 15.
 */
interface DriveStorage
{
    /**
     * @return array{drive_file_id: string, drive_file_url: string, drive_web_view: string}
     */
    public function upload(UploadedFile $file, string $targetType, string $targetName): array;

    /**
     * The raw bytes of a stored file, so the app can serve it under its own
     * origin. Null when the file cannot be read.
     */
    public function get(string $fileId): ?string;

    public function delete(?string $fileId): void;
}
