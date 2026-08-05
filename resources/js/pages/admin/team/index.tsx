import { Link, router } from '@inertiajs/react';
import { Download, Plus, UserCog, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

import { EmploymentStatusBadge } from '@/components/admin/team-member/badges';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { getInitials } from '@/lib/helpers';
import { exportTeamCsv } from '@/lib/team-csv';
import type { Paginated } from '@/types/intake';
import type { TeamMember, TeamMemberStats } from '@/types/team-member';

interface TeamIndexProps {
    teamMembers: Paginated<TeamMember>;
    stats: TeamMemberStats;
    positions: string[];
    filters: { search: string; position: string; status: string };
}

/** Admin team list, ported from cats-frontend/src/pages/admin/TeamPage.tsx. */
export default function AdminTeamIndex({
    teamMembers,
    stats,
    positions,
    filters,
}: TeamIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/team',
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyFilter = (key: 'position' | 'status', value: string) => {
        router.get(
            '/admin/team',
            { ...filters, search, [key]: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <div className="space-y-6 p-6">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Team Management
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Manage therapists and staff
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={() => exportTeamCsv(teamMembers.data)}
                    >
                        <Download /> Export CSV
                    </Button>
                    <Button className="rounded-[10px]" asChild>
                        <Link href="/admin/team/add">
                            <Plus /> Add Team Member
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
                    <p className="text-xs text-muted-foreground">Active</p>
                    <p className="text-2xl font-bold">{stats.active}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Total Caseload
                    </p>
                    <p className="text-2xl font-bold">{stats.caseload}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Avg Caseload
                    </p>
                    <p className="text-2xl font-bold">{stats.avg_caseload}</p>
                </Card>
            </div>

            <Card className="rounded-[10px] p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <label className="mb-1 block text-sm font-medium text-muted-foreground">
                            Search
                        </label>
                        <Input
                            placeholder="Search by name or email..."
                            className="rounded-[10px]"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>

                    <Select
                        value={filters.position}
                        onValueChange={(value) =>
                            applyFilter('position', value)
                        }
                    >
                        <SelectTrigger className="rounded-[10px] sm:w-48">
                            <SelectValue placeholder="Position" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Positions</SelectItem>
                            {positions.map((position) => (
                                <SelectItem key={position} value={position}>
                                    {position}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.status}
                        onValueChange={(value) => applyFilter('status', value)}
                    >
                        <SelectTrigger className="rounded-[10px] sm:w-48">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="on_leave">On Leave</SelectItem>
                            <SelectItem value="terminated">
                                Terminated
                            </SelectItem>
                            <SelectItem value="archived">Archived</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </Card>

            {teamMembers.data.length > 0 ? (
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {teamMembers.data.map((teamMember) => {
                        const caseload = teamMember.caseload ?? 0;
                        const percent = teamMember.maximum_caseload
                            ? Math.min(
                                  100,
                                  (caseload / teamMember.maximum_caseload) *
                                      100,
                              )
                            : 0;

                        return (
                            <Card key={teamMember.id} className="p-6">
                                <div className="mb-4 flex items-center justify-between gap-4">
                                    <div className="flex items-center gap-3">
                                        <Avatar className="h-12 w-12 bg-primary">
                                            <AvatarFallback className="bg-primary text-primary-foreground">
                                                {getInitials(
                                                    `${teamMember.user?.first_name ?? ''} ${teamMember.user?.last_name ?? ''}`,
                                                )}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p className="font-semibold">
                                                {teamMember.user?.first_name}{' '}
                                                {teamMember.user?.last_name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {teamMember.position}
                                            </p>
                                        </div>
                                    </div>
                                    <EmploymentStatusBadge
                                        status={teamMember.employment_status}
                                    />
                                </div>

                                <div className="mb-4 space-y-1 text-sm text-muted-foreground">
                                    <p>{teamMember.user?.email}</p>
                                    <p>{teamMember.phone}</p>
                                </div>

                                <div className="mb-4">
                                    <div className="flex justify-between text-xs text-muted-foreground">
                                        <span>Caseload</span>
                                        <span>
                                            {caseload} /{' '}
                                            {teamMember.maximum_caseload}
                                        </span>
                                    </div>
                                    <div className="mt-1 h-2 w-full rounded-full bg-gray-200">
                                        <div
                                            className="h-2 rounded-full bg-primary"
                                            style={{ width: `${percent}%` }}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="rounded-[5px]"
                                        asChild
                                    >
                                        <Link
                                            href={`/admin/team/${teamMember.id}`}
                                        >
                                            View Profile
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="rounded-[5px]"
                                        asChild
                                    >
                                        <Link
                                            href={`/admin/team/edit/${teamMember.id}`}
                                        >
                                            <UserCog className="h-4 w-4" />{' '}
                                            Manage
                                        </Link>
                                    </Button>
                                </div>
                            </Card>
                        );
                    })}
                </div>
            ) : (
                <Card className="w-full p-12">
                    <div className="text-center text-muted-foreground">
                        <Users className="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p className="mb-2 text-lg font-medium">
                            No Team Members Found
                        </p>
                    </div>
                </Card>
            )}
        </div>
    );
}

AdminTeamIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
