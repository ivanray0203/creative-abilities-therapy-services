<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Client;
use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\TeamMember;
use App\Models\Timesheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;

/**
 * Ported from cats-backend/cats/utils/pdf_generator.py. ReportLab's
 * paragraph/spacer flowables become Blade views rendered through dompdf —
 * see resources/views/pdf/{offer-letter,consent}.blade.php.
 */
class PdfService
{
    /**
     * The offer letter, unsigned by default. Pass the candidate's signature
     * as a PNG data URI to render the signed copy — the same shape as
     * invoice(), so both signed documents are produced the same way.
     *
     * The deadline is the one stored on the application when the offer was
     * sent, so the printed date and the lifetime of the signing link agree.
     */
    public function offerLetter(Application $application, ?string $signature = null, ?CarbonInterface $signedAt = null): string
    {
        $issuedDate = $application->offer_sent_at ?? now();

        return Pdf::loadView('pdf.offer-letter', [
            'application' => $application,
            'signature' => $signature,
            'signedAt' => $signedAt ?? $application->offer_accepted_at,
            'issuedDate' => $issuedDate,
            'deadline' => $application->offer_expires_at ?? $issuedDate->copy()->addDays((int) config('cats.offer.acceptance_days', 5)),
            'candidateName' => trim("{$application->first_name} {$application->last_name}"),
            'candidateAddress' => $this->offerAddressLines($application),
            'serviceArea' => $application->position_applied,
            'legalName' => (string) config('cats.offer.legal_name'),
            'engagementType' => (string) config('cats.offer.engagement_type'),
            'location' => (string) config('cats.offer.location'),
            'schedule' => (string) config('cats.offer.schedule'),
            'reportsTo' => (string) config('cats.offer.reports_to'),
            'startDate' => $application->preferred_start_date,
        ])->output();
    }

    /**
     * The candidate's address as printed lines, skipping whatever they left
     * blank so a missing unit number does not leave a gap in the letterhead.
     *
     * @return array<int, string>
     */
    private function offerAddressLines(Application $application): array
    {
        $cityLine = collect([$application->city, $application->province, $application->zip_code])
            ->filter(fn (?string $part): bool => filled($part))
            ->implode(', ');

        return collect([$application->street_address, $application->address_line_2, $cityLine])
            ->filter(fn (?string $line): bool => filled($line))
            ->values()
            ->all();
    }

    /**
     * The admin client page as one document — the Overview, Sessions,
     * Funding, Notes and Therapist tabs in the order they appear on screen.
     */
    public function clientProfile(Client $client): string
    {
        $client->loadMissing([
            'originalIntake',
            'assignedTherapist',
            'careTeam',
            'clientServices.service',
            'clientServices.therapist',
            'sessions.therapist',
            'sessions.clientServices.service',
        ]);

        $intake = $client->originalIntake;
        $services = $client->clientServices;

        // Only what the funding source in play actually uses — an FSCD client
        // has no policy number, and printing empty insurance rows for them
        // reads as missing data rather than data that never applied.
        $funding = $intake !== null ? ($intake->funding_source_info ?? []) : [];
        $fundingDetails = match ($intake?->funding_source) {
            'Insurance' => [
                'Insurance Provider' => $funding['insurance_provider'] ?? null,
                'Policy Number' => $funding['policy_number'] ?? null,
                'Certificate Number' => $funding['certificate_number'] ?? null,
                'Policy Holder' => $funding['policy_holder_name'] ?? null,
            ],
            'private', null => [],
            default => [
                'FSCD Case Worker' => $funding['FSCD_case_worker_name'] ?? null,
                'FSCD Case Worker Email' => $funding['FSCD_case_worker_email'] ?? null,
                'FSCD Approval Start' => $funding['FSCD_approval_start_date'] ?? null,
                'FSCD Approval End' => $funding['FSCD_approval_end_date'] ?? null,
            ],
        };

        $availedNames = $services->map(fn ($service) => $service->service?->name)->filter();

        return Pdf::loadView('pdf.client', [
            'client' => $client,
            'intake' => $intake,
            'childName' => $client->displayName(),
            'services' => $services,
            'requestedServices' => collect($intake !== null ? ($intake->services_needed ?? []) : [])->reject(
                fn (string $service): bool => $availedNames->contains($service),
            )->values(),
            'sessions' => $client->sessions->sortByDesc('scheduled_start')->values(),
            'fundingDetails' => $fundingDetails,
            'notes' => collect($client->clinical_notes ?? []),
            'careTeam' => $client->careTeam,
            'generatedAt' => now(),
        ])->output();
    }

