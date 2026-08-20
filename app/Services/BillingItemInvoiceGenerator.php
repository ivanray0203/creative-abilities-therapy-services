<?php

namespace App\Services;

use App\Models\BillingItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The clinic's invoice to a family, built from the bills an admin raised
 * for them over a chosen period.
 *
 * The billing items are the record of what the clinic billed and at what
 * rate, so they carry straight onto the invoice — unlike the month-end
 * generator, which re-prices a therapist's bills at the clinic's own card.
 * Every item that goes onto the invoice is stamped with its id, so no
 * period can bill the same work twice.
 *
 * Sent on the spot: generating it is the admin's decision to bill the
 * family, so the invoice goes out as `sent` and the caller emails it.
 */
class BillingItemInvoiceGenerator
{
    public function __construct(
        private ReferenceNumberGenerator $referenceNumbers,
        private InvoiceDocumentService $documents,
    ) {}

    /**
     * One invoice for everything billed to this client between the two
     * dates, or null when nothing is waiting to be invoiced.
     */
    public function generate(Client $client, CarbonInterface $from, CarbonInterface $to, User $issuedBy): ?Invoice
    {
        $invoice = DB::transaction(function () use ($client, $from, $to, $issuedBy): ?Invoice {
            $items = self::invoiceableFor($issuedBy)
                ->where('client_id', $client->id)
                ->whereDate('created_at', '>=', $from->toDateString())
                ->whereDate('created_at', '<=', $to->toDateString())
                // Locked for the length of the transaction: two admins
                // generating at once must not both claim the same lines.
                ->lockForUpdate()
                ->orderBy('created_at')
                ->orderBy('id')
                ->with('client.originalIntake')
                ->get();

            if ($items->isEmpty()) {
                return null;
            }

            $client->loadMissing('billing');

            $invoice = new Invoice([
                'client_id' => $client->id,
                'billing_account_id' => $client->billing?->id,
                'billed_by' => 'admin',
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'tax_percentage' => 0,
                'services' => $this->linesFrom($items),
                'status' => 'sent',
                'issued_by_id' => $issuedBy->id,
                'timeline' => [
                    $this->timelineEntry(
                        'Invoice generated from billing for '
                            .$from->format('M j, Y').' – '.$to->format('M j, Y'),
                    ),
                    $this->timelineEntry('Invoice sent'),
                ],
            ]);

            $invoice->invoice_id = $this->referenceNumbers->invoice();
            $invoice->calculateTotals();
            $invoice->save();

            BillingItem::query()
                ->whereIn('id', $items->pluck('id'))
                ->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        // Filed outside the transaction: the upload talks to Drive, and a
        // slow or failed call must not roll the invoice back.
        if ($invoice !== null) {
            $this->documents->storeUnsigned($invoice);
        }

        return $invoice;
    }

    /**
     * The therapist's own invoice to the clinic, for every service they
     * billed between the two dates — across all their clients, since it is
     * the clinic that owes them, not any one family.
     *
     * It is a statement in the same sense the month-end one is, so the admin
     * side sees it the same way (App\Http\Controllers\InvoiceController's
     * scopedQuery) and can recover it against what a family is charged.
     */
    public function generateForTherapist(User $therapist, CarbonInterface $from, CarbonInterface $to): ?Invoice
    {
        $invoice = DB::transaction(function () use ($therapist, $from, $to): ?Invoice {
            $items = self::invoiceableFor($therapist)
                ->whereDate('created_at', '>=', $from->toDateString())
                ->whereDate('created_at', '<=', $to->toDateString())
                ->lockForUpdate()
                ->orderBy('created_at')
                ->orderBy('id')
                ->with('client.originalIntake')
                ->get();

            if ($items->isEmpty()) {
                return null;
            }

            $invoice = new Invoice([
                'therapist_id' => $therapist->id,
                'billed_by' => 'therapist',
                // What reaches the clinic is a statement, never the single
                // bills behind it — the same rule the month-end run follows.
                'is_monthly' => true,
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'tax_percentage' => 0,
                'services' => $this->linesFrom($items),
                'status' => 'sent',
                'issued_by_id' => $therapist->id,
                // Addressed to the clinic, whose letterhead details already
                // live in config for the invoice PDF.
                'bill_to_name' => config('cats.invoice.legal_name'),
                'bill_to_address' => config('cats.invoice.street')."\n".config('cats.invoice.city_line'),
                'timeline' => [
                    $this->timelineEntry(
                        'Invoice generated from billing for '
                            .$from->format('M j, Y').' – '.$to->format('M j, Y'),
                    ),
                    $this->timelineEntry('Invoice sent to admin'),
                ],
            ]);

            $invoice->invoice_id = $this->referenceNumbers->invoice();
            $invoice->calculateTotals();
            $invoice->save();

            BillingItem::query()
                ->whereIn('id', $items->pluck('id'))
                ->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        // Filed outside the transaction: the upload talks to Drive, and a
        // slow or failed call must not roll the invoice back.
        if ($invoice !== null) {
            $this->documents->storeUnsigned($invoice);
        }

        return $invoice;
    }

    /**
     * @return array{id: string, title: string, date: string, time: string}
     */
    private function timelineEntry(string $title): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'date' => now()->toDateString(),
            'time' => now()->format('g:i:s A'),
        ];
    }

    /**
     * Billing items this user can still raise an invoice from: the ones on
     * their own side of the ledger, not yet on any invoice.
     *
     * The split matches what each side can see in Billing. A therapist bills
     * the clinic for their own lines; the clinic bills a family for the ones
     * its admins raised, never for a therapist's, which reach it as that
     * therapist's own invoice instead.
     *
     * @return Builder<BillingItem>
     */
    public static function invoiceableFor(User $user): Builder
    {
        return BillingItem::query()
            ->whereNull('invoice_id')
            ->when(
                $user->isAdmin(),
                fn (Builder $clinic) => $clinic->whereHas('issuedBy', fn (Builder $issuer) => $issuer->where('role', 'admin')),
                fn (Builder $own) => $own->where('issued_by_id', $user->id),
            );
    }

    /**
     * The billing items as invoice line items, in the same shape the invoice
     * form writes (app/Http/Controllers/InvoiceController.php). Each line
     * names the child it was for, so a statement spanning several reads.
     *
     * @param  Collection<int, BillingItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function linesFrom(Collection $items): array
    {
        return $items
            ->map(fn (BillingItem $item): array => [
                'invoice_service_id' => $item->invoice_service_id,
                'name' => $item->service_name,
                'description' => $item->notes,
                'period' => $item->created_at?->format('F Y') ?? now()->format('F Y'),
                'numberOfSessions' => (float) $item->quantity,
                'rate' => '$'.number_format((float) $item->rate, 2),
                'rate_numeric' => (float) $item->rate,
                'date' => $item->created_at?->toDateString(),
                'client' => $item->client?->displayName() ?? 'Client',
            ])
            ->values()
            ->all();
    }
}
