import { Link, router } from '@inertiajs/react';
import { Briefcase, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

import DeleteCareerModal from '@/components/admin/delete-career-modal';
import { Badge } from '@/components/ui/badge';
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
import type { Career } from '@/types/career';
import type { Paginated } from '@/types/intake';

interface CareersIndexProps {
    careers: Paginated<Career>;
    stats: { total: number; active: number };
    filters: { search: string; status: string };
}

/** Admin position (job posting) list. */
export default function AdminCareersIndex({
    careers,
    stats,
    filters,
}: CareersIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [pendingDelete, setPendingDelete] = useState<Career | null>(null);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/careers',
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatusFilter = (status: string) => {
        router.get(
            '/admin/careers',
            { ...filters, search, status },
            { preserveState: true, replace: true },
        );
    };

    return (
        <div className="space-y-6 p-6">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Positions
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Manage the job postings shown on the public Careers page
                    </p>
                </div>
                <Button className="rounded-[10px]" asChild>
                    <Link href="/admin/careers/add">
                        <Plus /> Add Position
                    </Link>
                </Button>
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
            </div>

            <Card className="rounded-[10px] p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <label className="mb-1 block text-sm font-medium text-muted-foreground">
                            Search
                        </label>
                        <Input
                            placeholder="Search by position title..."
                            className="rounded-[10px]"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>

                    <Select
                        value={filters.status}
                        onValueChange={applyStatusFilter}
                    >
                        <SelectTrigger className="rounded-[10px] sm:w-48">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </Card>

            {careers.data.length > 0 ? (
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {careers.data.map((career) => (
                        <Card key={career.id} className="p-6">
                            <div className="mb-3 flex items-start justify-between gap-4">
                                <div>
                                    <p className="font-semibold">
                                        {career.position}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {career.location}
                                    </p>
                                </div>
                                <Badge
                                    variant={
                                        career.is_active
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {career.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>

                            <div className="mb-4 space-y-1 text-sm text-muted-foreground">
                                <p>
                                    {career.schedule} &middot; {career.contract}
                                </p>
                                <p>{career.rate}</p>
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-[5px]"
                                    asChild
                                >
                                    <Link
                                        href={`/admin/careers/edit/${career.id}`}
                                    >
                                        <Pencil className="h-4 w-4" /> Edit
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-[5px] text-destructive hover:text-destructive"
                                    onClick={() => setPendingDelete(career)}
                                >
                                    <Trash2 className="h-4 w-4" /> Delete
                                </Button>
                            </div>
                        </Card>
                    ))}
                </div>
            ) : (
                <Card className="w-full p-12">
                    <div className="text-center text-muted-foreground">
                        <Briefcase className="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p className="mb-2 text-lg font-medium">
                            No Positions Found
                        </p>
                    </div>
                </Card>
            )}

            {pendingDelete && (
                <DeleteCareerModal
                    careerId={pendingDelete.id}
                    position={pendingDelete.position}
                    isOpen={pendingDelete !== null}
                    onClose={() => setPendingDelete(null)}
                />
            )}
        </div>
    );
}

AdminCareersIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
