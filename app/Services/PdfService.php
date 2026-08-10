<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Client;
use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\TeamMember;
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
