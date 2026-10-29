<?php

namespace App\Services\GoogleDrive;

use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Every column that records where an uploaded file lives, so a file can move
 * (pending → Drive) without each caller of DriveStorage::upload() knowing.
 *
 * Callers keep the array upload() returns in different shapes: the document
 * tables spread all three keys, applications keep the id beside one link,
 * and the generated PDFs (invoices, time sheets, offer letters) keep only
 * the web view. Each set below names its id column, if any, and which of
 * the two links each link column holds.
 *
 * A new place that stores an upload has to be listed here, or its file
 * keeps the pending link (which still works, see StoredFileController)
 * after the move.
 */
class StoredFileReferences
{
    public const FILE_URL = 'drive_file_url';

    public const WEB_VIEW = 'drive_web_view';

    /**
     * @var array<int, array{table: string, id: string|null, links: array<string, string>, name: string|null}>
     */
    public const SETS = [
        ['table' => 'intake_documents', 'id' => 'drive_file_id', 'links' => ['drive_file_url' => self::FILE_URL, 'drive_web_view' => self::WEB_VIEW], 'name' => 'name'],
        ['table' => 'client_documents', 'id' => 'drive_file_id', 'links' => ['drive_file_url' => self::FILE_URL, 'drive_web_view' => self::WEB_VIEW], 'name' => 'title'],
        ['table' => 'complaints', 'id' => 'drive_file_id', 'links' => ['drive_file_url' => self::FILE_URL, 'drive_web_view' => self::WEB_VIEW], 'name' => null],
        ['table' => 'applications', 'id' => 'resume_drive_file_id', 'links' => ['resume' => self::FILE_URL], 'name' => null],
        ['table' => 'applications', 'id' => 'cover_letter_drive_file_id', 'links' => ['cover_letter' => self::FILE_URL], 'name' => null],
        ['table' => 'applications', 'id' => 'offer_letter_drive_file_id', 'links' => ['offer_letter' => self::WEB_VIEW], 'name' => null],
        ['table' => 'applications', 'id' => 'signed_offer_letter_drive_file_id', 'links' => ['signed_offer_letter' => self::WEB_VIEW], 'name' => null],
        ['table' => 'invoices', 'id' => null, 'links' => ['not_signed_invoice' => self::WEB_VIEW], 'name' => null],
        ['table' => 'invoices', 'id' => null, 'links' => ['signed_invoice' => self::WEB_VIEW], 'name' => null],
        ['table' => 'timesheets', 'id' => null, 'links' => ['not_signed_timesheet' => self::WEB_VIEW], 'name' => null],
        ['table' => 'timesheets', 'id' => null, 'links' => ['signed_timesheet' => self::WEB_VIEW], 'name' => null],
    ];

    /**
     * Points every record holding the old file at the new one.
     *
     * A record matches on its id column equal to `$fromId`, or on a link
     * column ending in `$fromUrlSuffix` — a suffix, so the match survives an
     * APP_URL change between the upload and the move.
     *
     * @param  array{drive_file_id: string, drive_file_url: string, drive_web_view: string}  $to
     * @return int The number of records updated.
     */
    public function replace(string $fromId, string $fromUrlSuffix, array $to): int
    {
        $updated = 0;

        foreach (self::SETS as $set) {
            foreach ($this->matchingRows($set, $fromId, $fromUrlSuffix) as $row) {
                $values = [];

                if ($set['id'] !== null) {
                    $values[$set['id']] = $to['drive_file_id'];
                }

                foreach ($set['links'] as $column => $kind) {
                    $values[$column] = $to[$kind];
                }

                DB::table($set['table'])->where('id', $row->id)->update($values);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Rows of one set that hold the file. Link columns are narrowed in SQL
     * by the suffix's last segment (a uuid or a random filename, so no LIKE
     * wildcards) and confirmed in PHP, which keeps the match exact on every
     * database driver.
     *
     * @param  array{table: string, id: string|null, links: array<string, string>, name: string|null}  $set
     * @return array<int, stdClass>
     */
    public function matchingRows(array $set, string $fromId, string $fromUrlSuffix): array
    {
        $columns = array_values(array_filter(['id', $set['id'], $set['name'], ...array_keys($set['links'])]));
        $needle = basename($fromUrlSuffix);

        $candidates = DB::table($set['table'])
            ->select($columns)
            ->where(function ($query) use ($set, $fromId, $needle): void {
                if ($set['id'] !== null) {
                    $query->orWhere($set['id'], $fromId);
                }

                foreach (array_keys($set['links']) as $column) {
                    $query->orWhere($column, 'like', "%{$needle}");
                }
            })
            ->get();

        return $candidates
            ->filter(function (stdClass $row) use ($set, $fromId, $fromUrlSuffix): bool {
                if ($set['id'] !== null && $row->{$set['id']} === $fromId) {
                    return true;
                }

                foreach (array_keys($set['links']) as $column) {
                    if (is_string($row->{$column}) && str_ends_with($row->{$column}, $fromUrlSuffix)) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }
}
