<?php

namespace App\Services\GoogleDrive;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Ported from cats-backend/cats/utils/google_drive.py. Uploads go to a
 * Shared Drive under `{target_type}/{target_name} - {Y-m-d}/{file}`,
 * created on demand exactly like the reference's `get_or_create_folder`.
 *
 * Bound only when Drive credentials are configured — see
 * AppServiceProvider, which falls back to LocalDriveStorage otherwise so
 * local dev/tests don't need a live Google service account.
 */
class GoogleDriveService implements DriveStorage
{
    private ?GoogleDrive $service = null;

    public function __construct(
        private readonly string $credentialsPath,
        private readonly string $sharedDriveId,
    ) {}

    public function upload(UploadedFile $file, string $targetType, string $targetName): array
    {
        $typeFolderId = $this->getOrCreateFolder($targetType, $this->sharedDriveId);
        $dateFolderName = "{$targetName} - ".now()->format('Y-m-d');
        $folderId = $this->getOrCreateFolder($dateFolderName, $typeFolderId);

        $driveFile = new DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => [$folderId],
        ]);

        $uploaded = $this->service()->files->create($driveFile, [
            'data' => file_get_contents($file->getRealPath()),
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink, webContentLink',
            'supportsAllDrives' => true,
        ]);

        if (! $uploaded instanceof DriveFile) {
            throw new \RuntimeException('Unexpected Google Drive API response while uploading.');
        }

        $webContentLink = $uploaded->getWebContentLink();

        return [
            'drive_file_id' => $uploaded->getId(),
            'drive_file_url' => $webContentLink !== '' ? $webContentLink : $uploaded->getWebViewLink(),
            'drive_web_view' => $uploaded->getWebViewLink(),
        ];
    }

    /**
     * Hard-delete, falling back to trashing the file when the delete call
     * fails (matches the reference's permission-denied fallback).
     */
    public function delete(?string $fileId): void
    {
        if ($fileId === null || $fileId === '') {
            return;
        }

        try {
            $this->service()->files->delete($fileId, ['supportsAllDrives' => true]);
        } catch (\Throwable $exception) {
            Log::warning('Google Drive delete failed, trashing instead.', [
                'file_id' => $fileId,
                'error' => $exception->getMessage(),
            ]);

            try {
                $this->service()->files->update($fileId, new DriveFile(['trashed' => true]), [
                    'supportsAllDrives' => true,
                ]);
            } catch (\Throwable $trashException) {
                Log::error('Google Drive trash fallback also failed.', [
                    'file_id' => $fileId,
                    'error' => $trashException->getMessage(),
                ]);
            }
        }
    }

    private function getOrCreateFolder(string $name, string $parentId): string
    {
        $escapedName = str_replace("'", "\\'", $name);
        $query = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
            $escapedName,
            $parentId,
        );

        $results = $this->service()->files->listFiles([
            'q' => $query,
            'corpora' => 'drive',
            'driveId' => $this->sharedDriveId,
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
            'fields' => 'files(id, name)',
        ]);

        $existing = $results->getFiles()[0] ?? null;

        if ($existing !== null) {
            return $existing->getId();
        }

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $created = $this->service()->files->create($folder, [
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);

        return $created->getId();
    }

    private function service(): GoogleDrive
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $client = new GoogleClient();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope(GoogleDrive::DRIVE);

        return $this->service = new GoogleDrive($client);
    }
}
