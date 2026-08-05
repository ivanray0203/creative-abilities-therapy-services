import { Head, router } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

import { ClientCard } from '@/components/therapist/client-card';
import { ClientScheduleModal } from '@/components/therapist/client-schedule-modal';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client } from '@/types/client';
import type { Paginated } from '@/types/intake';

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

                <Tabs
                    value={filters.status}
                    onValueChange={applyStatus}
                    className="w-full"
                >
                    <TabsList>
                        <TabsTrigger value="active">Active</TabsTrigger>
                        <TabsTrigger value="inactive">Inactive</TabsTrigger>
                    </TabsList>

                    <TabsContent
                        value={filters.status}
                        className="mt-6 space-y-4"
                    >
                        {clients.data.length === 0 ? (
                            <Card className="rounded-[10px] p-10 text-center text-muted-foreground">
                                <Users className="mx-auto mb-2 h-6 w-6" />
                                No clients found
                            </Card>
                        ) : (
                            clients.data.map((client) => (
                                <ClientCard
                                    key={client.id}
                                    client={client}
                                    onViewSchedule={setSelectedClient}
                                />
                            ))
                        )}

                        {clients.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
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
                        )}
                    </TabsContent>
                </Tabs>
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

TherapistClientsIndex.layout = (page: React.ReactElement) => (
    <TherapistLayout>{page}</TherapistLayout>
);
