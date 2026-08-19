import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useState } from 'react';

import BillingItemsTable from '@/components/billing/billing-items-table';
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
import TherapistLayout from '@/layouts/therapist-layout';
import type {
    BillingFilters,
    BillingItem,
    BillingStats,
} from '@/types/billing';
import type { Paginated } from '@/types/intake';

interface BillingIndexProps {
    items: Paginated<BillingItem>;
    stats: BillingStats;
    filters: BillingFilters;
    role: 'admin' | 'therapist';
}

const BASE_PATHS: Record<BillingIndexProps['role'], string> = {
    admin: '/admin/billing',
    therapist: '/therapist/billing',
};

/**
 * The therapist's billing ledger — every service they have billed for, and
 * whether the month-end invoice has picked it up yet.
 */
export default function BillingIndex({
    items,
    stats,
    filters,
    role,
}: BillingIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const basePath = BASE_PATHS[role];

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

    const applyFilter = (key: keyof BillingFilters, value: string) => {
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
            <Head title="Billing" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Billing
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            {role === 'admin'
                                ? 'Bills raised by the clinic'
                                : 'Bill for the services you deliver'}
                        </p>
                    </div>
                    <Button className="rounded-[10px]" asChild>
                        <Link href={`${basePath}/create`}>
                            <Plus /> Create Bill
                        </Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Unbilled Amount
                        </p>
                        <p className="text-2xl font-bold">
                            ${Number(stats.unbilled_total).toFixed(2)}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Unbilled Items
                        </p>
                        <p className="text-2xl font-bold">
                            {stats.unbilled_count}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            This Month
                        </p>
                        <p className="text-2xl font-bold">
                            ${Number(stats.this_month_total).toFixed(2)}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Invoiced
                        </p>
                        <p className="text-2xl font-bold">
                            ${Number(stats.billed_total).toFixed(2)}
                        </p>
                    </Card>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Input
                                placeholder="Search by bill #, service or child name..."
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
                                <SelectItem value="all">All Items</SelectItem>
                                <SelectItem value="unbilled">
                                    Unbilled
                                </SelectItem>
                                <SelectItem value="billed">Invoiced</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </Card>

                <Card className="p-6">
                    <BillingItemsTable
                        items={items.data}
                        basePath={basePath}
                        showTherapist={role === 'admin'}
                    />

                    <PaginationFooter
                        className="mt-4"
                        currentPage={items.current_page}
                        lastPage={items.last_page}
                        perPage={items.per_page}
                        total={items.total}
                        countOnPage={items.data.length}
                        onPageChange={goToPage}
                    />
                </Card>
            </div>
        </>
    );
}

/**
 * Picks the layout via `usePage()` rather than the `page.props` argument
 * Inertia passes to `.layout()` — that argument comes back `undefined`
 * during client-side page swaps, which crashed navigation entirely when
 * read synchronously here.
 */
function BillingIndexLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: BillingIndexProps['role'] }>().props;

    return role === 'admin' ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

BillingIndex.layout = (page: React.ReactNode) => (
    <BillingIndexLayout>{page}</BillingIndexLayout>
);
