import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useState } from 'react';

import { SessionStatusBadge } from '@/components/sessions/badges';
import SessionCardModal from '@/components/sessions/session-card-modal';
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
import { formatScheduledDate, formatScheduledTime } from '@/lib/helpers';
import type { Client, ClientService } from '@/types/client';
import type { Paginated } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

interface ServiceSessionsProps {
    client: Client;
    clientService: ClientService;
    sessions: Paginated<ScheduleSession>;
    filters: { search: string; status: string };
}

/**
 * Session history scoped to one ClientService, reference:
 * cats-frontend/src/pages/ServiceSessions.tsx, linked from the client
 * Documents tab's Sessions card.
 */
export default function ServiceSessions({
    client,
    clientService,
    sessions,
    filters,
}: ServiceSessionsProps) {
    const [search, setSearch] = useState(filters.search);
    const [selectedSession, setSelectedSession] =
        useState<ScheduleSession | null>(null);

    const basePath = `/admin/clients/${client.id}/services/${clientService.id}/sessions`;

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                basePath,
                { search, status: filters.status },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatus = (status: string) => {
        router.get(
            basePath,
            { search, status },
            { preserveState: true, replace: true },
        );
    };

    const childName = client.original_intake
        ? `${client.original_intake.child_first_name} ${client.original_intake.child_last_name}`
        : `Client #${client.id}`;

    return (
        <>
            <Head
                title={`${clientService.service?.name ?? 'Service'} Sessions`}
            />

            <div className="space-y-6 p-6">
                <div>
                    <Link
                        href={`/admin/clients/${client.id}`}
                        className="flex items-center gap-1 text-sm text-primary"
                    >
                        <ArrowLeft className="h-4 w-4" /> Back to Client
                    </Link>
                    <h1 className="mt-2 text-2xl font-bold sm:text-3xl">
                        {clientService.service?.name ?? 'Service'} Sessions
                    </h1>
                    <p className="text-sm text-muted-foreground">{childName}</p>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Input
                            placeholder="Search by location..."
                            className="flex-1 rounded-[10px]"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                        <Select
                            value={filters.status}
                            onValueChange={applyStatus}
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

                <Card className="p-6">
                    {sessions.data.length === 0 ? (
                        <div className="py-8 text-center text-muted-foreground">
                            No sessions found for this service
                        </div>
                    ) : (
                        <div className="w-full overflow-x-auto">
                            <table className="w-full">
                                <thead className="border-b text-left text-sm text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">Date</th>
                                        <th className="pb-3">Time</th>
                                        <th className="pb-3">Therapist</th>
                                        <th className="pb-3">Location</th>
                                        <th className="pb-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sessions.data.map((session) => (
                                        <tr
                                            key={session.id}
                                            className="cursor-pointer border-b last:border-0 hover:bg-charcoal-gray/5"
                                            onClick={() =>
                                                setSelectedSession(session)
                                            }
                                        >
                                            <td className="py-3">
                                                {formatScheduledDate(
                                                    session.scheduled_start,
                                                )}
                                            </td>
                                            <td className="py-3">
                                                {formatScheduledTime(
                                                    session.scheduled_start,
                                                )}
                                            </td>
                                            <td className="py-3">
                                                {session.therapist
                                                    ? `${session.therapist.first_name} ${session.therapist.last_name}`
                                                    : '-'}
                                            </td>
                                            <td className="py-3">
                                                {session.location || '-'}
                                            </td>
                                            <td className="py-3">
                                                <SessionStatusBadge
                                                    status={session.status}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>

            <SessionCardModal
                session={selectedSession}
                isAdmin
                isOpen={selectedSession !== null}
                onClose={() => setSelectedSession(null)}
            />
        </>
    );
}

ServiceSessions.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
