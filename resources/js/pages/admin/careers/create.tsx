import { Head } from '@inertiajs/react';

import CareerForm from '@/components/admin/career-form';
import AdminLayout from '@/layouts/admin-layout';

/** Admin "Add Position" page, wraps the shared CareerForm. */
export default function AdminCareerCreate() {
    return (
        <>
            <Head title="Add Position" />
            <CareerForm />
        </>
    );
}

AdminCareerCreate.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
