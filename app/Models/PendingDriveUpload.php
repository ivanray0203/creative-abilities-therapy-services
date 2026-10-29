<?php

namespace App\Models;

use Database\Factories\PendingDriveUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An upload held on the private `local` disk until Google Drive can take it
 * — see FallbackDriveStorage. Records that point at it carry
 * `pending:{uuid}` as their file id and the `stored-files.show` route as
 * their link until `drive:sync-pending` swaps in the Drive ones.
 *
 * @property Carbon|null $last_attempted_at
 * @property Carbon|null $synced_at
 */
#[Fillable([
    'uuid', 'path', 'original_name', 'mime_type', 'target_type', 'target_name', 'attempts',
    'last_error', 'last_attempted_at', 'synced_at', 'drive_file_id', 'drive_file_url', 'drive_web_view',
])]
class PendingDriveUpload extends Model
{
    /** @use HasFactory<PendingDriveUploadFactory> */
    use HasFactory;

    public const ID_PREFIX = 'pending:';

    /** The longest a failing upload waits between retries, in minutes. */
    public const MAX_BACKOFF_MINUTES = 360;

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Uploads still waiting for Drive.
     *
     * @param  Builder<PendingDriveUpload>  $query
     */
    #[Scope]
    protected function unsynced(Builder $query): void
    {
        $query->whereNull('synced_at');
    }

    /** The file id handed to the record that owns this upload. */
    public function fileId(): string
    {
        return self::ID_PREFIX.$this->uuid;
    }

    /** The link handed to the record that owns this upload. */
    public function url(): string
    {
        return route('stored-files.show', $this);
    }

    public function isSynced(): bool
    {
        return $this->synced_at !== null;
    }

    /**
     * Whether a failed upload has waited long enough to be retried: twice as
     * long after each failure, capped at MAX_BACKOFF_MINUTES.
     */
    public function isDueForRetry(): bool
    {
        if ($this->last_attempted_at === null || $this->attempts === 0) {
            return true;
        }

        $minutes = min(2 ** $this->attempts, self::MAX_BACKOFF_MINUTES);

        return $this->last_attempted_at->addMinutes($minutes)->isPast();
    }

    public static function uuidFromFileId(?string $fileId): ?string
    {
        if ($fileId === null || ! str_starts_with($fileId, self::ID_PREFIX)) {
            return null;
        }

        return substr($fileId, strlen(self::ID_PREFIX));
    }
}
