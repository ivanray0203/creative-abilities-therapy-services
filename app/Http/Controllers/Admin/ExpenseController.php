<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clinic spending. The list is the working screen — filtered, paginated, and
 * totalled — while ExpenseReportController handles period reporting.
 */
class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $baseQuery = $this->filteredQuery($filters);

        $expenses = (clone $baseQuery)
            ->with('recorder:id,first_name,last_name')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/expenses/index', [
            'expenses' => $expenses,
            'stats' => [
                'total' => (float) (clone $baseQuery)->sum('amount') + (float) (clone $baseQuery)->sum('tax_amount'),
                'count' => (clone $baseQuery)->count(),
                'pending_total' => (float) (clone $baseQuery)->where('status', 'pending')->sum('amount'),
                'this_month_total' => (float) Expense::query()
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->sum('amount'),
            ],
            'filters' => $filters,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/expenses/create', $this->formOptions());
    }

    public function store(StoreExpenseRequest $request, ReferenceNumberGenerator $referenceNumbers): RedirectResponse
    {
        $expense = Expense::query()->create([
            ...$request->validated(),
            'tax_amount' => $request->validated('tax_amount') ?? 0,
            'reference_number' => $referenceNumbers->expense(),
            'recorded_by' => $request->user()->id,
        ]);

        AuditLogger::log(
            'Recorded expense',
            'Expenses',
            "Recorded {$expense->reference_number} ({$expense->category}) for {$expense->payee}: {$expense->total}",
        );

        return to_route('admin.expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('admin/expenses/edit', [
            'expense' => $expense,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update([
            ...$request->validated(),
            'tax_amount' => $request->validated('tax_amount') ?? 0,
        ]);

        AuditLogger::log(
            'Updated expense',
            'Expenses',
            "Updated {$expense->reference_number} ({$expense->category}) for {$expense->payee}",
        );

        return to_route('admin.expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $reference = $expense->reference_number;
        $expense->delete();

        AuditLogger::log('Deleted expense', 'Expenses', "Deleted expense {$reference}", 'warning');

        return to_route('admin.expenses.index')->with('success', 'Expense removed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Expense::CATEGORIES,
            'paymentMethods' => Expense::PAYMENT_METHODS,
        ];
    }

    /**
     * @return array{search: string, category: string, status: string, from: string, to: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'category' => (string) $request->query('category', 'all'),
            'status' => (string) $request->query('status', 'all'),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];
    }

    /**
     * @param  array{search: string, category: string, status: string, from: string, to: string}  $filters
     * @return Builder<Expense>
     */
    private function filteredQuery(array $filters): Builder
    {
        return Expense::query()
            ->between($filters['from'] ?: null, $filters['to'] ?: null)
            ->when($filters['category'] !== 'all', fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $inner) use ($filters): void {
                    $inner->where('payee', 'like', "%{$filters['search']}%")
                        ->orWhere('reference_number', 'like', "%{$filters['search']}%")
                        ->orWhere('description', 'like', "%{$filters['search']}%");
                });
            });
    }
}
