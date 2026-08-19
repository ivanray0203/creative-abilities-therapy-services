<?php

namespace App\Console\Commands;

use App\Services\MonthlyClientInvoiceGenerator;
use App\Services\MonthlyTherapistInvoiceGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Closes the billing month, on both sides of the ledger.
 *
 * Scheduled on the 1st, so it runs against the month that just ended:
 *
 *  - each therapist's per-client bills become one monthly statement to the
 *    clinic, sent to the admins on the spot;
 *  - each client gets the clinic's own monthly invoice for the work
 *    delivered to them, re-priced at the published rate card and left as a
 *    draft for an admin to review before the family is billed.
 *
 * Re-running is harmless — neither side bills the same work twice.
 */
#[Signature('invoices:generate-monthly {--month= : The month to close, as YYYY-MM. Defaults to last month.} {--only= : Limit to one side: therapist or client.}')]
#[Description('Close the billing month: therapist statements to the clinic, and the clinic\'s invoices to each family')]
class GenerateMonthlyTherapistInvoicesCommand extends Command
{
    public function handle(
        MonthlyTherapistInvoiceGenerator $therapistInvoices,
        MonthlyClientInvoiceGenerator $clientInvoices,
    ): int {
        $option = $this->option('month');

        $month = $option
            ? Carbon::createFromFormat('Y-m', (string) $option)?->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        if ($month === null || $month === false) {
            $this->error('Could not read --month. Use YYYY-MM, for example 2026-06.');

            return self::FAILURE;
        }

        $only = (string) ($this->option('only') ?? '');

        if (! in_array($only, ['', 'therapist', 'client'], true)) {
            $this->error('--only must be therapist or client.');

            return self::FAILURE;
        }

        $label = $month->format('F Y');

        if ($only !== 'client') {
            $statements = $therapistInvoices->generateForMonth($month);
            $this->info("Generated {$statements->count()} monthly invoice(s) for {$label}.");
        }

        // Runs second so a therapist's own statement is already on file, but
        // it reads the delivered work either way — the order does not change
        // what the family is billed.
        if ($only !== 'therapist') {
            $invoices = $clientInvoices->generateForMonth($month);
            $this->info("Generated {$invoices->count()} client invoice(s) for {$label}.");
        }

        return self::SUCCESS;
    }
}
