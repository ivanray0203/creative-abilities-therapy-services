<?php

namespace App\Services\GoogleDrive;

use App\Models\PendingDriveUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The DriveStorage bound whenever Google Drive is configured, so an upload is
 * never lost to Drive being unavailable.
 *
 * Uploads go straight to Drive when it is connected. When it isn't (no admin
 * has connected the account yet, or the stored token stopped working) or the
 * upload fails for any other reason, the file is held on the private `local`
 * disk as a PendingDriveUpload and the caller gets a `pending:{uuid}` id and
 * a link to StoredFileController in the usual shape. `drive:sync-pending`
 * moves it into the same Drive folder later and repoints the records (see
 * StoredFileReferences).
 *
 * Also reads and deletes files left on the public disk by LocalDriveStorage
 * before Drive was configured: their ids are the public-disk path.
 */
class FallbackDriveStorage implements DriveStorage
{
    public const PENDING_DIRECTORY = 'pending-drive';

    private const LEGACY_LOCAL_PREFIX = 'drive/';

    public function __construct(private readonly ?DriveStorage $remote) {}

    /** The Drive backend, or null while no account is connected. */
    public function remote(): ?DriveStorage
    {
        return $this->remote;
    }

    public function upload(UploadedFile $file, string $targetType, string $targetName): array
    {
        if ($this->remote !== null) {
            try {
                return $this->remote->upload($file, $targetType, $targetName);
            } catch (\Throwable $exception) {
                Log::error('Google Drive upload failed; holding the file until Drive is available.', [
                    'target_type' => $targetType,
                    'target_name' => $targetName,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $pending = $this->hold($file, $targetType, $targetName);
        $url = $pending->url();

        return [
            'drive_file_id' => $pending->fileId(),
            'drive_file_url' => $url,
            'drive_web_view' => $url,
        ];
    }

    public function get(string $fileId): ?string
    {
        if (($uuid = PendingDriveUpload::uuidFromFileId($fileId)) !== null) {
            $pending = PendingDriveUpload::query()->where('uuid', $uuid)->first();

            if ($pending === null) {
                return null;
            }

            if ($pending->isSynced()) {
                return $this->remote?->get((string) $pending->drive_file_id);
            }

            return Storage::disk('local')->get($pending->path);
        }

        if (str_starts_with($fileId, self::LEGACY_LOCAL_PREFIX)) {
            return Storage::disk('public')->get($fileId);
        }

        return $this->remote?->get($fileId);
    }

    public function delete(?string $fileId): void
    {
        if ($fileId === null || $fileId === '') {
            return;
        }

        if (($uuid = PendingDriveUpload::uuidFromFileId($fileId)) !== null) {
            $pending = PendingDriveUpload::query()->where('uuid', $uuid)->first();

            if ($pending === null) {
                return;
            }

            if ($pending->isSynced()) {
                $this->remote?->delete($pending->drive_file_id);
            } else {
                Storage::disk('local')->delete($pending->path);
            }

            $pending->delete();

            return;
        }

        if (str_starts_with($fileId, self::LEGACY_LOCAL_PREFIX)) {
            Storage::disk('public')->delete($fileId);

            return;
        }

        $this->remote?->delete($fileId);
    }

    /**
     * Copies the upload onto the private disk right away: callers such as
     * the invoice and offer-letter PDFs delete their temp file as soon as
     * upload() returns.
     */
    private function hold(UploadedFile $file, string $targetType, string $targetName): PendingDriveUpload
    {
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?? 'bin');

        $path = Storage::disk('local')->putFileAs(self::PENDING_DIRECTORY, $file, "{$uuid}.{$extension}");

        if ($path === false) {
            throw new \RuntimeException('Failed to hold the uploaded file on the local disk while Google Drive is unavailable.');
        }

        return PendingDriveUpload::query()->create([
            'uuid' => $uuid,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'target_type' => $targetType,
            'target_name' => $targetName,
        ]);
    }
}
