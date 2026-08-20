import { Card, CardContent } from '@/components/ui/card';
import type { Invoice } from '@/types/invoice';

/** "2026-Jun-01", the date format the clinic's monthly invoice sheet uses. */
function formatSheetDate(date: string | null | undefined): string {
    if (!date) {
        return '-';
    }

    const parsed = new Date(`${date}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    const month = parsed.toLocaleString('en-CA', { month: 'short' });

    return `${parsed.getFullYear()}-${month}-${String(parsed.getDate()).padStart(2, '0')}`;
}

const money = (value: number): string =>
    value.toLocaleString('en-CA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

/**
 * The therapist's month-end statement to the clinic, in the format of the
 * clinic's own invoice sheet: who it bills, the invoice box, then one row per
 * piece of work — date, client, service, rate, quantity, amount.
 *
 * Distinct from InvoicePrintable, which addresses a single client's invoice;
 * this one spans every client the therapist worked with that month.
 */
export default function MonthlyInvoicePrintable({
    invoice,
}: {
    invoice: Invoice;
}) {
    const therapistName = invoice.therapist
        ? `${invoice.therapist.first_name} ${invoice.therapist.last_name}`
        : 'Therapist';

    const billToLines = (invoice.bill_to_address ?? '')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);

    return (
        <div className="grid grid-cols-1 gap-5">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <div className="flex flex-col justify-between gap-6 sm:flex-row">
                        <div>
                            <p className="font-bold">BILL TO:</p>
                            <p>{invoice.bill_to_name ?? '-'}</p>
                            {billToLines.map((line) => (
                                <p
                                    key={line}
                                    className="text-sm text-muted-foreground"
                                >
                                    {line}
                                </p>
                            ))}
                            <p className="mt-3 text-sm text-muted-foreground">
                                From {therapistName}
                                {invoice.therapist?.email
                                    ? ` · ${invoice.therapist.email}`
                                    : ''}
                            </p>
                        </div>

                        <div className="sm:shrink-0">
                            <table className="text-sm">
                                <tbody>
                                    <tr className="border">
                                        <th className="border-r px-3 py-1.5 text-left font-bold">
                                            Invoice #:
                                        </th>
                                        <td className="px-3 py-1.5 text-center font-bold">
                                            {invoice.invoice_id ??
                                                `#${invoice.id}`}
                                        </td>
                                    </tr>
                                    <tr className="border">
                                        <th className="border-r px-3 py-1.5 text-left font-bold">
                                            Date:
                                        </th>
                                        <td className="px-3 py-1.5 text-center">
                                            {formatSheetDate(
                                                invoice.invoice_date,
                                            )}
                                        </td>
                                    </tr>
                                    <tr className="border">
                                        <th className="border-r px-3 py-1.5 text-left font-bold">
                                            Amount Due:
                                        </th>
                                        <td className="bg-secondary-orange/20 px-3 py-1.5 text-center font-bold">
                                            CA$
                                            {money(Number(invoice.amount_due))}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="overflow-x-auto p-5">
                    <table className="w-full border text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50 text-center font-bold">
                                <th className="border-r px-3 py-2">Date</th>
                                <th className="border-r px-3 py-2">Client</th>
                                <th className="border-r px-3 py-2">
                                    Service Provided
                                </th>
                                <th className="border-r px-3 py-2">Rate</th>
                                <th className="border-r px-3 py-2">Quantity</th>
                                <th className="px-3 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoice.services.map((line, index) => (
                                <tr key={index} className="border-b">
                                    <td className="border-r px-3 py-2 whitespace-nowrap">
                                        {formatSheetDate(line.date)}
                                    </td>
                                    <td className="border-r px-3 py-2">
                                        {line.client ?? '-'}
                                    </td>
                                    <td className="border-r px-3 py-2">
                                        {line.name}
                                    </td>
                                    <td className="border-r px-3 py-2 text-right">
                                        {money(line.rate_numeric)}
                                    </td>
                                    <td className="border-r px-3 py-2 text-right">
                                        {money(line.numberOfSessions)}
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        {money(
                                            line.rate_numeric *
                                                line.numberOfSessions,
                                        )}
                                    </td>
                                </tr>
                            ))}

                            {invoice.services.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-4 text-center text-muted-foreground"
                                    >
                                        No work billed for this month.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="font-bold">
                                <td
                                    colSpan={5}
                                    className="border-r px-3 py-2 text-right"
                                >
                                    Total
                                </td>
                                <td className="px-3 py-2 text-right">
                                    CA${money(Number(invoice.total))}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </CardContent>
            </Card>
        </div>
    );
}
