<?php

namespace App\Http\Controllers;

use App\Models\PendingDriveUpload;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * The link a record holds while its file waits for Google Drive (see
 * FallbackDriveStorage). Shows the file from the private disk until it has
 * moved, then streams it from Drive — always under our own origin, so a link
 * copied while the file was pending still opens in the app afterwards.
 *
 * Signed-in users only. The uuid in the link is the only way to find a file.
 */
class StoredFileController extends Controller
{
    public function __invoke(PendingDriveUpload $pendingDriveUpload, DriveStorage $drive): Response
    {
        $disposition = in_array($pendingDriveUpload->mime_type, DocumentFileController::INLINE_MIME_TYPES, true)
            ? 'inline'
            : 'attachment';

        if ($pendingDriveUpload->isSynced()) {
            $contents = $drive->get($pendingDriveUpload->fileId());

            abort_if($contents === null, 404);

            return response($contents, 200, array_filter([
                'Content-Type' => $pendingDriveUpload->mime_type,
                'Content-Disposition' => $disposition.'; filename="'.addslashes((string) $pendingDriveUpload->original_name).'"',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]));
        }

        abort_unless(Storage::disk('local')->exists($pendingDriveUpload->path), 404);

        return Storage::disk('local')->response(
            $pendingDriveUpload->path,
            $pendingDriveUpload->original_name,
            array_filter(['Content-Type' => $pendingDriveUpload->mime_type, 'X-Content-Type-Options' => 'nosniff']),
            $disposition,
        );
    }
}
