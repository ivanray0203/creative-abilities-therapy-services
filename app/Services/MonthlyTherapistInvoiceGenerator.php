<?php

namespace App\Services;

use App\Mail\InvoiceResendMail;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Rolls a therapist's per-client bills for one calendar month into the single
 * monthly statement the clinic is billed with.
 *
 * A therapist raises a bill per client as the work happens; those stay on
 * their own side of the wall. At month end every bill dated inside the month
 * becomes one line — date, client, service, rate, quantity — on a statement
 * addressed to the clinic, which is what the admin finally sees.
 */
class MonthlyTherapistInvoiceGenerator
{
    public function __construct(private ReferenceNumberGenerator $referenceNumbers) {}

    /**
     * Generate statements for every therapist with unbilled work in the month.
     *
     * @return Collection<int, Invoice>
     */
    public function generateForMonth(CarbonInterface $month): Collection
    {
        $therapistIds = $this->billsForMonth($month)
            ->select('therapist_id')
            ->distinct()
            ->pluck('therapist_id')
            ->filter();

        return $therapistIds
            ->map(fn (int $therapistId): ?Invoice => $this->generateFor($therapistId, $month))
            ->filter()
            ->values();
    }

    /**
     * One therapist's statement for one month, or null when they billed
     * nothing in it. Re-running is safe: a bill already carrying a
     * `monthly_invoice_id` is not picked up again.
     */
    public function generateFor(int $therapistId, CarbonInterface $month): ?Invoice
    {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        return DB::transaction(function () use ($therapistId, $periodStart, $periodEnd): ?Invoice {
            $bills = $this->billsForMonth($periodStart)
                ->where('therapist_id', $therapistId)
                ->with('client.originalIntake')
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($bills->isEmpty()) {
                return null;
            }

            $statement = new Invoice([
                'therapist_id' => $therapistId,
                'billed_by' => 'therapist',
                'is_monthly' => true,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'invoice_date' => $periodEnd->toDateString(),
                'due_date' => $periodEnd->copy()->addDays(30)->toDateString(),
                'tax_percentage' => 0,
                'services' => $this->linesFrom($bills),
                'status' => 'sent',
                'issued_by_id' => $therapistId,
                // The statement is addressed to the clinic, whose letterhead
                // details already live in config for the invoice PDF.
                'bill_to_name' => config('cats.invoice.legal_name'),
                'bill_to_address' => config('cats.invoice.street')."\n".config('cats.invoice.city_line'),
                'timeline' => [[
                    'id' => (string) Str::uuid(),
                    'title' => 'Monthly invoice generated and sent to admin',
                    'date' => now()->toDateString(),
                    'time' => now()->format('g:i:s A'),
                ]],
            ]);

            $statement->invoice_id = $this->referenceNumbers->invoice();
            $statement->calculateTotals();
            $statement->save();

            Invoice::query()->whereIn('id', $bills->pluck('id'))
                ->update(['monthly_invoice_id' => $statement->id]);

            $this->notifyAdmins($statement);

            return $statement;
        });
    }

    /**
     * Generating the statement is what sends it: the admins are mailed the
     * moment the month is closed, which is the first they see of the work.
     */
    private function notifyAdmins(Invoice $statement): void
    {
        $recipients = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->pluck('email');

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new InvoiceResendMail($statement));
        }
    }

    /**
     * The therapist bills dated inside the month that no statement has taken
     * yet. A bill is dated when it is created, so that date is the service
     * date the statement reports.
     *
     * @return Builder<Invoice>
     */
    private function billsForMonth(CarbonInterface $month): Builder
    {
        return Invoice::query()
            // Without the model's default ordering: MySQL rejects a DISTINCT
            // whose ORDER BY column is not in the select list, and reorder()
            // cannot help because the global scope is applied at execution.
            ->withoutGlobalScope('orderByInvoiceDate')
            ->unbilledClientBills()
            // Compared as dates, not strings: `invoice_date` is a date cast
            // stored with a midnight time, so a plain BETWEEN against
            // "2026-06-30" would drop the last day of the month.
            ->whereDate('invoice_date', '>=', $month->copy()->startOfMonth()->toDateString())
            ->whereDate('invoice_date', '<=', $month->copy()->endOfMonth()->toDateString());
    }

    /**
     * Every bill's lines, flattened and stamped with the bill's date and the
     * child it was for — the Date and Client columns of the statement.
     *
     * @param  Collection<int, Invoice>  $bills
     * @return array<int, array<string, mixed>>
     */
    private function linesFrom(Collection $bills): array
    {
        return $bills
            ->flatMap(fn (Invoice $bill): array => collect($bill->services ?? [])
                ->map(fn (array $line): array => [
                    ...$line,
                    'date' => $bill->invoice_date?->toDateString(),
                    'client' => $bill->client?->displayName() ?? 'Client',
                ])
                ->all())
            ->values()
            ->all();
    }
}
