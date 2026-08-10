import { Head, Link, router } from '@inertiajs/react';
import { FileText, Plus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';

import { IntakeStatusBadge } from '@/components/intake/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import ClientLayout from '@/layouts/client-layout';
import type { Intake, Paginated } from '@/types/intake';

interface ClientIntakeIndexProps {
    intakes: Paginated<Intake>;
    filters: {
        search: string;
    };
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function ClientIntakeIndex({
    intakes,
    filters,
}: ClientIntakeIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/client/intake',
                { search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const goToPage = (page: number) => {
        router.get(
            '/client/intake',
            { search, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Intakes" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <FileText className="h-6 w-6 text-primary" />
                            <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                                Intakes
                            </h1>
                        </div>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            Applications you've submitted and where each one is
                            in the process
                        </p>
                    </div>

                    <Button asChild className="rounded-[10px]">
                        <Link href="/client/intake/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Add Intake
                        </Link>
                    </Button>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search by child name or reference number..."
                            className="rounded-[10px] pl-10"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                </Card>

                {intakes.data.length === 0 ? (
                    <Card className="rounded-[10px] p-10 text-center text-muted-foreground">
                        <FileText className="mx-auto mb-2 h-6 w-6" />
                        {filters.search
                            ? 'No intakes match your search'
                            : "You haven't submitted any intakes yet"}
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {intakes.data.map((intake) => {
                            return (
                                <Card
                                    key={intake.id}
                                    className="rounded-[10px] p-5"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0">
                                            <h2 className="text-lg font-semibold">
                                                {intake.child_first_name}{' '}
                                                {intake.child_last_name}
                                            </h2>
                                            <p className="text-sm text-muted-foreground">
                                                {intake.reference_number ??
                                                    `Intake #${intake.id}`}{' '}
                                                &middot; Submitted{' '}
                                                {formatDate(intake.created_at)}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2">
                                            {intake.approved_as_client && (
                                                <Badge className="rounded-[5px] border border-green-400 bg-green-100 text-green-700">
                                                    Enrolled
                                                </Badge>
                                            )}
                                            <IntakeStatusBadge
                                                status={intake.status}
                                            />
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {intakes.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        <button
                            type="button"
                            className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                            onClick={() => goToPage(intakes.current_page - 1)}
                            disabled={intakes.current_page <= 1}
                        >
                            Previous
                        </button>
                        <span className="flex items-center px-2 text-sm">
                            Page {intakes.current_page} of {intakes.last_page}
                        </span>
                        <button
                            type="button"
                            className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                            onClick={() => goToPage(intakes.current_page + 1)}
                            disabled={intakes.current_page >= intakes.last_page}
                        >
                            Next
                        </button>
                    </div>
                )}
            </div>
        </>
    );
}

ClientIntakeIndex.layout = (page: React.ReactElement) => (
    <ClientLayout>{page}</ClientLayout>
);
