<?php

namespace App\Http\Controllers;

use App\Models\PendingDriveUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * The link a record holds while its file waits for Google Drive (see
 * FallbackDriveStorage). Shows the file from the private disk until it has
 * moved, then sends the viewer to it on Drive — so a link copied while the
 * file was pending still works afterwards.
 *
 * Signed-in users only. The uuid in the link is the only way to find a file,
 * which matches how Drive's own links are shared around the admin pages.
 */
class StoredFileController extends Controller
{
    public function __invoke(PendingDriveUpload $pendingDriveUpload): Response|RedirectResponse
    {
        if ($pendingDriveUpload->isSynced()) {
            return redirect()->away((string) $pendingDriveUpload->drive_web_view);
        }

        abort_unless(Storage::disk('local')->exists($pendingDriveUpload->path), 404);

        return Storage::disk('local')->response(
            $pendingDriveUpload->path,
            $pendingDriveUpload->original_name,
            array_filter(['Content-Type' => $pendingDriveUpload->mime_type]),
            'inline',
        );
    }
}
