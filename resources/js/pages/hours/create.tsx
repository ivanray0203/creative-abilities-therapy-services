import { Head } from '@inertiajs/react';

import HoursForm from '@/components/hours/hours-form';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Client } from '@/types/client';

interface HoursCreateProps {
    clients: Client[];
}

/** "Log Hours" — one timesheet entry is saved per day on the form. */
export default function HoursCreate({ clients }: HoursCreateProps) {
    return (
        <>
            <Head title="Log Hours" />
            <HoursForm clients={clients} />
        </>
    );
}

HoursCreate.layout = (page: React.ReactNode) => (
    <TherapistLayout>{page}</TherapistLayout>
);
