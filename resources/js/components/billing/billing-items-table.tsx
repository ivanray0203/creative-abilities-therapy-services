import { router } from '@inertiajs/react';
import { Trash } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/helpers';
import type { BillingItem } from '@/types/billing';

/** The child a line was billed for, as the table shows it. */
function childName(item: BillingItem): string {
    const intake = item.client?.original_intake;

    return intake
        ? `${intake.child_first_name} ${intake.child_last_name}`
        : `Client #${item.client_id}`;
}

/**
 * The billing ledger: one row per service billed. A row is "Unbilled" until
 * a month-end invoice claims it, after which it shows the invoice it went
 * out on and can no longer be removed.
 */
export default function BillingItemsTable({
    items,
    basePath,
    showTherapist = false,
}: {
    items: BillingItem[];
    basePath: string;
    /** Admins see the whole team's bills and need them told apart. */
    showTherapist?: boolean;
}) {
    if (items.length === 0) {
        return (
            <div className="py-8 text-center text-muted-foreground">
                No billing items found
            </div>
        );
    }

    const remove = (item: BillingItem) => {
        if (!confirm(`Remove billing item ${item.billing_number}?`)) {
            return;
        }

        router.delete(`${basePath}/${item.id}`, { preserveScroll: true });
    };

    return (
        <div className="w-full overflow-x-auto">
            <table className="w-full">
                <thead className="border-b text-left text-sm text-muted-foreground">
                    <tr>
                        <th className="pb-3">Bill #</th>
                        <th className="pb-3">Client</th>
                        {showTherapist && (
                            <th className="hidden pb-3 md:table-cell">
                                Therapist
                            </th>
                        )}
                        <th className="pb-3">Service</th>
                        <th className="hidden pb-3 md:table-cell">Qty</th>
                        <th className="hidden pb-3 md:table-cell">Rate</th>
                        <th className="pb-3">Amount</th>
                        <th className="hidden pb-3 md:table-cell">Date</th>
                        <th className="pb-3">Status</th>
                        <th className="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item) => (
                        <tr key={item.id} className="border-b last:border-0">
                            <td className="py-4">{item.billing_number}</td>
                            <td className="py-4">{childName(item)}</td>
                            {showTherapist && (
                                <td className="hidden py-4 md:table-cell">
                                    {item.therapist
                                        ? `${item.therapist.first_name} ${item.therapist.last_name}`
                                        : '-'}
                                </td>
                            )}
                            <td className="py-4">{item.service_name}</td>
                            <td className="hidden py-4 md:table-cell">
                                {Number(item.quantity)}
                            </td>
                            <td className="hidden py-4 md:table-cell">
                                ${Number(item.rate).toFixed(2)}
                            </td>
                            <td className="py-4">
                                ${Number(item.amount).toFixed(2)}
                            </td>
                            <td className="hidden py-4 md:table-cell">
                                {formatDate(item.created_at)}
                            </td>
                            <td className="py-4">
                                {item.invoice_id ? (
                                    <Badge variant="secondary">
                                        {item.invoice?.invoice_id ?? 'Invoiced'}
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">Unbilled</Badge>
                                )}
                            </td>
                            <td className="py-4">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    className="rounded-[10px] text-red-700 hover:bg-red-600 hover:text-white"
                                    disabled={item.invoice_id !== null}
                                    onClick={() => remove(item)}
                                >
                                    <Trash className="h-4 w-4" />
                                </Button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
