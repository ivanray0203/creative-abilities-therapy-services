<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Mirrors the reference's Celery beat `mark_invoices_overdue` task: any
 * `sent` invoice whose due date has passed becomes `overdue`.
 */
#[Signature('invoices:mark-overdue')]
#[Description('Mark sent invoices past their due date as overdue')]
class MarkInvoicesOverdueCommand extends Command
{
    public function handle(): int
    {
        $invoices = Invoice::query()
            ->where('status', 'sent')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'status' => 'overdue',
                'timeline' => [
                    ...($invoice->timeline ?? []),
                    [
                        'id' => (string) Str::uuid(),
                        'title' => 'Invoice marked overdue',
                        'date' => now()->toDateString(),
                        'time' => now()->format('g:i:s A'),
                    ],
                ],
            ]);
        }

        $this->info("Marked {$invoices->count()} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
