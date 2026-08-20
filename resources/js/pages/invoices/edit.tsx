import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import InvoiceForm from '@/components/invoices/invoice-form';
import AdminLayout from '@/layouts/admin-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client } from '@/types/client';
import type { Invoice, InvoiceServiceOption } from '@/types/invoice';

interface InvoicesEditProps {
    invoice: Invoice;
    role: 'admin' | 'therapist';
    clients: Client[];
    services: InvoiceServiceOption[];
    therapistInvoices: Invoice[];
}

const BASE_PATHS: Record<InvoicesEditProps['role'], string> = {
    admin: '/admin/invoices',
    therapist: '/therapist/invoices',
};

/** Shared "Edit Invoice" page — reference: cats-frontend/src/forms/InvoiceForm.tsx. */
export default function InvoicesEdit({
    invoice,
    role,
    clients,
    services,
    therapistInvoices,
}: InvoicesEditProps) {
    return (
        <>
            <Head title="Edit Invoice" />
            <InvoiceForm
                invoice={invoice}
                basePath={BASE_PATHS[role]}
                clients={clients}
                services={services}
                therapistInvoices={therapistInvoices}
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
function InvoicesEditLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: InvoicesEditProps['role'] }>().props;

    return role === 'admin' ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

InvoicesEdit.layout = (page: React.ReactNode) => (
    <InvoicesEditLayout>{page}</InvoicesEditLayout>
);
