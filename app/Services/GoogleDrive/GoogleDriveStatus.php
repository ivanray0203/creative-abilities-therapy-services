<?php

namespace App\Services\GoogleDrive;

use App\Models\PendingDriveUpload;

/**
 * Whether Google Drive is set up, for the admin layout's "connect Google
 * Drive" prompt. Mirrors the checks AppServiceProvider uses to pick the
 * DriveStorage binding.
 */
class GoogleDriveStatus
{
    /** The app has Drive credentials, so an admin can connect an account. */
    public function isConfigured(): bool
    {
        return filled(config('services.google_drive.client_id'))
            && filled(config('services.google_drive.client_secret'))
            && filled(config('services.google_drive.shared_drive_id'));
    }

    /** An admin has completed the one-time connection. */
    public function isConnected(): bool
    {
        return is_file((string) config('services.google_drive.token_path'));
    }

    /**
     * @return array{configured: bool, connected: bool, pending_uploads: int}
     */
    public function toArray(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'connected' => $this->isConnected(),
            'pending_uploads' => PendingDriveUpload::query()->unsynced()->count(),
        ];
    }
}
