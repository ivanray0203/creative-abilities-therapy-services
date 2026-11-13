<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ClientDocument;
use App\Models\Complaint;
use App\Models\IntakeDocument;
use App\Models\User;
use App\Services\ClientContext;
use App\Services\StoredDocumentReader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

/**
 * Serves uploaded files under the app's own origin, so they open in the
 * in-app viewer instead of sending the viewer off to Google Drive, and the
 * storage path never reaches the page.
 *
 * Every action authorizes against the record holding the file, then reads
 * the bytes through StoredDocumentReader — Drive, a pending upload, or the
 * local disk alike. Pass `?download=1` to save the file instead of viewing it.
 */
class DocumentFileController extends Controller
{
    /**
     * Types the browser renders without running anything of the file's own.
     * Anything else (HTML, SVG, office files) is only ever sent as a download,
     * so an uploaded file can never execute script on this origin.
     */
    public const INLINE_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'text/plain',
    ];

    /** The application files an admin can open, keyed by their URL segment. */
    private const APPLICATION_FILES = [
        'resume' => ['column' => 'resume', 'label' => 'Resume'],
        'cover-letter' => ['column' => 'cover_letter', 'label' => 'Cover Letter'],
        'offer-letter' => ['column' => 'offer_letter', 'label' => 'Offer Letter'],
        'signed-offer-letter' => ['column' => 'signed_offer_letter', 'label' => 'Signed Offer Letter'],
    ];

    public function __construct(
        private readonly StoredDocumentReader $reader,
        private readonly ClientContext $clientContext,
    ) {}

    public function intakeDocument(Request $request, IntakeDocument $intakeDocument): Response
    {
        abort_unless($request->user()->isAdmin(), 404);

        return $this->serve($request, $intakeDocument->drive_web_view ?? $intakeDocument->drive_file_url, $intakeDocument->name);
    }

    /**
     * Client documents and team members' onboarding documents share the table:
     * the admin sees all of them, a team member only their own.
     */
    public function clientDocument(Request $request, ClientDocument $clientDocument): Response
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || ($clientDocument->user_id !== null && $clientDocument->user_id === $user->id), 404);

        return $this->serve($request, $clientDocument->drive_web_view ?? $clientDocument->drive_file_url, $clientDocument->title);
    }

    /** The same ownership the complaints list applies (ComplaintController::scopedQuery). */
    public function complaint(Request $request, Complaint $complaint): Response
    {
        abort_unless($this->canViewComplaint($request->user(), $complaint), 404);

        return $this->serve($request, $complaint->drive_web_view ?? $complaint->drive_file_url, "{$complaint->subject} attachment");
    }

    public function application(Request $request, Application $application, string $file): Response
    {
        abort_unless($request->user()->isAdmin(), 404);

        $definition = self::APPLICATION_FILES[$file] ?? abort(404);
        $name = trim("{$application->first_name} {$application->last_name} {$definition['label']}");

        return $this->serve($request, $application->{$definition['column']}, $name);
    }

    private function canViewComplaint(User $user, Complaint $complaint): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTherapist()) {
            return $complaint->complained_by === 'therapist' && $complaint->therapist_id === $user->id;
        }

        return $complaint->complained_by === 'client' && $this->clientContext->owns($user, $complaint->client_id);
    }

    private function serve(Request $request, ?string $storedUrl, ?string $name): Response
    {
        abort_if(blank($storedUrl), 404);

        $contents = $this->reader->contents($storedUrl);

        // The record has a file but it could not be fetched — usually the
        // Google Drive connection has lapsed. Say so in the viewer rather
        // than showing a bare 404.
        if ($contents === null) {
            return response()
                ->view('files.unavailable', ['isAdmin' => $request->user()->isAdmin()], 503)
                ->header('X-Frame-Options', 'SAMEORIGIN');
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
        $filename = $this->filename($name, $mimeType);
        $download = $request->boolean('download');
        $inline = ! $download && in_array($mimeType, self::INLINE_MIME_TYPES, true);

        // Viewing a file the browser cannot render (a Word document, say)
        // would only start a download inside the viewer's frame; explain and
        // offer the download instead.
        if (! $download && ! $inline) {
            return response()
                ->view('files.unpreviewable', [
                    'filename' => $filename,
                    'downloadUrl' => $request->fullUrlWithQuery(['download' => 1]),
                ])
                ->header('X-Frame-Options', 'SAMEORIGIN');
        }

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            // The in-app viewer frames this on our own origin.
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private function filename(?string $name, string $mimeType): string
    {
        $base = Str::slug(pathinfo((string) $name, PATHINFO_FILENAME)) ?: 'document';
        $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? 'bin';

        return "{$base}.{$extension}";
    }
}
