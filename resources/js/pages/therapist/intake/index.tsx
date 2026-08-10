import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Search } from 'lucide-react';
import { useEffect, useState } from 'react';

import { IntakeStatusBadge } from '@/components/intake/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Intake, Paginated } from '@/types/intake';

interface TherapistReview {
    id: number;
    service: string | null;
    status: string;
}

type ReviewIntake = Intake & { therapist_reviews?: TherapistReview[] };

interface TherapistIntakeIndexProps {
    intakes: Paginated<ReviewIntake>;
    filters: {
        search: string;
        status: string;
    };
}

const AWAITING = ['pending', 'reassign'];

export default function TherapistIntakeIndex({
    intakes,
    filters,
}: TherapistIntakeIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/therapist/intake',
                { ...filters, search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatus = (status: string) => {
        router.get(
            '/therapist/intake',
            { ...filters, search, status },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/therapist/intake',
            { ...filters, search, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Intake Reviews" />

            <div className="space-y-6 p-6">
                <div>
                    <div className="flex items-center gap-2">
                        <ClipboardList className="h-6 w-6 text-primary" />
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Intake Reviews
                        </h1>
                    </div>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Intakes assigned to you to approve or decline
                    </p>
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

                <Tabs
                    value={filters.status}
                    onValueChange={applyStatus}
                    className="w-full"
                >
                    <TabsList>
                        <TabsTrigger value="pending">Awaiting me</TabsTrigger>
                        <TabsTrigger value="decided">Decided</TabsTrigger>
                        <TabsTrigger value="all">All</TabsTrigger>
                    </TabsList>

                    <TabsContent
                        value={filters.status}
                        className="mt-6 space-y-4"
                    >
                        {intakes.data.length === 0 ? (
                            <Card className="rounded-[10px] p-10 text-center text-muted-foreground">
                                <ClipboardList className="mx-auto mb-2 h-6 w-6" />
                                No intakes assigned to you here
                            </Card>
                        ) : (
                            intakes.data.map((intake) => {
                                const reviews = intake.therapist_reviews ?? [];
                                const awaiting = reviews.filter((review) =>
                                    AWAITING.includes(review.status),
                                );

                                return (
                                    <Card
                                        key={intake.id}
                                        className="rounded-[10px] p-5"
                                    >
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="min-w-0">
                                                <Link
                                                    href={`/therapist/intake/${intake.id}`}
                                                    className="text-lg font-semibold hover:underline"
                                                >
                                                    {intake.child_first_name}{' '}
                                                    {intake.child_last_name}
                                                </Link>
                                                <p className="text-sm text-muted-foreground">
                                                    {intake.reference_number ??
                                                        `Intake #${intake.id}`}
                                                    {intake.age
                                                        ? ` · Age ${intake.age}`
                                                        : ''}
                                                </p>

                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    {reviews.map((review) => (
                                                        <Badge
                                                            key={review.id}
                                                            variant="outline"
                                                            className="rounded"
                                                        >
                                                            {review.service ??
                                                                'Whole intake'}
                                                            {' — '}
                                                            {review.status}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2">
                                                {awaiting.length > 0 && (
                                                    <Badge className="rounded-[5px] border border-cyan-600 bg-cyan-50 text-cyan-800">
                                                        {awaiting.length}{' '}
                                                        awaiting you
                                                    </Badge>
                                                )}
                                                <IntakeStatusBadge
                                                    status={intake.status}
                                                />
                                            </div>
                                        </div>
                                    </Card>
                                );
                            })
                        )}

                        {intakes.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                    onClick={() =>
                                        goToPage(intakes.current_page - 1)
                                    }
                                    disabled={intakes.current_page <= 1}
                                >
                                    Previous
                                </button>
                                <span className="flex items-center px-2 text-sm">
                                    Page {intakes.current_page} of{' '}
                                    {intakes.last_page}
                                </span>
                                <button
                                    type="button"
                                    className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                    onClick={() =>
                                        goToPage(intakes.current_page + 1)
                                    }
                                    disabled={
                                        intakes.current_page >=
                                        intakes.last_page
                                    }
                                >
                                    Next
                                </button>
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

TherapistIntakeIndex.layout = (page: React.ReactElement) => (
    <TherapistLayout>{page}</TherapistLayout>
);
