<?php

namespace App\Services;

use App\Mail\OfferAcceptedAdminNotification;
use App\Mail\OfferLetterMail;
use App\Models\Application;
use App\Models\User;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * The offer letter, from sent to signed.
 *
 * The candidate has no account at this point — the account is what a hire
 * creates — so they sign through a temporary signed URL instead of logging
 * in. The link's lifetime is the letter's own deadline, so the offer and the
 * link expire together.
 *
 * Two Drive files, mirroring how invoices are filed:
 *
 *   applications/{id}_{name}/offer-letter.pdf
 *   applications/{id}_{name}/signed-offer-letter.pdf
 *
 * A null `signed_offer_letter` is what "still awaiting the candidate's
 * signature" means.
 */
class OfferLetterService
{
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly DriveStorage $drive,
    ) {}

    /**
     * Renders the letter, files the unsigned copy, and emails the candidate a
     * link to sign it. Stamping the dates first means the PDF and the link
     * both read the deadline off the record.
     */
    public function send(Application $application): void
    {
        $sentAt = now();

        $application->forceFill([
            'offer_sent_at' => $sentAt,
            'offer_expires_at' => $sentAt->copy()->addDays($this->acceptanceDays())->endOfDay(),
            'offer_accepted_at' => null,
            'offer_declined_at' => null,
            'signed_offer_letter' => null,
            'signed_offer_letter_drive_file_id' => null,
        ])->save();

        $contents = $this->pdfService->offerLetter($application);

        $this->file($application, $contents, 'offer-letter.pdf', 'offer_letter');

        Mail::to($application->email)->send(new OfferLetterMail($application, $this->signedUrl($application), $contents));
    }

    /**
     * The candidate's signature, stamped into the letter and filed as a
     * separate document — the unsigned original stays exactly as it was sent.
     */
    public function accept(Application $application, string $signature): bool
    {
        $acceptedAt = now();

        $contents = $this->pdfService->offerLetter($application, $signature, $acceptedAt);

        $url = $this->file($application, $contents, 'signed-offer-letter.pdf', 'signed_offer_letter');

        if ($url === null) {
            return false;
        }

        $application->forceFill(['offer_accepted_at' => $acceptedAt])->save();

        $this->notifyAdmins($application);

        return true;
    }

    public function decline(Application $application): void
    {
        $application->forceFill([
            'offer_declined_at' => now(),
            'application_status' => 'declined',
            'declined' => true,
        ])->save();

        $this->notifyAdmins($application, declined: true);
    }

    /**
     * A temporary signed URL, valid until the offer's own deadline. No token
     * table: the signature on the URL is the credential, and it stops working
     * the moment the offer lapses.
     *
     * A signature covers the whole URL, so accepting and declining each need
     * their own — the one minted for the page will not validate on a
     * different path.
     */
    public function signedUrl(Application $application, string $route = 'public.offer.show'): string
    {
        return URL::temporarySignedRoute(
            $route,
            $application->offer_expires_at ?? now()->addDays($this->acceptanceDays()),
            ['application' => $application->id],
        );
    }

    private function acceptanceDays(): int
    {
        return (int) config('cats.offer.acceptance_days', 5);
    }

    /**
     * Uploads a rendered PDF to the application's own Drive folder and
     * records where it landed. Wrapped: a Drive outage must not cost the
     * candidate their signature, since the record itself is already saved.
     */
    private function file(Application $application, string $contents, string $filename, string $column): ?string
    {
        $folder = "{$application->id}_{$application->first_name} {$application->last_name}";
        $tempPath = tempnam(sys_get_temp_dir(), 'offer-letter-');
        file_put_contents($tempPath, $contents);

        try {
            $uploaded = $this->drive->upload(
                new UploadedFile($tempPath, $filename, 'application/pdf', null, true),
                'applications',
                $folder,
            );

            // `drive_file_url` is Drive's webContentLink, which forces a
            // download; the web view opens the PDF in the browser instead.
            $url = $uploaded['drive_web_view'];

            $application->forceFill([
                $column => $url,
                "{$column}_drive_file_id" => $uploaded['drive_file_id'],
            ])->save();

            return $url;
        } catch (\Throwable $exception) {
            Log::error('Failed to file an offer letter PDF on Drive.', [
                'application_id' => $application->id,
                'filename' => $filename,
                'error' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function notifyAdmins(Application $application, bool $declined = false): void
    {
        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where('new_applications', true)
            ->pluck('email');

        if ($adminEmails->isEmpty()) {
            return;
        }

        try {
            Mail::to($adminEmails)->send(new OfferAcceptedAdminNotification($application, $declined));
        } catch (\Throwable $exception) {
            Log::error('Failed to queue an offer decision notification.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
