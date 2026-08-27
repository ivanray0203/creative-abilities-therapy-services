import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, BarChart3, Download } from 'lucide-react';

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
import { formatMoney, formatMonth } from '@/lib/expenses';
import type {
    ExpenseBreakdownRow,
    ExpensePeriod,
    ExpenseSummary,
} from '@/types/expense';

interface ExpenseReportProps {
    period: ExpensePeriod;
    summary: ExpenseSummary;
    byCategory: ExpenseBreakdownRow[];
    byMonth: ExpenseBreakdownRow[];
    byPaymentMethod: ExpenseBreakdownRow[];
    topPayees: ExpenseBreakdownRow[];
    categories: string[];
}

/**
 * A breakdown table with a proportion bar, so the largest lines are obvious
 * without a charting dependency.
 */
function Breakdown({
    id,
    title,
    rows,
    formatLabel = (label: string) => label,
}: {
    id: string;
    title: string;
    rows: ExpenseBreakdownRow[];
    formatLabel?: (label: string) => string;
}) {
    const largest = rows.reduce((max, row) => Math.max(max, row.total), 0);

    return (
        <Card className="rounded-[10px]">
            <CardContent className="p-5">
                <p className="font-bold text-primary">{title}</p>

                {rows.length === 0 ? (
                    <p className="mt-4 text-sm text-muted-foreground">
                        Nothing in this period.
                    </p>
                ) : (
                    <div id={id} className="mt-4 space-y-3">
                        {rows.map((row) => (
                            <div key={row.label}>
                                <div className="flex items-baseline justify-between gap-4 text-sm">
                                    <span>{formatLabel(row.label)}</span>
                                    <span className="font-semibold">
                                        {formatMoney(row.total)}
                                        <span className="ml-2 font-normal text-muted-foreground">
                                            ({row.count})
                                        </span>
                                    </span>
                                </div>
                                <div className="mt-1 h-2 w-full rounded-full bg-secondary-orange/10">
                                    <div
                                        className="h-2 rounded-full bg-primary"
                                        style={{
                                            width: `${largest > 0 ? (row.total / largest) * 100 : 0}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function AdminExpenseReport({
    period,
    summary,
    byCategory,
    byMonth,
    byPaymentMethod,
    topPayees,
    categories,
}: ExpenseReportProps) {
    const applyPeriod = (changes: Partial<ExpensePeriod>) =>
        router.get(
            '/admin/expenses/report',
            { ...period, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const exportQuery = new URLSearchParams(
        period as unknown as Record<string, string>,
    ).toString();

    return (
        <>
            <Head title="Expense Report" />

            <div className="grid grid-cols-1 gap-5 p-6">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <p className="flex items-center gap-2 text-xl font-bold text-primary">
                            <BarChart3 className="h-5 w-5" /> Expense Report
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {period.from} to {period.to}
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-[10px] border-primary text-primary"
                        >
                            <Link id="report-back-link" href="/admin/expenses">
                                <ArrowLeft /> Expenses
                            </Link>
                        </Button>
                        {/*
                         * A plain anchor, not an Inertia Link: this is a file
                         * download, and Inertia would try to parse the CSV as
                         * a page response.
                         */}
                        <Button asChild className="rounded-[10px]">
                            <a
                                id="report-export-link"
                                href={`/admin/expenses/report/export?${exportQuery}`}
                            >
                                <Download /> Export CSV
                            </a>
                        </Button>
                    </div>
                </div>

                <Card className="rounded-[10px]">
                    <CardContent className="grid grid-cols-1 gap-4 p-5 md:grid-cols-4">
                        <div>
                            <Label htmlFor="report-from">From</Label>
                            <Input
                                id="report-from"
                                type="date"
                                value={period.from}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    applyPeriod({ from: event.target.value })
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="report-to">To</Label>
                            <Input
                                id="report-to"
                                type="date"
                                value={period.to}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    applyPeriod({ to: event.target.value })
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="report-category">Category</Label>
                            <Select
                                value={period.category}
                                onValueChange={(category) =>
                                    applyPeriod({ category })
                                }
                            >
                                <SelectTrigger
                                    id="report-category"
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
                            <Label htmlFor="report-status">Status</Label>
                            <Select
                                value={period.status}
                                onValueChange={(status) =>
                                    applyPeriod({ status })
                                }
                            >
                                <SelectTrigger
                                    id="report-status"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    <SelectItem value="paid">Paid</SelectItem>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {[
                        {
                            id: 'summary-gross',
                            label: 'Total Spend',
                            value: formatMoney(summary.gross),
                        },
                        {
                            id: 'summary-net',
                            label: 'Before GST',
                            value: formatMoney(summary.net),
                        },
                        {
                            id: 'summary-tax',
                            label: 'GST',
                            value: formatMoney(summary.tax),
                        },
                        {
                            id: 'summary-count',
                            label: 'Entries',
                            value: String(summary.count),
                        },
                        {
                            id: 'summary-average',
                            label: 'Average Entry',
                            value: formatMoney(summary.average),
                        },
                        {
                            id: 'summary-pending',
                            label: 'Pending',
                            value: formatMoney(summary.pending),
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

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <Breakdown
                        id="report-by-category"
                        title="By Category"
                        rows={byCategory}
                    />
                    <Breakdown
                        id="report-by-month"
                        title="By Month"
                        rows={byMonth}
                        formatLabel={formatMonth}
                    />
                    <Breakdown
                        id="report-by-method"
                        title="By Payment Method"
                        rows={byPaymentMethod}
                    />
                    <Breakdown
                        id="report-top-payees"
                        title="Top Payees"
                        rows={topPayees}
                    />
                </div>
            </div>
        </>
    );
}

AdminExpenseReport.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
