import { Link } from '@inertiajs/react';

import { InvoiceStatusBadge } from '@/components/invoices/badges';
import { Card, CardContent } from '@/components/ui/card';
import type { Invoice } from '@/types/invoice';

/**
 * The other half of a two-hop bill.
 *
 * The clinic invoices the family for work a therapist invoiced the clinic
 * for, so on an admin's invoice this shows the cost behind it — what they owe
 * the therapist — and on a therapist's invoice it shows which client invoices
 * recover it. Admin-only: neither counterpart is the family's business.
 */
export default function LinkedInvoicePanel({
    invoice,
    basePath,
}: {
    invoice: Invoice;
    basePath: string;
}) {
    const owed = invoice.linked_therapist_invoice;
    const recoveredBy = invoice.linked_admin_invoices ?? [];

    if (!owed && recoveredBy.length === 0) {
        return null;
    }

    return (
        <Card className="rounded-[10px]">
            <CardContent className="p-5">
                <p className="font-bold text-primary">
                    {owed ? 'Cost Behind This Invoice' : 'Recovered By'}
                </p>
                <p className="text-sm text-muted-foreground">
                    {owed
                        ? 'What the clinic owes the therapist for this work.'
                        : 'Invoices the clinic raised to the family for this work.'}
                </p>

                <div className="mt-4 space-y-2">
                    {owed && (
                        <LinkedRow
                            invoice={owed}
                            basePath={basePath}
                            label={
                                owed.therapist
                                    ? `${owed.therapist.first_name} ${owed.therapist.last_name}`
                                    : 'Therapist'
                            }
                        />
                    )}
                    {recoveredBy.map((adminInvoice) => (
                        <LinkedRow
                            key={adminInvoice.id}
                            invoice={adminInvoice}
                            basePath={basePath}
                            label={
                                adminInvoice.client?.original_intake
                                    ? `${adminInvoice.client.original_intake.child_first_name} ${adminInvoice.client.original_intake.child_last_name}`
                                    : 'Client'
                            }
                        />
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

function LinkedRow({
    invoice,
    basePath,
    label,
}: {
    invoice: Invoice;
    basePath: string;
    label: string;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-2 rounded-[10px] border p-3">
            <div>
                <Link
                    href={`${basePath}/${invoice.id}`}
                    className="font-medium text-primary"
                >
                    {invoice.invoice_id ?? `Invoice #${invoice.id}`}
                </Link>
                <p className="text-sm text-muted-foreground">{label}</p>
            </div>
            <div className="flex items-center gap-3">
                <p className="font-semibold">
                    ${Number(invoice.total ?? 0).toFixed(2)}
                </p>
                <InvoiceStatusBadge status={invoice.status} />
            </div>
        </div>
    );
}
