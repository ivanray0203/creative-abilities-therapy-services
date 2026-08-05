import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import SessionsForm from '@/components/sessions/sessions-form';
import AdminLayout from '@/layouts/admin-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client, ServiceOffering } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

interface SessionsCreateProps {
    isAdmin: boolean;
    therapists: TherapistOption[];
    services: ServiceOffering[];
    clients: Client[];
}

/** Shared "New Session" page — reference: cats-frontend/src/forms/SessionsForm.tsx (`/sessions/add`). */
export default function SessionsCreate({
    isAdmin,
    therapists,
    services,
    clients,
}: SessionsCreateProps) {
    return (
        <>
            <Head title="New Session" />
            <SessionsForm
                isAdmin={isAdmin}
                therapists={therapists}
                services={services}
                clients={clients}
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
function SessionsCreateLayout({ children }: PropsWithChildren) {
    const { isAdmin } = usePage<{ isAdmin: boolean }>().props;

    return isAdmin ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

SessionsCreate.layout = (page: React.ReactNode) => (
    <SessionsCreateLayout>{page}</SessionsCreateLayout>
);
