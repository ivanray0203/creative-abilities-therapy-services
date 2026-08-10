import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarPlus,
    CalendarRange,
    ChevronDown,
    ChevronUp,
    Mail,
    Phone,
    Search,
    Users,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import PaginationFooter from '@/components/pagination-footer';
import { ClientScheduleModal } from '@/components/therapist/client-schedule-modal';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TherapistLayout from '@/layouts/therapist-layout';
import { getInitials } from '@/lib/helpers';
import type { Client } from '@/types/client';
import type { Paginated } from '@/types/intake';

const SERVICES_BEFORE_FOLD = 4;

/**
 * A stable pastel for the initials disc — keyed off the client id so a client
 * keeps the same colour between visits, and adjacent rows never collide the
 * way hashing similar names does.
 */
const AVATAR_TONES = [
    'bg-violet-100 text-violet-700',
    'bg-orange-100 text-orange-700',
    'bg-sky-100 text-sky-700',
    'bg-emerald-100 text-emerald-700',
    'bg-rose-100 text-rose-700',
    'bg-amber-100 text-amber-700',
];

function avatarTone(clientId: number): string {
    return AVATAR_TONES[clientId % AVATAR_TONES.length];
}

interface TherapistClientsIndexProps {
    clients: Paginated<Client>;
    filters: {
        search: string;
        status: string;
    };
}

