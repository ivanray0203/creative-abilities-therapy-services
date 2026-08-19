<?php

namespace App\Services;

use App\Models\Invoice;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Files each invoice PDF on Drive and records where it landed.
 *
 * Two folders, mirroring the two states an invoice can be in:
 *
 *   CATS/Invoice/not-signed/{client_id}_{client name}_invoice.pdf
 *   CATS/Invoice/signed/{client_id}_{client name}_signed_invoice.pdf
 *
 * A therapist's invoice to the clinic is filed alongside the unsigned ones —
 * it is never signed — under its own invoice number and their name.
 *
 * The Drive URL is written to `not_signed_invoice` / `signed_invoice`. Both
 * uploads are wrapped: a Drive outage must not cost the clinic an invoice or
 * a parent their signature, since the record itself is already saved.
 */
class InvoiceDocumentService
{
    private const FOLDER = 'Invoice';

    private const UNSIGNED_FOLDER = 'not-signed';

    private const SIGNED_FOLDER = 'signed';

    public function __construct(
        private readonly PdfService $pdfService,
        private readonly DriveStorage $drive,
    ) {}

    /**
     * Renders the invoice as it stands and files it under Invoice/not-signed.
     * Called once the invoice has been raised, on either side of the ledger —
     * each in its own format.
     */
    public function storeUnsigned(Invoice $invoice): ?string
    {
        $contents = $invoice->billed_by === 'therapist'
            ? $this->pdfService->therapistInvoice($invoice)
            : $this->pdfService->invoice($invoice);

        $url = $this->upload(
            $invoice,
            $contents,
            self::UNSIGNED_FOLDER,
            $this->filename($invoice, 'invoice'),
        );

        if ($url !== null) {
            $invoice->forceFill(['not_signed_invoice' => $url])->save();
        }

        return $url;
    }

    /**
     * Re-renders the invoice with the parent's signature drawn into the
     * signature box and files it under Invoice/signed.
     *
     * @param  string  $signature  A data: URI produced by the signature pad.
     */
    public function storeSigned(Invoice $invoice, string $signature): ?string
    {
        $url = $this->upload(
            $invoice,
            $this->pdfService->invoice($invoice, $signature),
            self::SIGNED_FOLDER,
            $this->filename($invoice, 'signed_invoice'),
        );

        if ($url !== null) {
            $invoice->forceFill(['signed_invoice' => $url])->save();
        }

        return $url;
    }

    /**
     * `{client_id}_{client name}_{suffix}.pdf`, with the name slugged so a
     * child called "Anne-Marie O'Neil" cannot produce an awkward Drive name.
     *
     * A therapist bills the clinic across every child they saw, so there is
     * no one client to name the file after: it is filed under its invoice
     * number and the therapist instead.
     */
    private function filename(Invoice $invoice, string $suffix): string
    {
        if ($invoice->billed_by === 'therapist') {
            $reference = Str::slug((string) ($invoice->invoice_id ?? $invoice->id), '-');
            $therapist = $invoice->therapist;
            $therapistName = Str::slug(
                $therapist !== null ? "{$therapist->first_name} {$therapist->last_name}" : 'therapist',
                '-',
            );

            return "{$reference}_{$therapistName}_{$suffix}.pdf";
        }

        $clientId = $invoice->client_id ?? 'unknown';
        $name = Str::slug($invoice->client?->displayName() ?? 'client', '-');

        return "{$clientId}_{$name}_{$suffix}.pdf";
    }

    private function upload(Invoice $invoice, string $contents, string $folder, string $filename): ?string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'invoice-pdf-');
        file_put_contents($tempPath, $contents);

        try {
            $uploaded = $this->drive->upload(
                new UploadedFile($tempPath, $filename, 'application/pdf', null, true),
                self::FOLDER,
                $folder,
            );

            // `drive_file_url` is Drive's webContentLink, which forces a
            // download; the web view opens the PDF in the browser instead.
            return $uploaded['drive_web_view'];
        } catch (\Throwable $exception) {
            Log::error('Failed to file an invoice PDF on Drive.', [
                'invoice_id' => $invoice->id,
                'folder' => $folder,
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
}
