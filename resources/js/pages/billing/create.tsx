import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import BillingForm from '@/components/billing/billing-form';
import AdminLayout from '@/layouts/admin-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { InvoiceServiceOption } from '@/types/invoice';

interface BillingCreateProps {
    role: 'admin' | 'therapist';
    clients: Client[];
    services: InvoiceServiceOption[];
    /** Admins only — the therapist the bill is raised for. */
    therapists: TherapistOption[];
}

const BASE_PATHS: Record<BillingCreateProps['role'], string> = {
    admin: '/admin/billing',
    therapist: '/therapist/billing',
};

/** "Create Bill" — one billing item is saved per service line on the form. */
export default function BillingCreate({
    role,
    clients,
    services,
    therapists,
}: BillingCreateProps) {
    return (
        <>
            <Head title="Create Bill" />
            <BillingForm
                basePath={BASE_PATHS[role]}
                sessionsPath={BASE_PATHS[role].replace('/billing', '/sessions')}
                clients={clients}
                services={services}
                therapists={therapists}
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
function BillingCreateLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: BillingCreateProps['role'] }>().props;

    return role === 'admin' ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

BillingCreate.layout = (page: React.ReactNode) => (
    <BillingCreateLayout>{page}</BillingCreateLayout>
);
