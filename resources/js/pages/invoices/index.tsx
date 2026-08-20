import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CheckCircle,
    Clock,
    Download,
    LayoutGrid,
    List,
    Plus,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useState } from 'react';

import { InvoiceStatusBadge } from '@/components/invoices/badges';
import GenerateInvoiceModal from '@/components/invoices/generate-invoice-modal';
import InvoicesTable from '@/components/invoices/invoices-table';
import PaginationFooter from '@/components/pagination-footer';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import ClientLayout from '@/layouts/client-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import { formatDate } from '@/lib/helpers';
import { exportInvoicesCsv } from '@/lib/invoices-csv';
import type { Client } from '@/types/client';
import type { Paginated } from '@/types/intake';
import type {
    Invoice,
    InvoiceFilters,
    InvoiceStats,
    QuickInvoiceFilter,
} from '@/types/invoice';

interface InvoicesIndexProps {
    invoices: Paginated<Invoice>;
    stats: InvoiceStats;
    filters: InvoiceFilters;
    role: 'admin' | 'therapist' | 'client';
    /** Admins only — clients with billing still waiting to be invoiced. */
    billableClients: Client[];
}

const QUICK_FILTERS: { value: QuickInvoiceFilter; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'paid', label: 'Paid' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'overdue', label: 'Overdue' },
];

const BASE_PATHS: Record<InvoicesIndexProps['role'], string> = {
    admin: '/admin/invoices',
    therapist: '/therapist/invoices',
    client: '/client/invoices',
};

/**
 * Shared invoices list, rendered for admin/therapist/client — reference:
 * cats-frontend/src/pages/admin/InvoicesPage.tsx and client/InvoicesPage.tsx.
 */
