import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import ComplaintForm from '@/components/complaints/complaint-form';
import ClientLayout from '@/layouts/client-layout';
import TherapistLayout from '@/layouts/therapist-layout';

interface ComplaintsCreateProps {
    role: 'therapist' | 'client';
    basePath: string;
    sessionsUrl: string;
}

/** Shared "File a Complaint" page — reference: cats-frontend/src/forms/ComplaintForm.tsx. */
export default function ComplaintsCreate({
    role,
    basePath,
    sessionsUrl,
}: ComplaintsCreateProps) {
    return (
        <>
            <Head title="File a Complaint" />
            <ComplaintForm
                role={role}
                basePath={basePath}
                sessionsUrl={sessionsUrl}
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
function ComplaintsCreateLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: ComplaintsCreateProps['role'] }>().props;

    return role === 'therapist' ? (
        <TherapistLayout>{children}</TherapistLayout>
    ) : (
        <ClientLayout>{children}</ClientLayout>
    );
}

ComplaintsCreate.layout = (page: React.ReactNode) => (
    <ComplaintsCreateLayout>{page}</ComplaintsCreateLayout>
);
