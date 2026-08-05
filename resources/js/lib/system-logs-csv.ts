import type { SystemLog } from '@/types/system-log';

/**
 * Client-side CSV export for the system logs list, mirrors
 * `lib/sessions-csv.ts`'s quoting/escaping conventions.
 */
const HEADERS = ['Status', 'Timestamp', 'User', 'Action', 'Module', 'Details'];

function escapeCell(value: string | number | null | undefined): string {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

export function exportSystemLogsCsv(logs: SystemLog[]): void {
    const rows = logs.map((log) => [
        log.details?.status ?? 'success',
        log.created_at,
        log.details?.user_email ?? '',
        log.action,
        log.details?.module ?? '',
        log.details?.detail ?? '',
    ]);

    const csvContent = [
        HEADERS.map(escapeCell).join(','),
        ...rows.map((row) => row.map(escapeCell).join(',')),
    ].join('\n');

    const link = document.createElement('a');
    link.href = `data:text/csv;charset=utf-8,${encodeURIComponent(csvContent)}`;
    link.download = 'System_Logs.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
