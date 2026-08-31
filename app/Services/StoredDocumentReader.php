<?php

namespace App\Services;

use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Support\Facades\Storage;

/**
 * The bytes behind a stored document URL, so the app can serve a filed PDF
 * under its own origin.
 *
 * Drive's own links either force a download (`webContentLink`) or refuse to
 * be framed (`webViewLink`), so the file is fetched rather than redirected
 * to. The file id is recovered from the URL, which keeps documents filed by
 * earlier versions readable. Local-disk URLs are read straight off the
 * public disk, which is what the test and offline configurations use.
 *
 * Shared by every document the app files and serves back — invoices, and
 * the aide time sheets that follow the same two-folder arrangement.
 */
class StoredDocumentReader
{
    public function __construct(private readonly DriveStorage $drive) {}

    public function contents(string $url): ?string
    {
        $localBase = rtrim(Storage::disk('public')->url(''), '/');

        if (str_starts_with($url, $localBase)) {
            $path = ltrim(substr($url, strlen($localBase)), '/');

            return Storage::disk('public')->exists($path)
                ? Storage::disk('public')->get($path)
                : null;
        }

        if (! preg_match('~(?:[?&]id=|/d/)([A-Za-z0-9_-]{10,})~', $url, $matches)) {
            return null;
        }

        return $this->drive->get($matches[1]);
    }
}