export default function InvoicesIndex({
    invoices,
    stats,
    filters,
    role,
    billableClients = [],
}: InvoicesIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [viewMode, setViewMode] = useState<'card' | 'table'>('table');
    const [isGenerateOpen, setIsGenerateOpen] = useState(false);
    const basePath = BASE_PATHS[role];
    const canCreate = role !== 'client';

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                basePath,
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyFilter = (key: keyof InvoiceFilters, value: string) => {
        router.get(
            basePath,
            { ...filters, search, [key]: value },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            basePath,
            { ...filters, search, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Invoices" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Invoices
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            {role === 'client'
                                ? 'View your invoices'
                                : 'Manage billing and payments'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => exportInvoicesCsv(invoices.data)}
                        >
                            <Download /> Export CSV
                        </Button>
                        {/*
                         * Both billers raise an invoice out of what has
                         * already been billed, so the button opens the modal
                         * rather than the line-by-line form.
                         */}
                        {canCreate && (
                            <Button
                                className="rounded-[10px]"
                                onClick={() => setIsGenerateOpen(true)}
                            >
                                <Plus /> Create Invoice
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Paid This Month
                        </p>
                        <p className="text-2xl font-bold">
                            ${Number(stats.paid_this_month).toFixed(2)}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">Pending</p>
                        <p className="text-2xl font-bold">{stats.pending}</p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">Overdue</p>
                        <p className="text-2xl font-bold">{stats.overdue}</p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Total Revenue
                        </p>
                        <p className="text-2xl font-bold">
                            ${Number(stats.total_revenue).toFixed(2)}
                        </p>
                    </Card>
                    {stats.owed_to_therapists !== null && (
                        <Card className="p-4">
                            <p className="text-xs text-muted-foreground">
                                Owed to Therapists
                            </p>
                            <p className="text-2xl font-bold">
                                ${Number(stats.owed_to_therapists).toFixed(2)}
                            </p>
                        </Card>
                    )}
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-wrap gap-2">
                        {QUICK_FILTERS.map((quick) => (
                            <Button
                                key={quick.value}
                                size="sm"
                                variant={
                                    filters.quick === quick.value
                                        ? 'default'
                                        : 'outline'
                                }
                                className="rounded-[10px]"
                                onClick={() =>
                                    applyFilter('quick', quick.value)
                                }
                            >
                                {quick.value === 'paid' && (
                                    <CheckCircle className="h-4 w-4" />
                                )}
                                {quick.value === 'overdue' && (
                                    <Clock className="h-4 w-4" />
                                )}
                                {quick.label}
                            </Button>
                        ))}
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Input
                                placeholder="Search by child name..."
                                className="rounded-[10px]"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>

                        <Select
                            value={filters.status}
                            onValueChange={(value) =>
                                applyFilter('status', value)
                            }
                        >
                            <SelectTrigger className="rounded-[10px] sm:w-48">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Statuses
                                </SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="sent">Sent</SelectItem>
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="overdue">Overdue</SelectItem>
                                <SelectItem value="unpaid">Unpaid</SelectItem>
                                <SelectItem value="refunded">
                                    Refunded
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        {role === 'admin' && (
                            <Select
                                value={filters.direction}
                                onValueChange={(value) =>
                                    applyFilter('direction', value)
                                }
                            >
                                <SelectTrigger className="rounded-[10px] sm:w-48">
                                    <SelectValue placeholder="Direction" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Both Directions
                                    </SelectItem>
                                    <SelectItem value="therapist">
                                        From Therapist
                                    </SelectItem>
                                    <SelectItem value="admin">
                                        To Client
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        )}

                        {role === 'client' && (
                            <div className="flex gap-2">
                                <Button
                                    size="sm"
                                    variant={
                                        viewMode === 'card'
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className="rounded-[10px]"
                                    onClick={() => setViewMode('card')}
                                >
                                    <LayoutGrid className="h-4 w-4" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant={
                                        viewMode === 'table'
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className="rounded-[10px]"
                                    onClick={() => setViewMode('table')}
                                >
                                    <List className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                    </div>
                </Card>

                <Card className="p-6">
                    {role === 'client' && viewMode === 'card' ? (
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                            {invoices.data.map((invoice) => (
                                <Link
                                    key={invoice.id}
                                    href={`${basePath}/${invoice.id}`}
                                >
                                    <Card className="rounded-[10px] hover:shadow-md">
                                        <div className="p-5">
                                            <div className="flex items-center justify-between">
                                                <p className="font-medium">
                                                    {invoice.invoice_id ??
                                                        `#${invoice.id}`}
                                                </p>
                                                <InvoiceStatusBadge
                                                    status={invoice.status}
                                                />
                                            </div>
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                Due{' '}
                                                {formatDate(invoice.due_date)}
                                            </p>
                                            <p className="mt-1 text-lg font-bold">
                                                $
                                                {Number(invoice.total).toFixed(
                                                    2,
                                                )}
                                            </p>
                                        </div>
                                    </Card>
                                </Link>
                            ))}
                            {invoices.data.length === 0 && (
                                <p className="col-span-full py-8 text-center text-muted-foreground">
                                    No invoices found
                                </p>
                            )}
                        </div>
                    ) : (
                        <InvoicesTable
                            invoices={invoices.data}
                            basePath={basePath}
                            showDirection={role === 'admin'}
                        />
                    )}

                    <PaginationFooter
                        className="mt-4"
                        currentPage={invoices.current_page}
                        lastPage={invoices.last_page}
                        perPage={invoices.per_page}
                        total={invoices.total}
                        countOnPage={invoices.data.length}
                        onPageChange={goToPage}
                    />
                </Card>
            </div>

            {canCreate && (
                <GenerateInvoiceModal
                    basePath={basePath}
                    // A therapist invoices the clinic for all their own work,
                    // so there is no client to pick.
                    clients={role === 'admin' ? billableClients : undefined}
                    isOpen={isGenerateOpen}
                    onClose={() => setIsGenerateOpen(false)}
                />
            )}
        </>
    );
}

/**
 * Picks the layout via `usePage()` rather than the `page.props` argument
 * Inertia passes to `.layout()` — that argument comes back `undefined`
 * during client-side page swaps, which crashed navigation entirely when
 * read synchronously here.
 */
function InvoicesLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: InvoicesIndexProps['role'] }>().props;

    if (role === 'admin') {
        return <AdminLayout>{children}</AdminLayout>;
    }

    if (role === 'therapist') {
        return <TherapistLayout>{children}</TherapistLayout>;
    }

    return <ClientLayout>{children}</ClientLayout>;
}

InvoicesIndex.layout = (page: React.ReactNode) => (
    <InvoicesLayout>{page}</InvoicesLayout>
);
