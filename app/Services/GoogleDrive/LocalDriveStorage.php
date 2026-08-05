<?php

namespace App\Services\GoogleDrive;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Drop-in stand-in for GoogleDriveService used whenever Drive credentials
 * aren't configured (local dev, tests). Mirrors the same folder convention
 * on the local `public` disk and returns fully-qualified URLs so callers
 * never need to know which backend is active — see AppServiceProvider.
 */
class LocalDriveStorage implements DriveStorage
{
    public function upload(UploadedFile $file, string $targetType, string $targetName): array
    {
        $folder = sprintf(
            'drive/CATS/%s/%s',
            Str::slug($targetType),
            Str::slug($targetName),
        );

        $path = Storage::disk('public')->putFile($folder, $file);

        if ($path === false) {
            throw new \RuntimeException("Failed to store the uploaded file under [{$folder}].");
        }

        $url = Storage::disk('public')->url($path);

        return [
            'drive_file_id' => $path,
            'drive_file_url' => $url,
            'drive_web_view' => $url,
        ];
    }

    public function delete(?string $fileId): void
    {
        if ($fileId === null || $fileId === '') {
            return;
        }

        Storage::disk('public')->delete($fileId);
    }
}
