<?php

namespace App\Console\Commands;

use App\Models\PendingDriveUpload;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\GoogleDrive\FallbackDriveStorage;
use App\Services\GoogleDrive\StoredFileReferences;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;

/**
 * One-time move for files uploaded while Google Drive was not configured at
 * all: LocalDriveStorage left those on the public disk under `drive/CATS/`,
 * reachable by anyone with the link, and nothing ever moves them to Drive.
 *
 * Each file a record still points at is moved to the private disk and queued
 * as a PendingDriveUpload, and its records are repointed at the pending link,
 * so it stops being public right away; `drive:sync-pending` then uploads it
 * like any other held file. The Drive folder comes from the stored path, so
 * it carries LocalDriveStorage's slugged names (e.g. `12-jane-doe`). Files no
 * record points at are left where they are and only counted.
 */
#[Signature('drive:adopt-local-files
    {--dry-run : Show what would be moved without changing anything}')]
#[Description('Queue files left on the public disk before Google Drive was set up for upload to Drive')]
class AdoptLocalDriveFilesCommand extends Command
{
    private const LEGACY_DIRECTORY = 'drive';

    public function handle(DriveStorage $storage, StoredFileReferences $references): int
    {
        if (! $storage instanceof FallbackDriveStorage) {
            $this->error('Google Drive is not configured (GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, SHARED_DRIVE_ID), so these files have nowhere to go yet.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $files = $this->referencedFiles();
        $adopted = 0;
        $missing = 0;

        foreach ($files as $path => $name) {
            if (! $public->exists($path)) {
                $missing++;
                $this->warn("Missing from the server, skipped: {$path}");

                continue;
            }

            if (! $dryRun) {
                $this->adopt($path, $name, $references);
            }

            $adopted++;
        }

        $unreferenced = count(array_diff($public->allFiles(self::LEGACY_DIRECTORY), array_keys($files)));

        $verb = $dryRun ? 'Would queue' : 'Queued';
        $this->info("{$verb} {$adopted} file(s) for Google Drive; {$missing} missing; {$unreferenced} file(s) no record points at were left in place.");

        if (! $dryRun && $adopted > 0) {
            $this->line('Run `php artisan drive:sync-pending` (or wait for the schedule) to upload them.');
        }

        return self::SUCCESS;
    }

    private function adopt(string $path, string $name, StoredFileReferences $references): void
    {
        $uuid = (string) Str::uuid();
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'bin';
        $heldPath = FallbackDriveStorage::PENDING_DIRECTORY."/{$uuid}.{$extension}";
        [$targetType, $targetName] = $this->folderFor($path);

        Storage::disk('local')->writeStream($heldPath, Storage::disk('public')->readStream($path));

        DB::transaction(function () use ($uuid, $heldPath, $name, $path, $targetType, $targetName, $references): void {
            $pending = PendingDriveUpload::query()->create([
                'uuid' => $uuid,
                'path' => $heldPath,
                'original_name' => $name,
                'mime_type' => Storage::disk('public')->mimeType($path) ?: null,
                'target_type' => $targetType,
                'target_name' => $targetName,
            ]);

            $url = $pending->url();

            $references->replace($path, "/storage/{$path}", [
                'drive_file_id' => $pending->fileId(),
                'drive_file_url' => $url,
                'drive_web_view' => $url,
            ]);
        });

        Storage::disk('public')->delete($path);
    }

    /**
     * Public-disk paths that records still point at, with the name to give
     * each file on Drive: the record's own title when it has one, since
     * LocalDriveStorage stored files under a random name.
     *
     * @return array<string, string>
     */
    private function referencedFiles(): array
    {
        $files = [];
        $prefix = self::LEGACY_DIRECTORY.'/';

        foreach (StoredFileReferences::SETS as $set) {
            $columns = array_values(array_filter(['id', $set['id'], $set['name'], ...array_keys($set['links'])]));

            $rows = DB::table($set['table'])
                ->select($columns)
                ->where(function ($query) use ($set, $prefix): void {
                    if ($set['id'] !== null) {
                        $query->orWhere($set['id'], 'like', "{$prefix}%");
                    }

                    foreach (array_keys($set['links']) as $column) {
                        $query->orWhere($column, 'like', "%/storage/{$prefix}%");
                    }
                })
                ->get();

            foreach ($rows as $row) {
                $path = $this->legacyPath($row, $set);

                if ($path !== null && ! isset($files[$path])) {
                    $files[$path] = $this->fileName($path, $set['name'] !== null ? $row->{$set['name']} : null);
                }
            }
        }

        return $files;
    }

    /**
     * @param  array{table: string, id: string|null, links: array<string, string>, name: string|null}  $set
     */
    private function legacyPath(stdClass $row, array $set): ?string
    {
        $prefix = self::LEGACY_DIRECTORY.'/';

        if ($set['id'] !== null && is_string($row->{$set['id']}) && str_starts_with($row->{$set['id']}, $prefix)) {
            return $row->{$set['id']};
        }

        foreach (array_keys($set['links']) as $column) {
            $value = $row->{$column};

            if (is_string($value) && ($position = strpos($value, "/storage/{$prefix}")) !== false) {
                return substr($value, $position + strlen('/storage/'));
            }
        }

        return null;
    }

    private function fileName(string $path, ?string $title): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (blank($title)) {
            return basename($path);
        }

        return $extension !== '' && ! Str::endsWith(Str::lower($title), '.'.Str::lower($extension))
            ? "{$title}.{$extension}"
            : $title;
    }

    /**
     * The Drive folder from LocalDriveStorage's `drive/CATS/{type}/{name}/…`.
     *
     * @return array{0: string, 1: string}
     */
    private function folderFor(string $path): array
    {
        $segments = explode('/', $path);

        return count($segments) >= 5
            ? [$segments[2], $segments[3]]
            : ['unsorted', 'unknown'];
    }
}
