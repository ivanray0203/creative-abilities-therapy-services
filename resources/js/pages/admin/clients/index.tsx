import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarClock,
    DollarSign,
    Eye,
    Shield,
    User,
    UserCheck,
    Users,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import {
    ClientStatusBadge,
    FundingBadge,
} from '@/components/admin/client/badges';
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
import type { Client, ClientStats } from '@/types/client';
import type { Paginated } from '@/types/intake';

interface ClientIndexProps {
    clients: Paginated<Client>;
    stats: ClientStats;
    filters: { search: string; status: string; funding: string };
}

/** Admin client list, ported from cats-frontend/src/pages/admin/ClientsPage.tsx. */
export default function AdminClientIndex({
    clients,
    stats,
    filters,
}: ClientIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/clients',
                { search, status: filters.status, funding: filters.funding },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatus = (status: string) => {
        router.get(
            '/admin/clients',
            { search, status, funding: filters.funding },
            { preserveState: true, replace: true },
        );
    };

    const applyFunding = (funding: string) => {
        router.get(
            '/admin/clients',
            { search, status: filters.status, funding },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/admin/clients',
            {
                search,
                status: filters.status,
                funding: filters.funding,
                page,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Client Management" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Client Management
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Manage active clients and their care teams
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:gap-4 md:grid-cols-4 md:gap-6">
                    <StatCard
                        label="Total Clients"
                        value={stats.total}
                        icon={
                            <Users className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                        }
                        tone="bg-primary/10"
                    />
                    <StatCard
                        label="Active"
                        value={stats.active}
                        icon={
                            <UserCheck className="h-5 w-5 text-green-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-green-100"
                    />
                    <StatCard
                        label="Upcoming"
                        value={stats.upcoming}
                        icon={
                            <CalendarClock className="h-5 w-5 text-blue-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-blue-100"
                    />
                    <StatCard
                        label="Paused"
                        value={stats.paused}
                        icon={
                            <User className="h-5 w-5 text-yellow-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-yellow-100"
                    />
                </div>

                <div className="rounded-sm border border-primary bg-gradient-to-br from-secondary-orange/5 to-transparent p-4 shadow-md sm:p-5">
                    <p className="flex items-center gap-2 text-sm sm:gap-3 sm:text-base">
                        <DollarSign className="h-4 w-4 text-primary sm:h-5 sm:w-5" />{' '}
                        Funding Type Overview
                    </p>

                    <div className="mt-4 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 md:gap-6">
                        <FundingCard
                            label="FSCD"
                            value={stats.fscd}
                            icon={
                                <DollarSign className="h-4 w-4 text-blue-600 sm:h-5 sm:w-5" />
                            }
                            tone="bg-blue-100"
                        />
                        <FundingCard
                            label="Private Insurance"
                            value={stats.insurance}
                            icon={
                                <Shield className="h-4 w-4 text-green-600 sm:h-5 sm:w-5" />
                            }
                            tone="bg-green-100"
                        />
                        <FundingCard
                            label="Private Pay"
                            value={stats.private}
                            icon={
                                <User className="h-4 w-4 text-gray-600 sm:h-5 sm:w-5" />
                            }
                            tone="bg-gray-100"
                        />
                    </div>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div className="w-full flex-1">
                            <label
                                htmlFor="client-search"
                                className="mb-1 block text-sm font-medium text-muted-foreground"
                            >
                                Search
                            </label>
                            <Input
                                id="client-search"
                                placeholder="Search by client or child name..."
                                className="w-full rounded-[10px]"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>

                        <div className="w-full sm:w-auto">
                            <label
                                htmlFor="client-status"
                                className="mb-1 block text-sm font-medium text-muted-foreground"
                            >
                                Status
                            </label>
                            <Select
                                value={filters.status}
                                onValueChange={applyStatus}
                            >
                                <SelectTrigger
                                    id="client-status"
                                    className="w-full rounded-[10px] sm:w-auto"
                                >
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="active">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="paused">
                                        Paused
                                    </SelectItem>
                                    <SelectItem value="completed">
                                        Completed
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactive
                                    </SelectItem>
                                    <SelectItem value="archive">
                                        Archived
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="w-full sm:w-auto">
                            <label
                                htmlFor="client-funding"
                                className="mb-1 block text-sm font-medium text-muted-foreground"
                            >
                                Funding Type
                            </label>
                            <Select
                                value={filters.funding}
                                onValueChange={applyFunding}
                            >
                                <SelectTrigger
                                    id="client-funding"
                                    className="w-full rounded-[10px] sm:w-auto"
                                >
                                    <SelectValue placeholder="Funding Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="fscd">FSCD</SelectItem>
                                    <SelectItem value="Insurance">
                                        Insurance
                                    </SelectItem>
                                    <SelectItem value="private">
                                        Private
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="mb-4">
                        <h3 className="text-lg font-semibold">Clients</h3>
                        <p className="text-sm text-muted-foreground">
                            Approved intakes managed as active clients
                        </p>
                    </div>

                    {clients.data.length === 0 ? (
                        <div className="py-8 text-center text-muted-foreground">
                            No clients found
                        </div>
                    ) : (
                        <div className="w-full overflow-x-auto">
                            <table className="w-full">
                                <thead className="border-b">
                                    <tr className="text-left">
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            ID
                                        </th>
                                        <th className="pb-3 text-sm font-medium text-muted-foreground md:text-base">
                                            Child Name
                                        </th>
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            Assigned Therapist
                                        </th>
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            Funding Type
                                        </th>
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            Status
                                        </th>
                                        <th className="pb-3 text-sm font-medium text-muted-foreground md:text-base">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {clients.data.map((client) => (
                                        <tr
                                            key={client.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="hidden py-4 md:table-cell">
                                                #{client.id}
                                            </td>
                                            <td className="py-4 text-xs md:text-base">
                                                <span>
                                                    {
                                                        client.original_intake
                                                            ?.child_first_name
                                                    }{' '}
                                                    {
                                                        client.original_intake
                                                            ?.child_last_name
                                                    }
                                                </span>
                                                <p className="block sm:hidden">
                                                    <ClientStatusBadge
                                                        status={client.status}
                                                    />
                                                </p>
                                            </td>
                                            <td className="hidden py-4 md:table-cell">
                                                {client.assigned_therapist
                                                    ? `${client.assigned_therapist.first_name} ${client.assigned_therapist.last_name}`
                                                    : '-'}
                                            </td>
                                            <td className="hidden py-4 md:table-cell">
                                                <FundingBadge
                                                    fundingSource={
                                                        client.original_intake
                                                            ?.funding_source ??
                                                        null
                                                    }
                                                />
                                            </td>
                                            <td className="hidden py-4 md:table-cell">
                                                <ClientStatusBadge
                                                    status={client.status}
                                                />
                                            </td>
                                            <td className="py-4">
                                                <Link
                                                    href={`/admin/clients/${client.id}`}
                                                    className="flex items-center gap-1 text-sm text-primary"
                                                >
                                                    <span className="block sm:hidden">
                                                        <Eye />
                                                    </span>
                                                    <span className="hidden sm:block">
                                                        View Details
                                                    </span>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="mt-4 flex justify-end gap-2">
                                <button
                                    type="button"
                                    className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                    onClick={() =>
                                        goToPage(clients.current_page - 1)
                                    }
                                    disabled={clients.current_page <= 1}
                                >
                                    Previous
                                </button>
                                <span className="flex items-center px-2 text-sm">
                                    Page {clients.current_page} of{' '}
                                    {clients.last_page}
                                </span>
                                <button
                                    type="button"
                                    className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                    onClick={() =>
                                        goToPage(clients.current_page + 1)
                                    }
                                    disabled={
                                        clients.current_page >=
                                        clients.last_page
                                    }
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    icon,
    tone,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    tone: string;
}) {
    return (
        <Card className="flex flex-col justify-between p-4 sm:p-5 md:p-6">
            <div className="flex items-center justify-between gap-2 sm:gap-3">
                <div>
                    <p className="text-xs text-muted-foreground sm:text-sm">
                        {label}
                    </p>
                    <p className="text-2xl font-bold sm:text-3xl">{value}</p>
                </div>
                <div className={`rounded-full p-2 sm:p-3 ${tone}`}>{icon}</div>
            </div>
        </Card>
    );
}

function FundingCard({
    label,
    value,
    icon,
    tone,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    tone: string;
}) {
    return (
        <Card className="flex items-center gap-2 p-3 sm:gap-3 sm:p-4 md:p-6">
            <div className={`flex-shrink-0 rounded-full p-2 sm:p-3 ${tone}`}>
                {icon}
            </div>
            <div className="truncate">
                <p className="truncate text-xs text-muted-foreground sm:text-sm">
                    {label}
                </p>
                <p className="truncate text-lg font-bold sm:text-xl">{value}</p>
            </div>
        </Card>
    );
}

AdminClientIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
