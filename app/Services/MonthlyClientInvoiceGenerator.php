<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The clinic's month-end invoice to each family.
 *
 * The therapists' per-client bills are the record of what was actually
 * delivered, so they are what the clinic's invoice is built from — but not at
 * the price the therapist charged. The clinic bills the family its own
 * published rate card, by the client's funding source: FSCD-funded care at
 * the FSCD rate, Insurance and private care at the private/insurance rate.
 *
 * Raised as a draft, not sent: an invoice to a parent goes out when an admin
 * decides it does, not because the month rolled over.
 */
class MonthlyClientInvoiceGenerator
{
    public function __construct(private ReferenceNumberGenerator $referenceNumbers) {}

    /**
     * One invoice per client with delivered work in the month.
     *
     * @return Collection<int, Invoice>
     */
    public function generateForMonth(CarbonInterface $month): Collection
    {
        return $this->workForMonth($month)
            ->select('client_id')
            ->distinct()
            ->pluck('client_id')
            ->filter()
            ->map(fn (int $clientId): ?Invoice => $this->generateFor($clientId, $month))
            ->filter()
            ->values();
    }

    /**
     * One client's invoice for one month, or null when nothing was delivered
     * to them. Re-running is safe: a month already invoiced for a client is
     * not invoiced again.
     */
    public function generateFor(int $clientId, CarbonInterface $month): ?Invoice
    {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        return DB::transaction(function () use ($clientId, $periodStart, $periodEnd): ?Invoice {
            if ($this->alreadyInvoiced($clientId, $periodStart)) {
                return null;
            }

            $client = Client::query()->with('originalIntake', 'billing')->find($clientId);

            if ($client === null) {
                return null;
            }

            $bills = $this->workForMonth($periodStart)
                ->where('client_id', $clientId)
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->get();

            $lines = $this->linesFrom($bills, $client);

            if ($lines === []) {
                return null;
            }

            $invoice = new Invoice([
                'client_id' => $client->id,
                'billing_account_id' => $client->billing?->id,
                'billed_by' => 'admin',
                'is_monthly' => true,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'invoice_date' => $periodEnd->toDateString(),
                'due_date' => $periodEnd->copy()->addDays(30)->toDateString(),
                'tax_percentage' => 0,
                'services' => $lines,
                // Draft on purpose: an admin reviews before the family is billed.
                'status' => 'draft',
                'timeline' => [[
                    'id' => (string) Str::uuid(),
                    'title' => 'Monthly invoice generated for '.$periodStart->format('F Y'),
                    'date' => now()->toDateString(),
                    'time' => now()->format('g:i:s A'),
                ]],
            ]);

            $invoice->invoice_id = $this->referenceNumbers->invoice();
            $invoice->calculateTotals();
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * A client is invoiced once per month. Regenerating would otherwise bill
     * the family twice for the same work.
     */
    private function alreadyInvoiced(int $clientId, CarbonInterface $periodStart): bool
    {
        return Invoice::query()
            ->where('client_id', $clientId)
            ->where('billed_by', 'admin')
            ->where('is_monthly', true)
            ->whereDate('period_start', $periodStart->toDateString())
            ->exists();
    }

    /**
     * The therapist bills that record work delivered in the month. They are
     * read as a record of what happened, not of what the family owes.
     *
     * @return Builder<Invoice>
     */
    private function workForMonth(CarbonInterface $month): Builder
    {
        return Invoice::query()
            // Without the model's default ordering: MySQL rejects a DISTINCT
            // whose ORDER BY column is not in the select list, and reorder()
            // cannot help because the global scope is applied at execution.
            ->withoutGlobalScope('orderByInvoiceDate')
            ->where('billed_by', 'therapist')
            ->where('is_monthly', false)
            ->whereDate('invoice_date', '>=', $month->copy()->startOfMonth()->toDateString())
            ->whereDate('invoice_date', '<=', $month->copy()->endOfMonth()->toDateString());
    }

    /**
     * Every delivered line, re-priced at the clinic's published rate for this
     * client's funding source.
     *
     * A line the rate card no longer carries — a hand-typed "Other", or a
     * retired line — keeps the rate it was delivered at rather than being
     * dropped or silently zeroed.
     *
     * @param  Collection<int, Invoice>  $bills
     * @return array<int, array<string, mixed>>
     */
    private function linesFrom(Collection $bills, Client $client): array
    {
        $stream = InvoiceService::fundingStream($client->originalIntake?->funding_source);
        $rateCard = InvoiceService::query()->get()->keyBy('id');

        return $bills
            ->flatMap(fn (Invoice $bill): array => collect($bill->services ?? [])
                ->map(function (array $line) use ($rateCard, $stream, $bill, $client): array {
                    $service = isset($line['invoice_service_id'])
                        ? $rateCard->get($line['invoice_service_id'])
                        : null;

                    $rate = (float) ($service?->rateFor($stream) ?? $line['rate_numeric'] ?? 0);

                    return [
                        ...$line,
                        'rate' => '$'.number_format($rate, 2),
                        'rate_numeric' => $rate,
                        'date' => $bill->invoice_date?->toDateString(),
                        'client' => $client->displayName(),
                    ];
                })
                ->all())
            ->values()
            ->all();
    }
}
