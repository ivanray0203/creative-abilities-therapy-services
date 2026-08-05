<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ConsentDocument;
use App\Models\Intake;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;

/**
 * Ported from cats-backend/cats/utils/pdf_generator.py. ReportLab's
 * paragraph/spacer flowables become Blade views rendered through dompdf —
 * see resources/views/pdf/{offer-letter,consent}.blade.php.
 */
class PdfService
{
    public function offerLetter(Application $application): string
    {
        $deadline = $this->businessDaysFrom(now(), 5);

        return Pdf::loadView('pdf.offer-letter', [
            'application' => $application,
            'deadline' => $deadline,
            'issuedDate' => now(),
        ])->output();
    }

    /**
     * @return string|null Raw PDF bytes, or null when the intake recorded
     *                     no consent document IDs (nothing to render).
     *
     * `intake.consents` holds ConsentDocument IDs accepted at submission
     * time (the intake form runs before any account exists, so there's no
     * per-user UserConsentAcceptance row to join through reliably here —
     * the intake's own `created_at` stands in for the acceptance date).
     */
    public function consentPdfForIntake(Intake $intake): ?string
    {
        $documentIds = $intake->consents ?? [];

        if ($documentIds === []) {
            return null;
        }

        $documents = ConsentDocument::query()
            ->whereIn('id', $documentIds)
            ->with('clauses')
            ->get();

        if ($documents->isEmpty()) {
            return null;
        }

        return Pdf::loadView('pdf.consent', [
            'intake' => $intake,
            'documents' => $documents,
        ])->output();
    }

    /**
     * Mirrors the reference's `_business_days_from`: a plain day-by-day
     * loop skipping Saturday/Sunday, no holiday awareness.
     */
    private function businessDaysFrom(CarbonInterface $start, int $days): CarbonInterface
    {
        $current = $start;
        $added = 0;

        while ($added < $days) {
            $current = $current->addDay();

            if ($current->isWeekday()) {
                $added++;
            }
        }

        return $current;
    }
}
