import { TimesheetStatusBadge } from '@/components/timesheets/badges';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/helpers';
import type { Timesheet } from '@/types/timesheet';
import { HOUR_COLUMNS, TOTAL_COLUMNS } from '@/types/timesheet';

/**
 * The time sheet as it reads on screen: the same grid the PDF prints, in the
 * same column order, so what the parent signs is what they were shown.
 *
 * Rendered from the form's frozen `rows` rather than the live entries — a
 * correction made after signing must not rewrite the record.
 */
export default function TimesheetPrintable({
    timesheet,
}: {
    timesheet: Timesheet;
}) {
    const rows = timesheet.rows ?? [];
    const childName = timesheet.client?.original_intake
        ? `${timesheet.client.original_intake.child_first_name} ${timesheet.client.original_intake.child_last_name}`
        : `Client #${timesheet.client_id}`;
    const aideName = timesheet.therapist
        ? `${timesheet.therapist.first_name} ${timesheet.therapist.last_name}`
        : 'Aide';

    const columnTotals: Record<string, string> = {
        hourly_respite: timesheet.total_hourly_respite,
        community_support: timesheet.total_community_support,
        bda_direct: timesheet.total_bda_direct,
        bda_indirect: timesheet.total_bda_indirect,
    };

    return (
        <div className="grid grid-cols-1 gap-5">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row">
                        <div>
                            <p className="text-lg font-bold text-primary">
                                {childName}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Aide: {aideName}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {formatDate(timesheet.period_start)} –{' '}
                                {formatDate(timesheet.period_end)}
                            </p>
                        </div>
                        <div className="text-left sm:text-right">
                            <p className="text-xl font-bold">TIME SHEET</p>
                            <p className="text-sm text-muted-foreground">
                                {timesheet.timesheet_number}
                            </p>
                            <div className="mt-2">
                                <TimesheetStatusBadge
                                    status={timesheet.status}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 w-full overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b text-left text-muted-foreground">
                                <tr>
                                    <th className="pb-2">Date</th>
                                    {HOUR_COLUMNS.map((column) => (
                                        <th
                                            key={column.rowKey}
                                            className="pb-2 text-right"
                                        >
                                            {column.label}
                                        </th>
                                    ))}
                                    <th className="pb-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row, index) => (
                                    <tr
                                        key={`${row.date}-${index}`}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-2">
                                            {formatDate(row.date)}
                                        </td>
                                        {HOUR_COLUMNS.map((column) => (
                                            <td
                                                key={column.rowKey}
                                                className="py-2 text-right"
                                            >
                                                {Number(
                                                    row[column.rowKey],
                                                ).toFixed(2)}
                                            </td>
                                        ))}
                                        <td className="py-2 text-right font-medium">
                                            {TOTAL_COLUMNS.reduce(
                                                (sum, column) =>
                                                    sum +
                                                    Number(row[column.rowKey]),
                                                0,
                                            ).toFixed(2)}
                                        </td>
                                    </tr>
                                ))}
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={HOUR_COLUMNS.length + 2}
                                            className="py-6 text-center text-muted-foreground"
                                        >
                                            No hours on this timesheet
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                            <tfoot className="border-t font-bold">
                                <tr>
                                    <td className="py-2">TOTAL HOURS</td>
                                    {HOUR_COLUMNS.map((column) => (
                                        <td
                                            key={column.rowKey}
                                            className="py-2 text-right"
                                        >
                                            {Number(
                                                columnTotals[column.rowKey],
                                            ).toFixed(2)}
                                        </td>
                                    ))}
                                    <td className="py-2 text-right">
                                        {Number(timesheet.total_hours).toFixed(
                                            2,
                                        )}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <SignatureSlot
                            heading="AIDE'S SIGNATURE"
                            name={aideName}
                            signature={timesheet.aide_signature}
                            signedAt={timesheet.aide_signed_at}
                        />
                        <SignatureSlot
                            heading="PARENT'S SIGNATURE"
                            name={
                                timesheet.client?.original_intake
                                    ?.primary_parent_name ?? 'Parent / Guardian'
                            }
                            signature={timesheet.parent_signature}
                            signedAt={timesheet.parent_signed_at}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function SignatureSlot({
    heading,
    name,
    signature,
    signedAt,
}: {
    heading: string;
    name: string;
    signature: string | null;
    signedAt: string | null;
}) {
    return (
        <div>
            <p className="text-sm font-bold">{heading}</p>
            <div className="mt-2 flex h-24 items-center justify-center rounded-[10px] border border-dashed bg-muted/30">
                {signature ? (
                    <img
                        src={signature}
                        alt={`${name}'s signature`}
                        className="max-h-20"
                    />
                ) : (
                    <span className="text-xs text-muted-foreground">
                        Awaiting signature
                    </span>
                )}
            </div>
            <p className="mt-2 border-t pt-2 text-sm">{name}</p>
            {signedAt && (
                <p className="text-xs text-muted-foreground">
                    Signed {formatDate(signedAt)}
                </p>
            )}
        </div>
    );
}
