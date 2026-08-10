import { usePage } from '@inertiajs/react';

import { InvoiceStatusBadge } from '@/components/invoices/badges';
import { Card, CardContent } from '@/components/ui/card';
import type { Invoice } from '@/types/invoice';

/**
 * The printable invoice layout — logo/bill-from, bill-to, services table,
 * totals, activity timeline. Shared by the InvoiceDetailPage and the PDF
 * export modal (reference: cats-frontend/src/pages/admin/InvoiceDetailPage.tsx).
 */
export default function InvoicePrintable({ invoice }: { invoice: Invoice }) {
    const { organization } = usePage().props;
    const childName = invoice.client?.original_intake
        ? `${invoice.client.original_intake.child_first_name} ${invoice.client.original_intake.child_last_name}`
        : invoice.bill_to_name;

    /*
     * Billing runs in two hops. A therapist bills the clinic for the work
     * they delivered; the clinic bills the family. The document has to be
     * addressed accordingly, or a therapist's invoice reads as though the
     * parent owes them directly.
     */
    const isTherapistBill = invoice.billed_by === 'therapist';
    const therapistName = invoice.therapist
        ? `${invoice.therapist.first_name} ${invoice.therapist.last_name}`
        : 'Therapist';

    return (
        <div className="grid grid-cols-1 gap-5">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row">
                        <div>
                            <p className="text-lg font-bold text-primary">
                                {isTherapistBill
                                    ? therapistName
                                    : organization.name}
                            </p>
                            {isTherapistBill && invoice.therapist?.email && (
                                <p className="text-sm text-muted-foreground">
                                    {invoice.therapist.email}
                                </p>
                            )}
                            {!isTherapistBill && organization.address && (
                                <p className="text-sm text-muted-foreground">
                                    {organization.address}
                                </p>
                            )}
                            {!isTherapistBill && organization.phone && (
                                <p className="text-sm text-muted-foreground">
                                    {organization.phone}
                                </p>
                            )}
                            {!isTherapistBill && organization.email && (
                                <p className="text-sm text-muted-foreground">
                                    {organization.email}
                                </p>
                            )}
                        </div>
                        <div className="text-left sm:text-right">
                            <p className="text-xl font-bold">
                                {invoice.invoice_id ?? `Invoice #${invoice.id}`}
                            </p>
                            <InvoiceStatusBadge status={invoice.status} />
                            <p className="mt-2 text-sm text-muted-foreground">
                                Invoice Date: {invoice.invoice_date ?? '-'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Due Date: {invoice.due_date ?? '-'}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="font-bold text-primary">Bill To</p>
                    {isTherapistBill ? (
                        <>
                            <p>{organization.name}</p>
                            {organization.email && (
                                <p className="text-sm text-muted-foreground">
                                    {organization.email}
                                </p>
                            )}
                            {organization.phone && (
                                <p className="text-sm text-muted-foreground">
                                    {organization.phone}
                                </p>
                            )}
                            {childName && (
                                <p className="mt-2 text-sm text-muted-foreground">
                                    For services delivered to {childName}
                                </p>
                            )}
                        </>
                    ) : (
                        <>
                            <p>{invoice.bill_to_name ?? childName ?? '-'}</p>
                            <p className="text-sm text-muted-foreground">
                                {invoice.bill_to_email ??
                                    invoice.client?.original_intake
                                        ?.primary_parent_email}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {invoice.bill_to_phone ??
                                    invoice.client?.original_intake
                                        ?.primary_parent_phone}
                            </p>
                        </>
                    )}
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="overflow-x-auto p-5">
                    <table className="w-full text-sm">
                        <thead className="border-b text-left text-muted-foreground">
                            <tr>
                                <th className="pb-2">Service</th>
                                <th className="pb-2">Period</th>
                                <th className="pb-2 text-right">Sessions</th>
                                <th className="pb-2 text-right">Rate</th>
                                <th className="pb-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoice.services.map((line, index) => (
                                <tr
                                    key={index}
                                    className="border-b last:border-0"
                                >
                                    <td className="py-2">
                                        {line.name}
                                        {line.description && (
                                            <p className="text-xs text-muted-foreground">
                                                {line.description}
                                            </p>
                                        )}
                                    </td>
                                    <td className="py-2">{line.period}</td>
                                    <td className="py-2 text-right">
                                        {line.numberOfSessions}
                                    </td>
                                    <td className="py-2 text-right">
                                        {line.rate}
                                    </td>
                                    <td className="py-2 text-right">
                                        $
                                        {(
                                            line.rate_numeric *
                                            line.numberOfSessions
                                        ).toFixed(2)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="mt-5 ml-auto max-w-xs space-y-1 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Sub Total
                            </span>
                            <span>${Number(invoice.sub_total).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                GST ({Number(invoice.tax_percentage)}%)
                            </span>
                            <span>${Number(invoice.gst).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between border-t pt-1 font-bold">
                            <span>Total</span>
                            <span>${Number(invoice.total).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Amount Due
                            </span>
                            <span>
                                ${Number(invoice.amount_due).toFixed(2)}
                            </span>
                        </div>
                    </div>

                    {invoice.notes && (
                        <div className="mt-5 border-t pt-3">
                            <p className="text-xs text-muted-foreground">
                                Notes
                            </p>
                            <p className="text-sm">{invoice.notes}</p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {(invoice.linked_therapist_invoice ||
                (invoice.linked_admin_invoices &&
                    invoice.linked_admin_invoices.length > 0)) && (
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="font-bold text-primary">Linked Invoice</p>
                        {invoice.linked_therapist_invoice && (
                            <p className="text-sm text-muted-foreground">
                                Therapist invoice:{' '}
                                {invoice.linked_therapist_invoice.invoice_id}
                            </p>
                        )}
                        {invoice.linked_admin_invoices?.map((linked) => (
                            <p
                                key={linked.id}
                                className="text-sm text-muted-foreground"
                            >
                                Admin invoice: {linked.invoice_id}
                            </p>
                        ))}
                    </CardContent>
                </Card>
            )}

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="font-bold text-primary">Activity</p>
                    <div className="mt-3 grid grid-cols-1 gap-2">
                        {(invoice.timeline ?? []).map((entry) => (
                            <div key={entry.id} className="text-sm">
                                <span className="text-muted-foreground">
                                    {entry.date} {entry.time} —{' '}
                                </span>
                                {entry.title}
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
