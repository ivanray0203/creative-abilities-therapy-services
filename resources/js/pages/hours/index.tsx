import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

import HoursTable from '@/components/hours/hours-table';
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
import TherapistLayout from '@/layouts/therapist-layout';
import type { Paginated } from '@/types/intake';
import type {
    HoursFilters,
    HoursStats,
    TimesheetEntry,
} from '@/types/timesheet';

interface HoursIndexProps {
    entries: Paginated<TimesheetEntry>;
    stats: HoursStats;
    filters: HoursFilters;
}

const BASE_PATH = '/therapist/hours';

/**
 * The aide's hours ledger — every day they have logged, and whether a
 * generated timesheet has picked it up yet. The aide's counterpart to the
 * therapist's billing ledger, in hours rather than money.
 */
export default function HoursIndex({
    entries,
    stats,
    filters,
}: HoursIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                BASE_PATH,
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyFilter = (key: keyof HoursFilters, value: string) => {
        router.get(
            BASE_PATH,
            { ...filters, search, [key]: value },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            BASE_PATH,
            { ...filters, search, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Hours" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Hours
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            Log the hours you give each child, day by day
                        </p>
                    </div>
                    <Button className="rounded-[10px]" asChild>
                        <Link href={`${BASE_PATH}/create`}>
                            <Plus /> Log Hours
                        </Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Hours Not Sheeted
                        </p>
                        <p className="text-2xl font-bold">
                            {Number(stats.unsheeted_hours).toFixed(2)}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Days Not Sheeted
                        </p>
                        <p className="text-2xl font-bold">
                            {stats.unsheeted_days}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            This Month
                        </p>
                        <p className="text-2xl font-bold">
                            {Number(stats.this_month_hours).toFixed(2)}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            On a Timesheet
                        </p>
                        <p className="text-2xl font-bold">
                            {Number(stats.sheeted_hours).toFixed(2)}
                        </p>
                    </Card>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
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
                                <SelectItem value="all">All Days</SelectItem>
                                <SelectItem value="unsheeted">
                                    Not Sheeted
                                </SelectItem>
                                <SelectItem value="sheeted">
                                    On a Timesheet
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </Card>

                <Card className="p-6">
                    <HoursTable entries={entries.data} />

                    <PaginationFooter
                        className="mt-4"
                        currentPage={entries.current_page}
                        lastPage={entries.last_page}
                        perPage={entries.per_page}
                        total={entries.total}
                        countOnPage={entries.data.length}
                        onPageChange={goToPage}
                    />
                </Card>
            </div>
        </>
    );
}

HoursIndex.layout = (page: React.ReactNode) => (
    <TherapistLayout>{page}</TherapistLayout>
);
