import { Head } from '@inertiajs/react';

import TeamMemberForm from '@/components/admin/team-member-form';
import AdminLayout from '@/layouts/admin-layout';

/** Admin "Add Team Member" page, wraps the shared TeamMemberForm. */
export default function AdminTeamCreate() {
    return (
        <>
            <Head title="Add Team Member" />
            <TeamMemberForm />
        </>
    );
}

AdminTeamCreate.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
