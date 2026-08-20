import { Head, router } from '@inertiajs/react';
import { Edit, Layers, Search } from 'lucide-react';
import { useEffect, useState } from 'react';

import InvoiceServiceRatesModal from '@/components/admin/invoice-service-rates-modal';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/types/intake';
import type { InvoiceService } from '@/types/invoice';

/** Sheet disciplines, labelled as the rate card groups them. */
const DISCIPLINE_LABELS: Record<string, string> = {
    slp: 'Speech-Language Pathology',
    psych: 'Psychology',
    ot: 'Occupational Therapy',
    pt: 'Physiotherapy',
    bc: 'Behavioural Consulting',
    aide: 'Aide Services',
    other: 'Other',
};

/** A null rate is a blank column on the sheet: not billable under that stream. */
const formatRate = (rate: string | null): string =>
    rate === null ? '—' : `$${rate}`;

interface AdminServicesIndexProps {
    services: Paginated<InvoiceService>;
    filters: { search: string; discipline: string };
    disciplines: string[];
}

/**
 * The invoice rate card — every billable "Service Provided" line, with its
 * rates editable per line.
 *
 * Per-therapist overrides of these rates are set on the team member's Rates
 * tab (/admin/team/{id}); this screen sets the clinic's published rates.
 */
export default function AdminServicesIndex({
    services,
    filters,
    disciplines,
}: AdminServicesIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [serviceToEdit, setServiceToEdit] = useState<InvoiceService | null>(
        null,
    );

    /** Debounced so typing doesn't fire a visit per keystroke. */
    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/services',
                { search, discipline: filters.discipline },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.search, filters.discipline]);

    const applyDiscipline = (discipline: string) => {
        router.get(
            '/admin/services',
            { search, discipline },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/admin/services',
            { page, search: filters.search, discipline: filters.discipline },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Invoice Services" />
            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-primary/10 p-3">
                        <Layers className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-3xl font-bold">
                                Invoice Services
                            </h1>
                            <Badge variant="secondary">{services.total}</Badge>
                        </div>
                        <p className="text-muted-foreground">
                            The billable rate card, with FSCD and
                            private/insurance rates
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-3 md:flex-row md:items-center">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by name or code"
                            className="rounded-[10px] pl-9"
                        />
                    </div>

                    <Select
                        value={filters.discipline}
                        onValueChange={applyDiscipline}
                    >
                        <SelectTrigger className="rounded-[10px] md:w-72">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All disciplines</SelectItem>
                            {disciplines.map((value) => (
                                <SelectItem key={value} value={value}>
                                    {DISCIPLINE_LABELS[value] ?? value}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <Card className="p-6">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Discipline</TableHead>
                                    <TableHead className="text-right">
                                        FSCD Rate
                                    </TableHead>
                                    <TableHead className="text-right whitespace-nowrap">
                                        Private/Insurance Rate
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {services.data.map((service) => (
                                    <TableRow key={service.id}>
                                        <TableCell>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium">
                                                    {service.name}
                                                </span>
                                                {!service.is_active && (
                                                    <Badge variant="destructive">
                                                        Inactive
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                Code: {service.code}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {DISCIPLINE_LABELS[
                                                    service.discipline
                                                ] ?? service.discipline}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatRate(service.rate_fscd)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatRate(service.rate_private)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="rounded-[10px]"
                                                onClick={() =>
                                                    setServiceToEdit(service)
                                                }
                                            >
                                                <Edit className="mr-1 h-4 w-4" />{' '}
                                                Edit Rates
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}

                                {services.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-center text-muted-foreground"
                                        >
                                            No services match this search.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {services.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            <button
                                type="button"
                                className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                onClick={() =>
                                    goToPage(services.current_page - 1)
                                }
                                disabled={services.current_page <= 1}
                            >
                                Previous
                            </button>
                            <span className="flex items-center px-2 text-sm">
                                Page {services.current_page} of{' '}
                                {services.last_page}
                            </span>
                            <button
                                type="button"
                                className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                onClick={() =>
                                    goToPage(services.current_page + 1)
                                }
                                disabled={
                                    services.current_page >= services.last_page
                                }
                            >
                                Next
                            </button>
                        </div>
                    )}
                </Card>
            </div>

            <InvoiceServiceRatesModal
                service={serviceToEdit}
                isOpen={serviceToEdit !== null}
                onClose={() => setServiceToEdit(null)}
            />
        </>
    );
}

AdminServicesIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
