import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Sparkles, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

import DeleteProgramModal from '@/components/admin/delete-program-modal';
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
import { formatProgramDates, formatProgramPrice } from '@/lib/programs';
import type { Paginated } from '@/types/intake';

interface AdminProgram {
    id: number;
    slug: string;
    name: string;
    category: string | null;
    summary: string;
    age_range: string | null;
    location: string | null;
    price: string | null;
    starts_on: string | null;
    ends_on: string | null;
    capacity: number | null;
    is_active: boolean;
    registrations_count: number;
    taken_places: number;
}

interface ProgramsIndexProps {
    programs: Paginated<AdminProgram>;
    stats: {
        total: number;
        active: number;
        registrations: number;
        pending: number;
    };
    filters: { search: string; status: string };
}

export default function AdminProgramsIndex({
    programs,
    stats,
    filters,
}: ProgramsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [pendingDelete, setPendingDelete] = useState<AdminProgram | null>(
        null,
    );

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/programs',
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatusFilter = (status: string) => {
        router.get(
            '/admin/programs',
            { ...filters, search, status },
            { preserveState: true, replace: true },
        );
    };

    return (
        <div className="space-y-6 p-6">
            <Head title="Programs" />

            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Programs
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Manage the group programs shown on the public Programs
                        page
                    </p>
                </div>
                <Button className="rounded-[10px]" asChild>
                    <Link href="/admin/programs/add">
                        <Plus /> Add Program
                    </Link>
                </Button>
            </div>

            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Total</p>
                    <p className="text-2xl font-bold">{stats.total}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Published</p>
                    <p className="text-2xl font-bold">{stats.active}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Registrations
                    </p>
                    <p className="text-2xl font-bold">{stats.registrations}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Awaiting Review
                    </p>
                    <p className="text-2xl font-bold">{stats.pending}</p>
                </Card>
            </div>

            <Card className="rounded-[10px] p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <label className="mb-1 block text-sm font-medium text-muted-foreground">
                            Search
                        </label>
                        <Input
                            id="program-search"
                            placeholder="Search by program name..."
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
                            <SelectItem value="active">Published</SelectItem>
                            <SelectItem value="inactive">
                                Unpublished
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </Card>

            {programs.data.length > 0 ? (
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {programs.data.map((program) => (
                        <Card key={program.id} className="p-6">
                            <div className="mb-3 flex items-start justify-between gap-4">
                                <div>
                                    <p className="font-semibold">
                                        {program.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {program.category ?? 'Uncategorised'}
                                    </p>
                                </div>
                                <Badge
                                    variant={
                                        program.is_active
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {program.is_active
                                        ? 'Published'
                                        : 'Unpublished'}
                                </Badge>
                            </div>

                            <div className="mb-4 space-y-1 text-sm text-muted-foreground">
                                {formatProgramDates(program) && (
                                    <p>{formatProgramDates(program)}</p>
                                )}
                                <p>
                                    {formatProgramPrice(program.price)}
                                    {program.age_range
                                        ? ` · ${program.age_range}`
                                        : ''}
                                </p>
                                <p>
                                    {program.taken_places}
                                    {program.capacity == null
                                        ? ' registered (no limit)'
                                        : ` of ${program.capacity} places taken`}
                                </p>
                            </div>

                            <div className="grid grid-cols-3 gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-[5px]"
                                    asChild
                                >
                                    <Link
                                        href={`/admin/programs/${program.slug}`}
                                    >
                                        <Eye className="h-4 w-4" /> View
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-[5px]"
                                    asChild
                                >
                                    <Link
                                        href={`/admin/programs/edit/${program.slug}`}
                                    >
                                        <Pencil className="h-4 w-4" /> Edit
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-[5px] text-destructive hover:text-destructive"
                                    onClick={() => setPendingDelete(program)}
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
                        <Sparkles className="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p className="mb-2 text-lg font-medium">
                            No Programs Found
                        </p>
                    </div>
                </Card>
            )}

            {pendingDelete && (
                <DeleteProgramModal
                    slug={pendingDelete.slug}
                    name={pendingDelete.name}
                    registrationCount={pendingDelete.registrations_count}
                    isOpen={pendingDelete !== null}
                    onClose={() => setPendingDelete(null)}
                />
            )}
        </div>
    );
}

AdminProgramsIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
