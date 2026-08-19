import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Users } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/admin-layout';
import { formatProgramDate, formatProgramPrice } from '@/lib/programs';

interface AdminProgramDetail {
    id: number;
    slug: string;
    name: string;
    category: string | null;
    summary: string;
    age_range: string | null;
    schedule: string | null;
    location: string | null;
    capacity: number | null;
    price: string | null;
    starts_on: string | null;
    ends_on: string | null;
    registration_closes_on: string | null;
    is_active: boolean;
    taken_places: number;
    is_open: boolean;
}

interface AdminRegistration {
    id: number;
    reference_number: string;
    participant: string;
    participant_date_of_birth: string | null;
    parent_name: string;
    parent_email: string;
    parent_phone: string;
    notes: string | null;
    status: string;
    registered_on: string | null;
}

const STATUSES = ['pending', 'confirmed', 'waitlisted', 'cancelled'];

export default function AdminProgramShow({
    program,
    registrations,
}: {
    program: AdminProgramDetail;
    registrations: AdminRegistration[];
}) {
    const setStatus = (registration: AdminRegistration, status: string) => {
        router.put(
            `/admin/programs/${program.slug}/registrations/${registration.id}`,
            { status },
            { preserveScroll: true },
        );
    };

    const facts = [
        { label: 'Category', value: program.category },
        { label: 'Ages', value: program.age_range },
        { label: 'Schedule', value: program.schedule },
        { label: 'Location', value: program.location },
        { label: 'Cost', value: formatProgramPrice(program.price) },
        { label: 'Starts', value: formatProgramDate(program.starts_on) },
        { label: 'Ends', value: formatProgramDate(program.ends_on) },
        {
            label: 'Registration closes',
            value: formatProgramDate(program.registration_closes_on),
        },
    ].filter((fact) => Boolean(fact.value));

    return (
        <div className="space-y-6 p-6">
            <Head title={program.name} />

            <Link
                href="/admin/programs"
                className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"
            >
                <ArrowLeft className="h-4 w-4" />
                All programs
            </Link>

            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            {program.name}
                        </h1>
                        <Badge
                            variant={
                                program.is_active ? 'default' : 'secondary'
                            }
                        >
                            {program.is_active ? 'Published' : 'Unpublished'}
                        </Badge>
                        {!program.is_open && program.is_active && (
                            <Badge variant="secondary">
                                Registration closed
                            </Badge>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        {program.summary}
                    </p>
                </div>
                <Button className="rounded-[10px]" asChild>
                    <Link href={`/admin/programs/edit/${program.slug}`}>
                        <Pencil className="h-4 w-4" /> Edit
                    </Link>
                </Button>
            </div>

            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Places taken
                    </p>
                    <p className="text-2xl font-bold">
                        {program.taken_places}
                        {program.capacity != null && (
                            <span className="text-base font-normal text-muted-foreground">
                                {' '}
                                / {program.capacity}
                            </span>
                        )}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Total registrations
                    </p>
                    <p className="text-2xl font-bold">{registrations.length}</p>
                </Card>
            </div>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-2 gap-4 p-5 md:grid-cols-4">
                    {facts.map((fact) => (
                        <div key={fact.label}>
                            <p className="text-xs text-muted-foreground">
                                {fact.label}
                            </p>
                            <p className="text-sm">{fact.value}</p>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <div>
                <h2 className="mb-3 text-lg font-semibold">Registrations</h2>

                {registrations.length === 0 ? (
                    <Card className="w-full p-12">
                        <div className="text-center text-muted-foreground">
                            <Users className="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p className="text-lg font-medium">
                                No registrations yet
                            </p>
                        </div>
                    </Card>
                ) : (
                    <Card className="rounded-[10px]">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Child</TableHead>
                                        <TableHead>Parent</TableHead>
                                        <TableHead>Contact</TableHead>
                                        <TableHead>Registered</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrations.map((registration) => (
                                        <TableRow key={registration.id}>
                                            <TableCell className="whitespace-nowrap">
                                                {registration.reference_number}
                                            </TableCell>
                                            <TableCell>
                                                <p>
                                                    {registration.participant}
                                                </p>
                                                {registration.participant_date_of_birth && (
                                                    <p className="text-xs text-muted-foreground">
                                                        Born{' '}
                                                        {formatProgramDate(
                                                            registration.participant_date_of_birth,
                                                        )}
                                                    </p>
                                                )}
                                                {registration.notes && (
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {registration.notes}
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {registration.parent_name}
                                            </TableCell>
                                            <TableCell>
                                                <p className="text-sm">
                                                    {registration.parent_email}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {registration.parent_phone}
                                                </p>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {formatProgramDate(
                                                    registration.registered_on,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Select
                                                    value={registration.status}
                                                    onValueChange={(status) =>
                                                        setStatus(
                                                            registration,
                                                            status,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        id={`registration-status-${registration.id}`}
                                                        className="w-36 rounded-[10px] capitalize"
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {STATUSES.map(
                                                            (status) => (
                                                                <SelectItem
                                                                    key={status}
                                                                    value={
                                                                        status
                                                                    }
                                                                    className="capitalize"
                                                                >
                                                                    {status}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </Card>
                )}
            </div>
        </div>
    );
}

AdminProgramShow.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
