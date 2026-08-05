import { Head } from '@inertiajs/react';

import ClientForm from '@/components/admin/client-form';
import AdminLayout from '@/layouts/admin-layout';
import type { Client } from '@/types/client';

interface ClientEditProps {
    client: Client;
}

/** Admin "Edit Client" page, ported from cats-frontend/src/pages/admin/ClientDetailPage.tsx (edit mode). */
export default function AdminClientEdit({ client }: ClientEditProps) {
    return (
        <>
            <Head title="Edit Client" />
            <ClientForm client={client} />
        </>
    );
}

AdminClientEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
