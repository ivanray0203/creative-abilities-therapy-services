import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import SessionsForm from '@/components/sessions/sessions-form';
import AdminLayout from '@/layouts/admin-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client, ServiceOffering } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

interface SessionsEditProps {
    session: ScheduleSession;
    isAdmin: boolean;
    therapists: TherapistOption[];
    services: ServiceOffering[];
    clients: Client[];
}

/** Shared "Edit/Reschedule Session" page — reference: cats-frontend/src/forms/SessionsForm.tsx (`/sessions/edit/:id`). */
export default function SessionsEdit({
    session,
    isAdmin,
    therapists,
    services,
    clients,
}: SessionsEditProps) {
    return (
        <>
            <Head title="Edit Session" />
            <SessionsForm
                session={session}
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
function SessionsEditLayout({ children }: PropsWithChildren) {
    const { isAdmin } = usePage<{ isAdmin: boolean }>().props;

    return isAdmin ? (
        <AdminLayout>{children}</AdminLayout>
    ) : (
        <TherapistLayout>{children}</TherapistLayout>
    );
}

SessionsEdit.layout = (page: React.ReactNode) => (
    <SessionsEditLayout>{page}</SessionsEditLayout>
);
