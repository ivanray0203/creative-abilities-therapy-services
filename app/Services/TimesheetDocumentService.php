<?php

namespace App\Services;

use App\Models\Timesheet;
use App\Services\GoogleDrive\DriveStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Files each time sheet PDF on Drive and records where it landed.
 *
 * Two folders, mirroring the two states a form can be in — the same
 * arrangement InvoiceDocumentService uses for invoices:
 *
 *   CATS/Timesheet/not-signed/{client_id}_{client name}_timesheet.pdf
 *   CATS/Timesheet/signed/{client_id}_{client name}_signed_timesheet.pdf
 *
 * "not-signed" here means the parent has not signed; the aide always has,
 * since they sign as they generate.
 *
 * Both uploads are wrapped: a Drive outage must not cost the clinic a
 * timesheet or a parent their signature, since the record itself is already
 * saved.
 */
class TimesheetDocumentService
{
    private const FOLDER = 'Timesheet';

    private const UNSIGNED_FOLDER = 'not-signed';

    private const SIGNED_FOLDER = 'signed';

    public function __construct(
        private readonly PdfService $pdfService,
        private readonly DriveStorage $drive,
    ) {}

    /**
     * Renders the form as generated and files it under Timesheet/not-signed.
     */
    public function storeUnsigned(Timesheet $timesheet): ?string
    {
        $url = $this->upload(
            $timesheet,
            $this->pdfService->timesheet($timesheet),
            self::UNSIGNED_FOLDER,
            $this->filename($timesheet, 'timesheet'),
        );

        if ($url !== null) {
            $timesheet->forceFill(['not_signed_timesheet' => $url])->save();
        }

        return $url;
    }

    /**
     * Re-renders the form with the parent's signature drawn into their box
     * and files it under Timesheet/signed.
     *
     * @param  string  $parentSignature  A data: URI produced by the signature pad.
     */
    public function storeSigned(Timesheet $timesheet, string $parentSignature): ?string
    {
        $url = $this->upload(
            $timesheet,
            $this->pdfService->timesheet($timesheet, $parentSignature),
            self::SIGNED_FOLDER,
            $this->filename($timesheet, 'signed_timesheet'),
        );

        if ($url !== null) {
            $timesheet->forceFill(['signed_timesheet' => $url])->save();
        }

        return $url;
    }

    /**
     * `{client_id}_{client name}_{suffix}.pdf`, with the name slugged so a
     * child called "Anne-Marie O'Neil" cannot produce an awkward Drive name.
     */
    private function filename(Timesheet $timesheet, string $suffix): string
    {
        $clientId = $timesheet->client_id ?? 'unknown';
        $name = Str::slug($timesheet->client?->displayName() ?? 'client', '-');

        return "{$clientId}_{$name}_{$suffix}.pdf";
    }

    private function upload(Timesheet $timesheet, string $contents, string $folder, string $filename): ?string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'timesheet-pdf-');
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
            Log::error('Failed to file a timesheet PDF on Drive.', [
                'timesheet_id' => $timesheet->id,
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
