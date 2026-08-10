import { Head, Link } from '@inertiajs/react';
import { Edit, Plus, Printer, Trash, UserCog } from 'lucide-react';
import { useState } from 'react';

import AddClientServiceModal from '@/components/admin/add-client-service-modal';
import AssignTherapistModal from '@/components/admin/assign-therapist-modal';
import { ClientStatusBadge } from '@/components/admin/client/badges';
import DocumentsTab from '@/components/admin/client/documents-tab';
import FundingTab from '@/components/admin/client/funding-tab';
import InvoicesTab from '@/components/admin/client/invoices-tab';
import NotesTab from '@/components/admin/client/notes-tab';
import OverviewTab from '@/components/admin/client/overview-tab';
import ProgressTab from '@/components/admin/client/progress-tab';
import SessionsTab from '@/components/admin/client/sessions-tab';
import TherapistTab from '@/components/admin/client/therapist-tab';
import DeleteClientModal from '@/components/admin/delete-client-modal';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import type { Client, ServiceOffering } from '@/types/client';
import type { DeclinedService, TherapistOption } from '@/types/intake';

interface ClientShowProps {
    client: Client;
    declinedServices: DeclinedService[];
    therapists: TherapistOption[];
    services: ServiceOffering[];
}

/** Admin client detail page, ported from cats-frontend/src/pages/admin/ClientDetailPage.tsx. */
export default function AdminClientShow({
    client,
    declinedServices,
    therapists,
    services,
}: ClientShowProps) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [addServiceOpen, setAddServiceOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const intake = client.original_intake;

    return (
        <>
            <Head
                title={`${intake?.child_first_name ?? 'Client'} ${intake?.child_last_name ?? ''}`}
            />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                                {intake?.child_first_name}{' '}
                                {intake?.child_last_name}
                            </h1>
                            <ClientStatusBadge status={client.status} />
                        </div>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            Client #{client.id}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => setAssignOpen(true)}
                        >
                            <UserCog /> Reassign Therapist
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => setAddServiceOpen(true)}
                        >
                            <Plus /> Add Service
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            asChild
                        >
                            <Link href={`/admin/clients/${client.id}/edit`}>
                                <Edit /> Edit
                            </Link>
                        </Button>
                        {/*
                         * A file download, so a plain anchor rather than an
                         * Inertia <Link> — a router visit would try to parse
                         * the PDF as a page response.
                         */}
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            asChild
                        >
                            <a href={`/admin/clients/${client.id}/pdf`}>
                                <Printer /> Export PDF
                            </a>
                        </Button>
                        <Button
                            variant="destructive"
                            className="rounded-[10px]"
                            onClick={() => setDeleteOpen(true)}
                        >
                            <Trash /> Delete
                        </Button>
                    </div>
                </div>

                <Tabs defaultValue="overview">
                    <TabsList className="flex w-full flex-wrap justify-start">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="sessions">Sessions</TabsTrigger>
                        <TabsTrigger value="documents">Documents</TabsTrigger>
                        <TabsTrigger value="funding">Funding</TabsTrigger>
                        <TabsTrigger value="invoices">Invoices</TabsTrigger>
                        <TabsTrigger value="notes">Notes</TabsTrigger>
                        <TabsTrigger value="progress">Progress</TabsTrigger>
                        <TabsTrigger value="therapist">Therapist</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <OverviewTab
                            client={client}
                            declinedServices={declinedServices}
                            therapists={therapists}
                        />
                    </TabsContent>
                    <TabsContent value="sessions">
                        <SessionsTab client={client} />
                    </TabsContent>
                    <TabsContent value="documents">
                        <DocumentsTab client={client} />
                    </TabsContent>
                    <TabsContent value="funding">
                        <FundingTab client={client} />
                    </TabsContent>
                    <TabsContent value="invoices">
                        <InvoicesTab client={client} />
                    </TabsContent>
                    <TabsContent value="notes">
                        <NotesTab client={client} />
                    </TabsContent>
                    <TabsContent value="progress">
                        <ProgressTab />
                    </TabsContent>
                    <TabsContent value="therapist">
                        <TherapistTab client={client} therapists={therapists} />
                    </TabsContent>
                </Tabs>
            </div>

            <AssignTherapistModal
                client={client}
                therapists={therapists}
                isOpen={assignOpen}
                onClose={() => setAssignOpen(false)}
            />
            <AddClientServiceModal
                client={client}
                services={services}
                therapists={therapists}
                isOpen={addServiceOpen}
                onClose={() => setAddServiceOpen(false)}
            />
            <DeleteClientModal
                clientId={client.id}
                isOpen={deleteOpen}
                onClose={() => setDeleteOpen(false)}
            />
        </>
    );
}

AdminClientShow.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
