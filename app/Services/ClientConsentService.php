<?php

namespace App\Services;

use App\Mail\ClientConsentMail;
use App\Mail\ClientConsentSignedAdminNotification;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\User;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * The therapy consent and family service agreement, from sent to signed.
 *
 * Once every service on the child's intake is approved, the parent is
 * emailed a link to a page where they download the consent package as a
 * PDF, sign it, and upload the signed copy back. The link is a temporary
 * signed URL, like the offer letter's, so the parent does not have to log
 * in to return it.
 *
 * The signed copy is filed as a ClientDocument in the client's Drive folder,
 * so it shows up on the admin Documents tab alongside everything else.
 */
class ClientConsentService
{
    public const DOCUMENT_TYPE = 'Signed Consent';

    public function __construct(
        private readonly PdfService $pdfService,
        private readonly DriveStorage $drive,
    ) {}

    /**
     * True once the intake has at least one service review and every one of
     * them is approved — nothing pending, reassigned or rejected.
     */
    public function allServicesApproved(Client $client): bool
    {
        $intake = $client->originalIntake;

        if ($intake === null) {
            return false;
        }

        $statuses = $intake->therapistReviews()->pluck('status');

        return $statuses->isNotEmpty() && $statuses->every(fn (string $status): bool => $status === 'approved');
    }

    /**
     * Sends the consent link the first time every service is approved. Later
     * approvals (a service added afterwards) do not resend on their own —
     * an admin can resend from the client page.
     */
    public function sendIfReady(Client $client): bool
    {
        if ($client->consent_sent_at !== null || $client->consent_signed_at !== null) {
            return false;
        }

        if (! $this->allServicesApproved($client)) {
            return false;
        }

        return $this->send($client);
    }

    /**
     * Emails the parent a fresh link with the consent PDF attached. Returns
     * false when the intake has no parent email to send to.
     */
    public function send(Client $client): bool
    {
        $email = $this->parentEmail($client);

        if ($email === null) {
            return false;
        }

        $client->forceFill(['consent_sent_at' => now()])->save();

        Mail::to($email)->send(new ClientConsentMail(
            $client,
            $this->signedUrl($client),
            $this->pdf($client),
        ));

        return true;
    }

    /** The consent package, prefilled with what the intake already holds. */
    public function pdf(Client $client): string
    {
        return $this->pdfService->clientConsent($client);
    }

    /**
     * Files the parent's signed copy — one scanned PDF, or several photos of
     * the signed pages — and marks the consent as returned.
     *
     * A second upload is the parent correcting a wrong file, so it replaces
     * the earlier one: leaving both would make the admin guess which copy is
     * the real one. The old files are only removed once the new ones are
     * safely filed.
     *
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, ClientDocument>
     */
    public function receiveSigned(Client $client, array $files): Collection
    {
        $replaced = $client->consent_signed_at !== null;
        $previous = $client->documents()->where('doc_type', self::DOCUMENT_TYPE)->get();
        $title = 'Signed Consent - '.$client->displayName();

        $documents = collect($files)->values()->map(function (UploadedFile $file, int $index) use ($client, $files, $title): ClientDocument {
            $uploaded = $this->drive->upload($file, 'client', $this->folderName($client));

            return $client->documents()->create([
                'intake_id' => $client->original_intake_id,
                'user_id' => $client->user_id,
                'title' => count($files) > 1 ? "{$title} (page ".($index + 1).')' : $title,
                'doc_type' => self::DOCUMENT_TYPE,
                ...$uploaded,
                'upload_origin' => 'client',
                'uploaded_at' => now(),
            ]);
        });

        foreach ($previous as $document) {
            $this->drive->delete($document->drive_file_id);
            $document->delete();
        }

        $client->forceFill(['consent_signed_at' => now()])->save();

        $this->notifyAdmins($client, $replaced);

        return $documents;
    }

    /**
     * A temporary signed URL. A signature covers the whole URL, so the page,
     * the PDF download and the upload each need their own.
     */
    public function signedUrl(Client $client, string $route = 'public.consent.show'): string
    {
        return URL::temporarySignedRoute(
            $route,
            ($client->consent_sent_at ?? now())->copy()->addDays($this->linkDays()),
            ['client' => $client->id],
        );
    }

    private function linkDays(): int
    {
        return (int) config('cats.consent.link_days', 30);
    }

    private function parentEmail(Client $client): ?string
    {
        $email = $client->originalIntake->primary_parent_email ?? $client->user?->email;

        return filled($email) ? trim($email) : null;
    }

    /** Matches ClientController::clientFolderName so every client file lands together. */
    private function folderName(Client $client): string
    {
        $intake = $client->originalIntake;
        $name = trim("{$intake?->child_first_name} {$intake?->child_last_name}");

        return trim("{$intake?->id}_{$name}", '_');
    }

    private function notifyAdmins(Client $client, bool $replaced): void
    {
        $adminEmails = User::query()
            ->role('admin')
            ->where('is_active', true)
            ->where('new_intake', true)
            ->pluck('email');

        if ($adminEmails->isEmpty()) {
            return;
        }

        try {
            Mail::to($adminEmails)->send(new ClientConsentSignedAdminNotification($client, $replaced));
        } catch (\Throwable $exception) {
            Log::error('Failed to queue a signed consent notification.', [
                'client_id' => $client->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