    /**
     * The whole team-member record as one printable sheet — everything the
     * admin edit screen holds, minus the SIN, which is stored one-way hashed
     * and cannot (and should not) be reproduced.
     */
    public function teamMemberProfile(TeamMember $teamMember): string
    {
        $teamMember->loadMissing('user');

        $address = collect([
            $teamMember->street_address,
            $teamMember->address_line_2,
            $teamMember->city,
            $teamMember->province,
            $teamMember->zip_code,
        ])->filter()->implode(', ');

        return Pdf::loadView('pdf.team-member', [
            'teamMember' => $teamMember,
            'fullName' => trim((string) $teamMember->user?->full_name) ?: "Team Member #{$teamMember->id}",
            'address' => $address,
            // Only rows an admin actually filled in are worth printing.
            'availability' => collect($teamMember->availability ?? [])
                ->filter(fn (array $slot): bool => filled($slot['time_from'] ?? null) || filled($slot['time_to'] ?? null))
                ->values(),
            'documents' => collect($teamMember->documents ?? []),
            'generatedAt' => now(),
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
     * The client-facing invoice (resources/views/pdf/invoice.blade.php),
     * matching the clinic's printed form.
     *
     * @param  string|null  $parentSignature  A data: URI for the parent's drawn
     *                                        signature. Null renders the empty
     *                                        signature box the parent signs.
     */
    public function invoice(Invoice $invoice, ?string $parentSignature = null): string
    {
        $invoice->loadMissing(['client.originalIntake', 'client.user']);
        $intake = $invoice->client?->originalIntake;

        $directorSignature = config('cats.invoice.clinical_director_signature');
        $directorPath = $directorSignature !== null && is_file(public_path($directorSignature))
            ? public_path($directorSignature)
            : null;

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'logoPath' => public_path('CatsLogo/web-app-manifest-192x192.png'),
            'billTo' => [
                // The invoice is addressed to whoever is billed, falling back
                // to the parent captured on the intake.
                'name' => $invoice->bill_to_name
                    ?: (optional($intake)->primary_parent_name ?? 'Parent / Guardian'),
                'address' => $this->invoiceAddressLines($invoice, $intake),
            ],
            'clientDetails' => [
                'name' => $invoice->client?->displayName() ?? 'Client',
                'date_of_birth' => $intake?->date_of_birth?->format('Y-M-d') ?? '',
                // The clinic's own file number for the child.
                'number' => $invoice->client?->id !== null ? (string) $invoice->client->id : '',
            ],
            'parentSignature' => $parentSignature,
            'directorSignature' => $directorPath,
        ])->output();
    }

    /**
     * The aide's FSCD time sheet (resources/views/pdf/timesheet.blade.php),
     * matching the printed form.
     *
     * The aide's signature is already on the record — they sign as they
     * generate — so only the parent's is passed in, the same shape invoice()
     * uses for theirs.
     *
     * @param  string|null  $parentSignature  A data: URI for the parent's drawn
     *                                        signature. Null renders the empty
     *                                        signature box the parent signs.
     */
    public function timesheet(Timesheet $timesheet, ?string $parentSignature = null): string
    {
        $timesheet->loadMissing(['client.originalIntake', 'client.user', 'therapist']);
        $intake = $timesheet->client?->originalIntake;
        $aide = $timesheet->therapist;

        return Pdf::loadView('pdf.timesheet', [
            'timesheet' => $timesheet,
            'logoPath' => public_path('CatsLogo/web-app-manifest-192x192.png'),
            'clientDetails' => [
                'name' => $timesheet->client?->displayName() ?? 'Client',
                'date_of_birth' => $intake?->date_of_birth?->format('Y-M-d') ?? '',
                // The funding reference captured at intake — an FSCD-funded
                // child's file number.
                'fscd_file_number' => $intake->funding_number ?? '',
            ],
            'aideName' => $aide !== null ? trim("{$aide->first_name} {$aide->last_name}") : 'Aide',
            'parentName' => $intake->primary_parent_name ?? 'Parent / Guardian',
            'aideSignature' => $timesheet->aide_signature,
            'parentSignature' => $parentSignature ?? $timesheet->parent_signature,
        ])->output();
    }

    /**
     * A therapist's invoice to the clinic.
     *
     * Its own layout, not the client invoice's: this one bills the clinic
     * across every child the therapist saw, so each line names the client and
     * there is no client block or parent signature box to fill in. Mirrors
     * the statement format the app already shows on screen
     * (resources/js/components/invoices/monthly-invoice-printable.tsx).
     */
    public function therapistInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing('therapist');
        $therapist = $invoice->therapist;

        return Pdf::loadView('pdf.therapist-invoice', [
            'invoice' => $invoice,
            'logoPath' => public_path('CatsLogo/web-app-manifest-192x192.png'),
            'therapist' => [
                'name' => $therapist !== null
                    ? trim("{$therapist->first_name} {$therapist->last_name}")
                    : 'Therapist',
                'email' => $therapist?->email,
            ],
        ])->output();
    }

    /**
     * Address lines for the BILL TO block: the address recorded on the
     * invoice if one was captured, otherwise the intake's.
     *
     * @return array<int, string>
     */
    private function invoiceAddressLines(Invoice $invoice, ?Intake $intake): array
    {
        if (filled($invoice->bill_to_address)) {
            return array_values(array_filter(
                preg_split('/

|
|
/', (string) $invoice->bill_to_address) ?: [],
                fn (string $line): bool => trim($line) !== '',
            ));
        }

        if ($intake === null) {
            return [];
        }

        return array_values(array_filter([
            $intake->street_address,
            $intake->address_line_2,
            trim(implode(', ', array_filter([$intake->city, $intake->state_province]))),
            $intake->postal_code,
        ], fn (?string $line): bool => filled($line)));
    }
}
