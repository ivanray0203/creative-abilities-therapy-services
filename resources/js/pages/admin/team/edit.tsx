import { Head } from '@inertiajs/react';

import TeamMemberForm from '@/components/admin/team-member-form';
import AdminLayout from '@/layouts/admin-layout';
import type { TeamMember } from '@/types/team-member';

interface TeamEditProps {
    teamMember: TeamMember;
}

/** Admin "Edit Team Member" page, wraps the shared TeamMemberForm. */
export default function AdminTeamEdit({ teamMember }: TeamEditProps) {
    return (
        <>
            <Head title="Edit Team Member" />
            <TeamMemberForm teamMember={teamMember} />
        </>
    );
}

AdminTeamEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
