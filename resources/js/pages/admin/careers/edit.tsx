import { Head } from '@inertiajs/react';

import CareerForm from '@/components/admin/career-form';
import AdminLayout from '@/layouts/admin-layout';
import type { Career } from '@/types/career';

interface CareerEditProps {
    career: Career;
}

/** Admin "Edit Position" page, wraps the shared CareerForm. */
export default function AdminCareerEdit({ career }: CareerEditProps) {
    return (
        <>
            <Head title="Edit Position" />
            <CareerForm career={career} />
        </>
    );
}

AdminCareerEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
