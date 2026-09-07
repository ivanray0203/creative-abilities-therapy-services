<?php

namespace App\Services\GoogleDrive;

use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Uploads go to the pre-existing "CATS" folder inside the root Drive
 * folder ("cats"), under `CATS/{target_type}/{target_name}/{file}` — e.g.
 * `CATS/client/{intake_id}_{client name}/document.pdf`. Folders are
 * created on demand like the reference's `get_or_create_folder`.
 *
 * The root is a regular Drive folder owned by a real Google account, not
 * a Shared Drive — Shared Drives require Google Workspace, which this
 * account doesn't have. Service accounts also can't be used here: they
 * have no storage quota of their own and can't own files outside a
 * Shared Drive. So this authenticates via OAuth as that real account
 * instead, using a refresh token obtained once through
 * Admin\GoogleDriveConnectionController and cached on disk (see
 * GoogleAccountClient) — AppServiceProvider falls back to LocalDriveStorage
 * until that one-time connection has been completed.
 */
class GoogleDriveService implements DriveStorage
{
    private const ROOT_FOLDER_NAME = 'CATS';

    private ?GoogleDrive $service = null;

    public function __construct(
        private readonly GoogleAccountClient $account,
        private readonly string $rootFolderId,
    ) {}

    public function upload(UploadedFile $file, string $targetType, string $targetName): array
    {
        $rootId = $this->getOrCreateStableFolder(self::ROOT_FOLDER_NAME, $this->rootFolderId);
        $typeFolderId = $this->getOrCreateStableFolder($targetType, $rootId);
        $folderId = $this->getOrCreateFolder($targetName, $typeFolderId);

        $driveFile = new DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => [$folderId],
        ]);

        $uploaded = $this->service()->files->create($driveFile, [
            'data' => file_get_contents($file->getRealPath()),
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink, webContentLink',
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
     * Downloads a file's contents. Drive's share links either force a
     * download or refuse to be framed, so anything that needs to render
     * in-page has to be streamed by us instead of linked to.
     */
    public function get(string $fileId): ?string
    {
        try {
            $response = $this->service()->files->get($fileId, ['alt' => 'media']);

            return (string) $response->getBody();
        } catch (\Throwable $exception) {
            Log::error('Google Drive download failed.', [
                'file_id' => $fileId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
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
            $this->service()->files->delete($fileId);
        } catch (\Throwable $exception) {
            Log::warning('Google Drive delete failed, trashing instead.', [
                'file_id' => $fileId,
                'error' => $exception->getMessage(),
            ]);

            try {
                $this->service()->files->update($fileId, new DriveFile(['trashed' => true]));
            } catch (\Throwable $trashException) {
                Log::error('Google Drive trash fallback also failed.', [
                    'file_id' => $fileId,
                    'error' => $trashException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Same as getOrCreateFolder(), but cached forever — for the small set
     * of folders (root "CATS" and the per-type folders under it) whose IDs
     * never change, so we don't hit the Drive API's listFiles() on every
     * single upload just to re-discover a folder we already know about.
     */
    private function getOrCreateStableFolder(string $name, string $parentId): string
    {
        return Cache::rememberForever(
            "gdrive_folder:{$parentId}:{$name}",
            fn () => $this->getOrCreateFolder($name, $parentId),
        );
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
        ]);

        return $created->getId();
    }

    private function service(): GoogleDrive
    {
        return $this->service ??= new GoogleDrive(
            $this->account->make([GoogleDrive::DRIVE]),
        );
    }
}
