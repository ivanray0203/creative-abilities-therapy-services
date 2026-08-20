import { Head } from '@inertiajs/react';

import ProgramForm from '@/components/admin/program-form';
import type { ProgramFormValues } from '@/components/admin/program-form';
import AdminLayout from '@/layouts/admin-layout';

export default function AdminProgramEdit({
    program,
}: {
    program: ProgramFormValues;
}) {
    return (
        <>
            <Head title={`Edit ${program.name}`} />
            <ProgramForm program={program} />
        </>
    );
}

AdminProgramEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
