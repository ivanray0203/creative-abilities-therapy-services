import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Pencil, Plus, Receipt, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

import PaginationFooter from '@/components/pagination-footer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatMoney } from '@/lib/expenses';
import { formatDate } from '@/lib/helpers';
import type { Expense } from '@/types/expense';
import type { Paginated } from '@/types/intake';

interface ExpenseFilters {
    search: string;
    category: string;
    status: string;
    from: string;
    to: string;
}

interface ExpensesIndexProps {
    expenses: Paginated<Expense>;
    stats: {
        total: number;
        count: number;
        pending_total: number;
        this_month_total: number;
    };
    filters: ExpenseFilters;
    categories: string[];
}

export default function AdminExpensesIndex({
    expenses,
    stats,
    filters,
    categories,
}: ExpensesIndexProps) {
    const [search, setSearch] = useState(filters.search);

    // Debounced so typing does not fire a request per keystroke.
    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/expenses',
                { ...filters, search },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyFilters = (
        changes: Partial<ExpenseFilters & { page: number }>,
    ) =>
        router.get(
            '/admin/expenses',
            { ...filters, search, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const removeExpense = (expense: Expense) => {
        if (
            !window.confirm(
                `Remove ${expense.reference_number}? This cannot be undone.`,
            )
        ) {
            return;
        }

        router.delete(`/admin/expenses/${expense.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Expenses" />

            <div className="grid grid-cols-1 gap-5 p-6">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <p className="flex items-center gap-2 text-xl font-bold text-primary">
                            <Receipt className="h-5 w-5" /> Expenses
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Clinic spending, recorded as it happens.
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-[10px] border-primary text-primary"
                        >
                            <Link
                                id="expenses-report-link"
                                href="/admin/expenses/report"
                            >
                                <BarChart3 /> Report
                            </Link>
                        </Button>
                        <Button asChild className="rounded-[10px]">
                            <Link
                                id="expenses-add-link"
                                href="/admin/expenses/add"
                            >
                                <Plus /> Record Expense
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Totals reflect the current filters, except this month. */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        {
                            label: 'Filtered Total',
                            value: formatMoney(stats.total),
                            id: 'stat-total',
                        },
                        {
                            label: 'Entries',
                            value: String(stats.count),
                            id: 'stat-count',
                        },
                        {
                            label: 'Pending',
                            value: formatMoney(stats.pending_total),
                            id: 'stat-pending',
                        },
                        {
                            label: 'This Month',
                            value: formatMoney(stats.this_month_total),
                            id: 'stat-this-month',
                        },
                    ].map((stat) => (
                        <Card key={stat.id} className="rounded-[10px]">
                            <CardContent className="p-5">
                                <p className="text-sm text-muted-foreground">
                                    {stat.label}
                                </p>
                                <p
                                    id={stat.id}
                                    className="mt-1 text-2xl font-bold text-primary"
                                >
                                    {stat.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="rounded-[10px]">
                    <CardContent className="grid grid-cols-1 gap-4 p-5 md:grid-cols-5">
                        <div className="md:col-span-2">
                            <Label htmlFor="expense-search">Search</Label>
                            <Input
                                id="expense-search"
                                placeholder="Payee, reference, or description"
                                value={search}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="expense-filter-category">
                                Category
                            </Label>
                            <Select
                                value={filters.category}
                                onValueChange={(category) =>
                                    applyFilters({ category })
                                }
                            >
                                <SelectTrigger
                                    id="expense-filter-category"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All categories
                                    </SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem
                                            key={category}
                                            value={category}
                                        >
                                            {category}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="expense-filter-from">From</Label>
                            <Input
                                id="expense-filter-from"
                                type="date"
                                value={filters.from}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    applyFilters({ from: event.target.value })
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="expense-filter-to">To</Label>
                            <Input
                                id="expense-filter-to"
                                type="date"
                                value={filters.to}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    applyFilters({ to: event.target.value })
                                }
                            />
                        </div>

                        <div className="flex flex-wrap gap-2 md:col-span-5">
                            {['all', 'paid', 'pending'].map((status) => (
                                <Button
                                    key={status}
                                    variant={
                                        filters.status === status
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className="rounded-full capitalize"
                                    onClick={() => applyFilters({ status })}
                                >
                                    {status}
                                </Button>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-[10px]">
                    <CardContent className="p-0">
                        {expenses.data.length === 0 ? (
                            <p
                                id="expenses-empty"
                                className="p-10 text-center text-muted-foreground"
                            >
                                No expenses match these filters.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[900px] text-left text-sm">
                                    <thead className="border-b bg-secondary-orange/5">
                                        <tr>
                                            <th className="p-4">Reference</th>
                                            <th className="p-4">Date</th>
                                            <th className="p-4">Category</th>
                                            <th className="p-4">Payee</th>
                                            <th className="p-4">Method</th>
                                            <th className="p-4 text-right">
                                                Amount
                                            </th>
                                            <th className="p-4 text-right">
                                                GST
                                            </th>
                                            <th className="p-4 text-right">
                                                Total
                                            </th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4" />
                                        </tr>
                                    </thead>
                                    <tbody id="expenses-rows">
                                        {expenses.data.map((expense) => (
                                            <tr
                                                key={expense.id}
                                                className="border-b last:border-b-0"
                                            >
                                                <td className="p-4 font-medium text-primary">
                                                    {expense.reference_number}
                                                </td>
                                                <td className="p-4">
                                                    {formatDate(
                                                        expense.expense_date,
                                                    )}
                                                </td>
                                                <td className="p-4">
                                                    {expense.category}
                                                </td>
                                                <td className="p-4">
                                                    {expense.payee}
                                                </td>
                                                <td className="p-4">
                                                    {expense.payment_method}
                                                </td>
                                                <td className="p-4 text-right">
                                                    {formatMoney(
                                                        expense.amount,
                                                    )}
                                                </td>
                                                <td className="p-4 text-right">
                                                    {formatMoney(
                                                        expense.tax_amount,
                                                    )}
                                                </td>
                                                <td className="p-4 text-right font-semibold">
                                                    {formatMoney(expense.total)}
                                                </td>
                                                <td className="p-4">
                                                    <Badge
                                                        className={
                                                            expense.status ===
                                                            'paid'
                                                                ? 'bg-secondary-orange/10 text-primary'
                                                                : 'bg-muted text-muted-foreground'
                                                        }
                                                    >
                                                        {expense.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                            className="rounded-[8px]"
                                                        >
                                                            <Link
                                                                id={`expense-edit-${expense.id}`}
                                                                href={`/admin/expenses/edit/${expense.id}`}
                                                                aria-label={`Edit ${expense.reference_number}`}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            id={`expense-delete-${expense.id}`}
                                                            size="sm"
                                                            variant="outline"
                                                            className="rounded-[8px] text-destructive"
                                                            aria-label={`Remove ${expense.reference_number}`}
                                                            onClick={() =>
                                                                removeExpense(
                                                                    expense,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {expenses.data.length > 0 && (
                    <PaginationFooter
                        currentPage={expenses.current_page}
                        lastPage={expenses.last_page}
                        perPage={expenses.per_page}
                        total={expenses.total}
                        countOnPage={expenses.data.length}
                        onPageChange={(page) => applyFilters({ page })}
                    />
                )}
            </div>
        </>
    );
}

AdminExpensesIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