export default function TherapistClientsIndex({
    clients,
    filters,
}: TherapistClientsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [selectedClient, setSelectedClient] = useState<Client | null>(null);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/therapist/clients',
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatus = (status: string) => {
        router.get(
            '/therapist/clients',
            { ...filters, search, status },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/therapist/clients',
            { ...filters, search, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="My Clients" />

            <div className="space-y-6 p-6">
                <div>
                    <div className="flex items-center gap-2">
                        <Users className="h-6 w-6 text-primary" />
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            My Clients
                        </h1>
                    </div>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Manage your assigned clients
                    </p>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search clients by name..."
                            className="rounded-[10px] pl-10"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                </Card>

                <Tabs value={filters.status} onValueChange={applyStatus}>
                    <TabsList>
                        <TabsTrigger value="active">Active</TabsTrigger>
                        <TabsTrigger value="inactive">Inactive</TabsTrigger>
                    </TabsList>
                </Tabs>

                <Card className="rounded-[10px] p-4 sm:p-6">
                    {clients.data.length === 0 ? (
                        <div className="p-10 text-center text-muted-foreground">
                            <Users className="mx-auto mb-2 h-6 w-6" />
                            No clients found
                        </div>
                    ) : (
                        <div className="w-full overflow-x-auto">
                            <table className="w-full">
                                <thead className="border-b text-left text-sm text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">Client</th>
                                        <th className="hidden w-[320px] pb-3 lg:table-cell">
                                            Services
                                        </th>
                                        <th className="hidden pb-3 lg:table-cell">
                                            Contact
                                        </th>
                                        <th className="pb-3">Status</th>
                                        <th className="pb-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {clients.data.map((client) => (
                                        <ClientRow
                                            key={client.id}
                                            client={client}
                                            onViewSchedule={setSelectedClient}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <PaginationFooter
                        className="mt-4"
                        currentPage={clients.current_page}
                        lastPage={clients.last_page}
                        perPage={clients.per_page}
                        total={clients.total}
                        countOnPage={clients.data.length}
                        onPageChange={goToPage}
                    />
                </Card>
            </div>

            {selectedClient?.original_intake && (
                <ClientScheduleModal
                    open={selectedClient !== null}
                    onClose={() => setSelectedClient(null)}
                    intake={selectedClient.original_intake}
                />
            )}
        </>
    );
}

function ClientRow({
    client,
    onViewSchedule,
}: {
    client: Client;
    onViewSchedule: (client: Client) => void;
}) {
    const [showAllServices, setShowAllServices] = useState(false);
    const intake = client.original_intake;

    /*
     * `clients.original_intake_id` is nullOnDelete, so deleting an intake
     * orphans its client. Say so rather than dropping the row, which would
     * silently shrink the therapist's caseload with no explanation.
     */
    if (!intake) {
        return (
            <tr className="border-b last:border-0">
                <td colSpan={5} className="py-4">
                    <span className="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-500">
                        <AlertTriangle className="h-4 w-4 shrink-0" />
                        Client #{client.id} — intake record missing, contact an
                        administrator.
                    </span>
                </td>
            </tr>
        );
    }

    const services = (client.client_services ?? []).filter(
        (service) => service.service,
    );
    const fullName = `${intake.child_first_name} ${intake.child_last_name}`;
    // Four fills the two-row block in the design; the rest fold behind a
    // toggle so one busy caseload row cannot push the others off screen.
    const visibleServices = showAllServices
        ? services
        : services.slice(0, SERVICES_BEFORE_FOLD);

    return (
        <tr className="border-b align-top last:border-0">
            <td className="py-5">
                <div className="flex items-center gap-3">
                    <Avatar className="h-11 w-11 shrink-0">
                        <AvatarFallback
                            className={`text-sm font-semibold ${avatarTone(client.id)}`}
                        >
                            {getInitials(fullName)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="truncate font-semibold">{fullName}</p>
                        <p className="text-sm text-muted-foreground">
                            {intake.age}
                        </p>
                    </div>
                </div>
            </td>
            <td className="hidden w-[320px] py-5 lg:table-cell">
                {services.length === 0 ? (
                    <span className="text-sm text-muted-foreground">—</span>
                ) : (
                    <>
                        <div className="flex flex-wrap gap-2">
                            {visibleServices.map((service) => (
                                <Badge
                                    key={service.id}
                                    variant="outline"
                                    className="rounded-[6px] border-border bg-background px-2.5 py-1 font-normal text-foreground"
                                >
                                    {service.service?.name}
                                </Badge>
                            ))}
                        </div>

                        {services.length > SERVICES_BEFORE_FOLD && (
                            <button
                                type="button"
                                onClick={() =>
                                    setShowAllServices(!showAllServices)
                                }
                                className="mt-2 flex items-center gap-1 text-sm font-medium text-teal-600 hover:underline"
                            >
                                {showAllServices ? (
                                    <>
                                        Less <ChevronUp className="h-4 w-4" />
                                    </>
                                ) : (
                                    <>
                                        +{' '}
                                        {services.length - SERVICES_BEFORE_FOLD}{' '}
                                        More
                                        <ChevronDown className="h-4 w-4" />
                                    </>
                                )}
                            </button>
                        )}
                    </>
                )}
            </td>
            <td className="hidden py-5 lg:table-cell">
                <p className="flex items-center gap-2 text-sm">
                    <Phone className="h-4 w-4 shrink-0 text-teal-600" />
                    {intake.primary_parent_phone || '—'}
                </p>
                <p className="mt-2 flex items-center gap-2 text-sm">
                    <Mail className="h-4 w-4 shrink-0 text-teal-600" />
                    <span className="truncate">
                        {intake.primary_parent_email || '—'}
                    </span>
                </p>
            </td>
            <td className="py-5">
                <Badge className="gap-1.5 rounded-full border-none bg-green-100 px-3 py-1 font-medium text-green-700 capitalize">
                    <span className="h-1.5 w-1.5 rounded-full bg-green-600" />
                    {client.status}
                </Badge>
            </td>
            <td className="py-5">
                <div className="flex gap-2 whitespace-nowrap">
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-[8px]"
                        onClick={() => onViewSchedule(client)}
                    >
                        <CalendarRange className="h-4 w-4" />
                        <span className="hidden sm:inline">Schedule</span>
                    </Button>

                    {/*
                     * Disabled when every availed service of this therapist's is
                     * already booked — the session form would not offer the client
                     * at all, so a live button would lead to an empty picker.
                     */}
                    {client.has_bookable_service ? (
                        <Button size="sm" className="rounded-[8px]" asChild>
                            <Link
                                href={`/therapist/sessions/create?client_id=${client.id}`}
                            >
                                <CalendarPlus className="h-4 w-4" />
                                <span className="hidden sm:inline">
                                    Create Session
                                </span>
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            size="sm"
                            className="rounded-[8px]"
                            disabled
                            title="No services left to schedule for this client"
                        >
                            <CalendarPlus className="h-4 w-4" />
                            <span className="hidden sm:inline">
                                Create Session
                            </span>
                        </Button>
                    )}
                </div>
            </td>
        </tr>
    );
}

TherapistClientsIndex.layout = (page: React.ReactElement) => (
    <TherapistLayout>{page}</TherapistLayout>
);
