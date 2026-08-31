import { Head, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useState } from 'react';

import PaginationFooter from '@/components/pagination-footer';
import GenerateTimesheetModal from '@/components/timesheets/generate-timesheet-modal';
import TimesheetsTable from '@/components/timesheets/timesheets-table';
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
import { useAuthUser } from '@/hooks/use-auth-user';
import AdminLayout from '@/layouts/admin-layout';
import ClientLayout from '@/layouts/client-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client } from '@/types/client';
import type { Paginated } from '@/types/intake';
import type {
    Timesheet,
    TimesheetFilters,
    TimesheetStats,
} from '@/types/timesheet';

interface TimesheetsIndexProps {
    timesheets: Paginated<Timesheet>;
    stats: TimesheetStats;
    filters: TimesheetFilters;
    role: 'admin' | 'therapist' | 'client';
    /** Gather the rows under a heading per child. Off for a parent. */
    groupedByClient: boolean;
    /** Aides only — children with hours still waiting to go on a form. */
    sheetableClients: Client[];
}

const BASE_PATHS: Record<TimesheetsIndexProps['role'], string> = {
    admin: '/admin/timesheets',
    therapist: '/therapist/timesheets',
    client: '/client/timesheets',
};

const SUBTITLES: Record<TimesheetsIndexProps['role'], string> = {
    admin: 'Time sheets signed by the aide and the parent',
    therapist: 'Roll your logged hours into a form the parent signs',
    client: 'Confirm the hours your aide has recorded',
};

/** Shared timesheets list, rendered for aide/parent/admin. */
export default function TimesheetsIndex({
    timesheets,
    stats,
    filters,
    role,
    groupedByClient,
    sheetableClients = [],
}: TimesheetsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [isGenerateOpen, setIsGenerateOpen] = useState(false);
    const user = useAuthUser();
    const basePath = BASE_PATHS[role];
    const canGenerate = role === 'therapist';

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

    const applyFilter = (key: keyof TimesheetFilters, value: string) => {
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
            <Head title="Timesheets" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Timesheets
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            {SUBTITLES[role]}
                        </p>
                    </div>
                    {canGenerate && (
                        <Button
                            className="rounded-[10px]"
                            onClick={() => setIsGenerateOpen(true)}
                        >
                            <Plus /> Generate Timesheet
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Awaiting Parent
                        </p>
                        <p className="text-2xl font-bold">
                            {stats.awaiting_client}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">Signed</p>
                        <p className="text-2xl font-bold">{stats.signed}</p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Total Hours
                        </p>
                        <p className="text-2xl font-bold">
                            {Number(stats.total_hours).toFixed(2)}
                        </p>
                    </Card>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Input
                                placeholder="Search by timesheet # or child name..."
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
                            <SelectTrigger className="rounded-[10px] sm:w-52">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Timesheets
                                </SelectItem>
                                <SelectItem value="awaiting_client">
                                    Awaiting Parent
                                </SelectItem>
                                <SelectItem value="signed">Signed</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </Card>

                <Card className="p-6">
                    <TimesheetsTable
                        timesheets={timesheets.data}
                        basePath={basePath}
                        showAide={role === 'admin'}
                        groupedByClient={groupedByClient}
                    />

                    <PaginationFooter
                        className="mt-4"
                        currentPage={timesheets.current_page}
                        lastPage={timesheets.last_page}
                        perPage={timesheets.per_page}
                        total={timesheets.total}
                        countOnPage={timesheets.data.length}
                        onPageChange={goToPage}
                    />
                </Card>
            </div>

            {canGenerate && (
                <GenerateTimesheetModal
                    aideName={`${user.first_name} ${user.last_name}`}
                    clients={sheetableClients}
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
function TimesheetsLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: TimesheetsIndexProps['role'] }>().props;

    if (role === 'admin') {
        return <AdminLayout>{children}</AdminLayout>;
    }

    if (role === 'therapist') {
        return <TherapistLayout>{children}</TherapistLayout>;
    }

    return <ClientLayout>{children}</ClientLayout>;
}

TimesheetsIndex.layout = (page: React.ReactNode) => (
    <TimesheetsLayout>{page}</TimesheetsLayout>
);
