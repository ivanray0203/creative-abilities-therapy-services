import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';

import { InvoiceStatusBadge } from '@/components/invoices/badges';
import { formatDate } from '@/lib/helpers';
import type { Invoice } from '@/types/invoice';

/** Reference: cats-frontend/src/components/InvoicesTable.tsx — shared across InvoicesPage and the client-detail Invoices tab. */
export default function InvoicesTable({
    invoices,
    basePath,
}: {
    invoices: Invoice[];
    basePath: string;
}) {
    if (invoices.length === 0) {
        return (
            <div className="py-8 text-center text-muted-foreground">
                No invoices found
            </div>
        );
    }

    return (
        <div className="w-full overflow-x-auto">
            <table className="w-full">
                <thead className="border-b text-left text-sm text-muted-foreground">
                    <tr>
                        <th className="pb-3">Invoice #</th>
                        <th className="hidden pb-3 md:table-cell">Client</th>
                        <th className="pb-3">Date</th>
                        <th className="hidden pb-3 md:table-cell">Due Date</th>
                        <th className="pb-3">Total</th>
                        <th className="pb-3">Status</th>
                        <th className="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {invoices.map((invoice) => (
                        <tr key={invoice.id} className="border-b last:border-0">
                            <td className="py-4">
                                {invoice.invoice_id ?? `#${invoice.id}`}
                            </td>
                            <td className="hidden py-4 md:table-cell">
                                {invoice.client?.original_intake
                                    ? `${invoice.client.original_intake.child_first_name} ${invoice.client.original_intake.child_last_name}`
                                    : '-'}
                            </td>
                            <td className="py-4">
                                {formatDate(invoice.invoice_date)}
                            </td>
                            <td className="hidden py-4 md:table-cell">
                                {formatDate(invoice.due_date)}
                            </td>
                            <td className="py-4">
                                ${Number(invoice.total).toFixed(2)}
                            </td>
                            <td className="py-4">
                                <InvoiceStatusBadge status={invoice.status} />
                            </td>
                            <td className="py-4">
                                <Link
                                    href={`${basePath}/${invoice.id}`}
                                    className="flex items-center gap-1 text-sm text-primary"
                                >
                                    <Eye className="h-4 w-4" /> View
                                </Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
