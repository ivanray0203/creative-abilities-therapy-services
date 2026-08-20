import { Head } from '@inertiajs/react';

import ProgramForm from '@/components/admin/program-form';
import AdminLayout from '@/layouts/admin-layout';

export default function AdminProgramCreate() {
    return (
        <>
            <Head title="Add Program" />
            <ProgramForm />
        </>
    );
}

AdminProgramCreate.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
