import type { Invoice } from '@/types/invoice';

/**
 * Client-side CSV export for the invoices list, mirrors
 * `lib/sessions-csv.ts`'s quoting/escaping conventions.
 */
const HEADERS = [
    'Invoice #',
    'Client',
    'Invoice Date',
    'Due Date',
    'Sub Total',
    'GST',
    'Total',
    'Status',
    'Billed By',
];

function escapeCell(value: string | number | null | undefined): string {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

export function exportInvoicesCsv(invoices: Invoice[]): void {
    const rows = invoices.map((invoice) => [
        invoice.invoice_id ?? `#${invoice.id}`,
        invoice.client?.original_intake
            ? `${invoice.client.original_intake.child_first_name} ${invoice.client.original_intake.child_last_name}`
            : '',
        invoice.invoice_date,
        invoice.due_date,
        invoice.sub_total,
        invoice.gst,
        invoice.total,
        invoice.status,
        invoice.billed_by,
    ]);

    const csvContent = [
        HEADERS.map(escapeCell).join(','),
        ...rows.map((row) => row.map(escapeCell).join(',')),
    ].join('\n');

    const link = document.createElement('a');
    link.href = `data:text/csv;charset=utf-8,${encodeURIComponent(csvContent)}`;
    link.download = 'Invoices.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
