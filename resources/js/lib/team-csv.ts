import type { TeamMember } from '@/types/team-member';

/**
 * Client-side CSV export for the team list, mirrors
 * `lib/sessions-csv.ts`'s quoting/escaping conventions.
 */
const HEADERS = ['First Name', 'Last Name', 'Position', 'Email', 'Phone'];

function escapeCell(value: string | number | null | undefined): string {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

export function exportTeamCsv(teamMembers: TeamMember[]): void {
    const rows = teamMembers.map((teamMember) => [
        teamMember.user?.first_name ?? '',
        teamMember.user?.last_name ?? '',
        teamMember.position ?? '',
        teamMember.user?.email ?? '',
        teamMember.phone ?? '',
    ]);

    const csvContent = [
        HEADERS.map(escapeCell).join(','),
        ...rows.map((row) => row.map(escapeCell).join(',')),
    ].join('\n');

    const link = document.createElement('a');
    link.href = `data:text/csv;charset=utf-8,${encodeURIComponent(csvContent)}`;
    link.download = 'Team_Members.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
