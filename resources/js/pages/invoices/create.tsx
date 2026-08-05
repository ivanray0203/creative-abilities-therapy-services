import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import InvoiceForm from '@/components/invoices/invoice-form';
import AdminLayout from '@/layouts/admin-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client, ServiceOffering } from '@/types/client';

interface InvoicesCreateProps {
    role: 'admin' | 'therapist';
    clients: Client[];
    services: ServiceOffering[];
}

const BASE_PATHS: Record<InvoicesCreateProps['role'], string> = {
    admin: '/admin/invoices',
    therapist: '/therapist/invoices',
};

/** Shared "New Invoice" page — reference: cats-frontend/src/forms/InvoiceForm.tsx. */
export default function InvoicesCreate({
    role,
    clients,
    services,
}: InvoicesCreateProps) {
    return (
        <>
            <Head title="New Invoice" />
            <InvoiceForm
                basePath={BASE_PATHS[role]}
                clients={clients}
                services={services}
            />
        </>
    );
}

/**
 * Picks the layout via `usePage()` rather than the `page.props` argument
 * Inertia passes to `.layout()` — that argument comes back `undefined`
 * during client-side page swaps, which crashed navigation entirely when
 * read synchronously here.
 */
function InvoicesCreateLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: InvoicesCreateProps['role'] }>().props;

    return role === 'admin' ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

InvoicesCreate.layout = (page: React.ReactNode) => (
    <InvoicesCreateLayout>{page}</InvoicesCreateLayout>
);
