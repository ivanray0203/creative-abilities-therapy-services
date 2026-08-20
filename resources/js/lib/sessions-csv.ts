import { formatScheduledDate, formatScheduledTime } from '@/lib/helpers';
import { sessionServiceLabel } from '@/lib/sessions';
import type { ScheduleSession } from '@/types/session';

/**
 * Client-side CSV export for the sessions list, mirrors `lib/intake-csv.ts`'s
 * quoting/escaping conventions.
 */
const HEADERS = [
    'Client',
    'Therapist',
    'Service',
    'Date',
    'Start Time',
    'End Time',
    'Duration',
    'Location',
    'Status',
];

function escapeCell(value: string | number | null | undefined): string {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

export function exportSessionsCsv(sessions: ScheduleSession[]): void {
    const rows = sessions.map((session) => [
        session.client?.original_intake
            ? `${session.client.original_intake.child_first_name} ${session.client.original_intake.child_last_name}`
            : '',
        session.therapist
            ? `${session.therapist.first_name} ${session.therapist.last_name}`
            : '',
        sessionServiceLabel(session, ''),
        formatScheduledDate(session.scheduled_start),
        formatScheduledTime(session.scheduled_start),
        formatScheduledTime(session.scheduled_end),
        session.duration,
        session.location,
        session.status,
    ]);

    const csvContent = [
        HEADERS.map(escapeCell).join(','),
        ...rows.map((row) => row.map(escapeCell).join(',')),
    ].join('\n');

    const link = document.createElement('a');
    link.href = `data:text/csv;charset=utf-8,${encodeURIComponent(csvContent)}`;
    link.download = 'Sessions.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
