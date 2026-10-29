<?php

namespace App\Console\Commands;

use App\Models\PendingDriveUpload;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\GoogleDrive\FallbackDriveStorage;
use App\Services\GoogleDrive\StoredFileReferences;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Moves uploads held on the server (see FallbackDriveStorage) into Google
 * Drive once it is connected: each one goes into the same Drive folder it
 * would have gone to directly, every record pointing at it is repointed at
 * Drive, and the server copy is deleted.
 *
 * Runs on the schedule and right after an admin connects Drive. Does nothing
 * while Drive is not connected. A file that fails waits twice as long before
 * each retry (up to six hours; `--force` ignores the wait), and three
 * failures in a row end the run, since that usually means Drive itself is
 * down rather than one bad file.
 */
#[Signature('drive:sync-pending
    {--limit=200 : The most uploads to move in one run}
    {--force : Retry failed uploads now instead of waiting out their backoff}')]
#[Description('Move uploads held on the server while Google Drive was unavailable into Google Drive')]
class SyncPendingDriveUploadsCommand extends Command
{
    /** Leaves a fresh upload alone until the record that owns it has been saved. */
    private const GRACE_SECONDS = 60;

    private const MAX_CONSECUTIVE_FAILURES = 3;

    public function handle(DriveStorage $storage, StoredFileReferences $references): int
    {
        $waiting = PendingDriveUpload::query()->unsynced()->count();

        if (! $storage instanceof FallbackDriveStorage) {
            $this->warn('Google Drive is not configured; nothing to sync.');

            return self::SUCCESS;
        }

        $remote = $storage->remote();

        if ($remote === null) {
            $this->warn("Google Drive is not connected; {$waiting} upload(s) waiting.");

            return self::SUCCESS;
        }

        $moved = 0;
        $failed = 0;
        $deferred = 0;
        $consecutiveFailures = 0;

        $pendingUploads = PendingDriveUpload::query()
            ->unsynced()
            ->where('created_at', '<=', now()->subSeconds(self::GRACE_SECONDS))
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($pendingUploads as $pending) {
            if (! $this->option('force') && ! $pending->isDueForRetry()) {
                $deferred++;

                continue;
            }

            try {
                $this->move($pending, $remote, $references);
                $moved++;
                $consecutiveFailures = 0;
            } catch (\Throwable $exception) {
                $pending->update([
                    'attempts' => $pending->attempts + 1,
                    'last_error' => $exception->getMessage(),
                    'last_attempted_at' => now(),
                ]);

                $failed++;
                $consecutiveFailures++;
                $this->warn("Failed {$pending->original_name} ({$pending->uuid}): {$exception->getMessage()}");

                if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                    $this->warn('Stopped after '.self::MAX_CONSECUTIVE_FAILURES.' failures in a row; Google Drive looks unavailable.');

                    break;
                }
            }
        }

        $remaining = PendingDriveUpload::query()->unsynced()->count();
        $this->info("Moved {$moved} upload(s) to Google Drive; {$failed} failed, {$deferred} waiting to retry, {$remaining} still on the server.");

        return self::SUCCESS;
    }

    private function move(PendingDriveUpload $pending, DriveStorage $remote, StoredFileReferences $references): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($pending->path)) {
            throw new \RuntimeException("The held file [{$pending->path}] is missing from the server.");
        }

        $uploaded = $remote->upload(
            new UploadedFile($disk->path($pending->path), $pending->original_name, $pending->mime_type, null, true),
            $pending->target_type,
            $pending->target_name,
        );

        $repointed = DB::transaction(function () use ($pending, $uploaded, $references): int {
            $pending->update([
                ...$uploaded,
                'synced_at' => now(),
                'last_error' => null,
            ]);

            return $references->replace($pending->fileId(), "/stored-files/{$pending->uuid}", $uploaded);
        });

        $disk->delete($pending->path);

        $this->line("Moved {$pending->original_name} to {$pending->target_type}/{$pending->target_name} ({$repointed} record(s) updated).");
    }
}
