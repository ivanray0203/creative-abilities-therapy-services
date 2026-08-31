<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Spending reported over a period: totals by category, by month, and by
 * payment method, plus the top payees. Defaults to the current calendar year
 * so the screen is useful before anyone touches the filters.
 */
class ExpenseReportController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $this->period($request);
        $query = $this->scopedQuery($period);

        return Inertia::render('admin/expenses/report', [
            'period' => $period,
            'summary' => $this->summary($query),
            'byCategory' => $this->byCategory($query),
            'byMonth' => $this->byMonth($query),
            'byPaymentMethod' => $this->byPaymentMethod($query),
            'topPayees' => $this->topPayees($query),
            'categories' => Expense::CATEGORIES,
        ]);
    }

    /**
     * The filtered rows as CSV, for handing to a bookkeeper.
     */
    public function export(Request $request): StreamedResponse
    {
        $period = $this->period($request);

        $expenses = $this->scopedQuery($period)
            ->with('recorder:id,first_name,last_name')
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        AuditLogger::log(
            'Exported expense report',
            'Expenses',
            "Exported {$expenses->count()} expenses for {$period['from']} to {$period['to']}",
        );

        $filename = "expenses-{$period['from']}-to-{$period['to']}.csv";

        return response()->streamDownload(function () use ($expenses): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Reference', 'Date', 'Category', 'Payee', 'Description',
                'Amount', 'GST', 'Total', 'Payment Method', 'Status', 'Recorded By',
            ]);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->reference_number,
                    $expense->expense_date->toDateString(),
                    $expense->category,
                    $expense->payee,
                    $expense->description,
                    $expense->amount,
                    $expense->tax_amount,
                    $expense->total,
                    $expense->payment_method,
                    $expense->status,
                    $expense->recorder?->first_name.' '.$expense->recorder?->last_name,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{from: string, to: string, category: string, status: string}
     */
    private function period(Request $request): array
    {
        return [
            'from' => (string) $request->query('from', now()->startOfYear()->toDateString()),
            'to' => (string) $request->query('to', now()->toDateString()),
            'category' => (string) $request->query('category', 'all'),
            'status' => (string) $request->query('status', 'all'),
        ];
    }

    /**
     * @param  array{from: string, to: string, category: string, status: string}  $period
     * @return Builder<Expense>
     */
    private function scopedQuery(array $period): Builder
    {
        return Expense::query()
            ->between($period['from'], $period['to'])
            ->when($period['category'] !== 'all', fn (Builder $query) => $query->where('category', $period['category']))
            ->when($period['status'] !== 'all', fn (Builder $query) => $query->where('status', $period['status']));
    }

    /**
     * @param  Builder<Expense>  $query
     * @return array<string, float|int>
     */
    private function summary(Builder $query): array
    {
        $net = (float) (clone $query)->sum('amount');
        $tax = (float) (clone $query)->sum('tax_amount');
        $count = (clone $query)->count();

        return [
            'net' => round($net, 2),
            'tax' => round($tax, 2),
            'gross' => round($net + $tax, 2),
            'count' => $count,
            'average' => $count > 0 ? round(($net + $tax) / $count, 2) : 0.0,
            'pending' => round((float) (clone $query)->where('status', 'pending')->sum('amount'), 2),
        ];
    }

    /**
     * The aggregate is aliased `total_amount`, not `total`: `total` is an
     * appended accessor on the model, and it would shadow the SUM with a
     * figure computed from columns this query never selected. `toBase()` keeps
     * these grouped rows as plain objects rather than half-filled models.
     *
     * @param  Builder<Expense>  $query
     * @return array<int, array{label: string, total: float, count: int}>
     */
    private function byCategory(Builder $query): array
    {
        return (clone $query)
            ->select('category')
            ->selectRaw('SUM(amount + tax_amount) as total_amount, COUNT(*) as entries')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->category,
                'total' => round((float) $row->total_amount, 2),
                'count' => (int) $row->entries,
            ])
            ->all();
    }

    /**
     * Month buckets, keyed `YYYY-MM` so the client can sort and label them
     * without parsing a localized string.
     *
     * @param  Builder<Expense>  $query
     * @return array<int, array{label: string, total: float, count: int}>
     */
    private function byMonth(Builder $query): array
    {
        $driver = DB::connection()->getDriverName();

        $month = $driver === 'sqlite'
            ? "strftime('%Y-%m', expense_date)"
            : "DATE_FORMAT(expense_date, '%Y-%m')";

        return (clone $query)
            ->selectRaw("{$month} as month, SUM(amount + tax_amount) as total_amount, COUNT(*) as entries")
            ->groupBy('month')
            ->orderBy('month')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->month,
                'total' => round((float) $row->total_amount, 2),
                'count' => (int) $row->entries,
            ])
            ->all();
    }

    /**
     * @param  Builder<Expense>  $query
     * @return array<int, array{label: string, total: float, count: int}>
     */
    private function byPaymentMethod(Builder $query): array
    {
        return (clone $query)
            ->select('payment_method')
            ->selectRaw('SUM(amount + tax_amount) as total_amount, COUNT(*) as entries')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->payment_method,
                'total' => round((float) $row->total_amount, 2),
                'count' => (int) $row->entries,
            ])
            ->all();
    }

    /**
     * @param  Builder<Expense>  $query
     * @return array<int, array{label: string, total: float, count: int}>
     */
    private function topPayees(Builder $query): array
    {
        return (clone $query)
            ->select('payee')
            ->selectRaw('SUM(amount + tax_amount) as total_amount, COUNT(*) as entries')
            ->groupBy('payee')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->payee,
                'total' => round((float) $row->total_amount, 2),
                'count' => (int) $row->entries,
            ])
            ->all();
    }
}
