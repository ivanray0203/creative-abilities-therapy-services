import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    CalendarDays,
    Download,
    LayoutGrid,
    List,
    Plus,
    Search,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useState } from 'react';

import SessionCardModal from '@/components/sessions/session-card-modal';
import SessionsDailyView from '@/components/sessions/sessions-daily-view';
import SessionsMonthlyView from '@/components/sessions/sessions-monthly-view';
import SessionsWeeklyView from '@/components/sessions/sessions-weekly-view';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import { exportSessionsCsv } from '@/lib/sessions-csv';
import type { Client, ServiceOffering } from '@/types/client';
import type { Paginated, TherapistOption } from '@/types/intake';
import type {
    QuickFilter,
    ScheduleSession,
    SessionFilters,
    SessionStats,
} from '@/types/session';

interface SessionsIndexProps {
    sessions: Paginated<ScheduleSession>;
    stats: SessionStats;
    filters: SessionFilters;
    isAdmin: boolean;
    therapists: TherapistOption[];
    services: ServiceOffering[];
    clients: Client[];
}

const QUICK_FILTERS: { value: QuickFilter; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'today', label: 'Today' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'past', label: 'Past' },
    { value: 'disputed', label: 'Disputed' },
];

/**
 * Shared sessions list/calendar, rendered for both `/admin/sessions` and
 * `/therapist/calendar` — reference: cats-frontend/src/pages/admin/SessionssPage.tsx.
 */
export default function SessionsIndex({
    sessions,
    stats,
    filters,
    isAdmin,
    therapists,
    services,
}: SessionsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [viewMode, setViewMode] = useState<'card' | 'table'>('card');
    const [selectedSession, setSelectedSession] =
        useState<ScheduleSession | null>(null);

    const basePath = isAdmin ? '/admin/sessions' : '/therapist/calendar';

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

    const applyFilter = (key: keyof SessionFilters, value: string) => {
        router.get(
            basePath,
            { ...filters, search, [key]: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Sessions" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Sessions
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            Manage scheduled therapy sessions
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => exportSessionsCsv(sessions.data)}
                        >
                            <Download /> Export CSV
                        </Button>
                        <Button className="rounded-[10px]" asChild>
                            <Link
                                href={
                                    isAdmin
                                        ? '/admin/sessions/create'
                                        : '/therapist/sessions/create'
                                }
                            >
                                <Plus /> New Session
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">Total</p>
                        <p className="text-2xl font-bold">{stats.total}</p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">Today</p>
                        <p className="text-2xl font-bold">{stats.today}</p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Upcoming
                        </p>
                        <p className="text-2xl font-bold">{stats.upcoming}</p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Disputed
                        </p>
                        <p className="text-2xl font-bold">{stats.disputed}</p>
                    </Card>
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
                                {quick.value === 'today' && (
                                    <CalendarClock className="h-4 w-4" />
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

                        {isAdmin && (
                            <Select
                                value={filters.therapist_id ?? 'all'}
                                onValueChange={(value) =>
                                    applyFilter(
                                        'therapist_id',
                                        value === 'all' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="rounded-[10px] sm:w-48">
                                    <SelectValue placeholder="Therapist" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All Therapists
                                    </SelectItem>
                                    {therapists.map((therapist) => (
                                        <SelectItem
                                            key={therapist.id}
                                            value={String(therapist.id)}
                                        >
                                            {therapist.first_name}{' '}
                                            {therapist.last_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        <Select
                            value={filters.service_id ?? 'all'}
                            onValueChange={(value) =>
                                applyFilter(
                                    'service_id',
                                    value === 'all' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="rounded-[10px] sm:w-48">
                                <SelectValue placeholder="Service" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Services
                                </SelectItem>
                                {services.map((service) => (
                                    <SelectItem
                                        key={service.id}
                                        value={String(service.id)}
                                    >
                                        {service.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

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
                                <SelectItem value="scheduled">
                                    Scheduled
                                </SelectItem>
                                <SelectItem value="completed">
                                    Completed
                                </SelectItem>
                                <SelectItem value="cancelled">
                                    Cancelled
                                </SelectItem>
                                <SelectItem value="disputed">
                                    Disputed
                                </SelectItem>
                                <SelectItem value="no_show">No Show</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </Card>

                <Tabs defaultValue="day">
                    <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <TabsList>
                            <TabsTrigger value="day">
                                <CalendarClock className="mr-1 h-4 w-4" /> Day
                            </TabsTrigger>
                            <TabsTrigger value="week">
                                <CalendarDays className="mr-1 h-4 w-4" /> Week
                            </TabsTrigger>
                            <TabsTrigger value="month">
                                <CalendarDays className="mr-1 h-4 w-4" /> Month
                            </TabsTrigger>
                        </TabsList>

                        <div className="flex items-center gap-2">
                            <Input
                                type="date"
                                className="w-auto rounded-[10px]"
                                value={selectedDate.toISOString().slice(0, 10)}
                                onChange={(event) =>
                                    setSelectedDate(
                                        new Date(event.target.value),
                                    )
                                }
                            />
                            <Button
                                size="sm"
                                variant={
                                    viewMode === 'card' ? 'default' : 'outline'
                                }
                                className="rounded-[10px]"
                                onClick={() => setViewMode('card')}
                            >
                                <LayoutGrid className="h-4 w-4" />
                            </Button>
                            <Button
                                size="sm"
                                variant={
                                    viewMode === 'table' ? 'default' : 'outline'
                                }
                                className="rounded-[10px]"
                                onClick={() => setViewMode('table')}
                            >
                                <List className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <TabsContent value="day" className="mt-4">
                        <SessionsDailyView
                            sessions={sessions.data}
                            selectedDate={selectedDate}
                            viewMode={viewMode}
                            onSelectSession={setSelectedSession}
                        />
                    </TabsContent>
                    <TabsContent value="week" className="mt-4">
                        <SessionsWeeklyView
                            sessions={sessions.data}
                            selectedDate={selectedDate}
                            onSelectSession={setSelectedSession}
                        />
                    </TabsContent>
                    <TabsContent value="month" className="mt-4">
                        <SessionsMonthlyView
                            sessions={sessions.data}
                            selectedDate={selectedDate}
                            onSelectSession={setSelectedSession}
                        />
                    </TabsContent>
                </Tabs>

                {sessions.data.length === 0 && (
                    <Card className="rounded-[10px] p-10 text-center text-muted-foreground">
                        <Search className="mx-auto mb-2 h-6 w-6" />
                        No sessions match your filters
                    </Card>
                )}
            </div>

            <SessionCardModal
                session={selectedSession}
                isAdmin={isAdmin}
                isOpen={selectedSession !== null}
                onClose={() => setSelectedSession(null)}
            />
        </>
    );
}

/**
 * Picks the layout via `usePage()` rather than the `page.props` argument
 * Inertia passes to `.layout()` — that argument comes back `undefined`
 * during client-side page swaps (e.g. navigating between admin sidebar
 * links), which crashed navigation entirely when read synchronously here.
 */
function SessionsLayout({ children }: PropsWithChildren) {
    const { isAdmin } = usePage<{ isAdmin: boolean }>().props;

    return isAdmin ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

SessionsIndex.layout = (page: React.ReactNode) => (
    <SessionsLayout>{page}</SessionsLayout>
);
