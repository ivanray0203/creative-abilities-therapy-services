import { Fragment } from 'react';

import { Card } from '@/components/ui/card';
import type {
    HourTrackingReport,
    HourTrackingRow,
} from '@/types/hour-tracking';

/** Trims a trailing .00 so 40 hours does not read as "40.00". */
function formatHours(value: number): string {
    return String(Math.round(value * 100) / 100);
}

/** The sheet writes dates as 2026-Jul-15, not 2026-07-15. */
function sheetDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const month = date.toLocaleString('en-US', { month: 'short' });

    return `${date.getFullYear()}-${month}-${String(date.getDate()).padStart(2, '0')}`;
}

/**
 * Phase 21 — the hour-tracking sheet itself: three totals and the table.
 *
 * One section per service, one row per contract, a column per month, and what
 * is left at the end of the row. Shared by the therapist's own page and the
 * admin's clinic-wide one — `showTherapist` is the only difference, because a
 * single therapist's copy would repeat their name down every row.
 *
 * Read-only. Hours move when a session is booked, cancelled or edited.
 */
export default function HourTrackingSheet({
    report,
    showTherapist = false,
}: {
    report: HourTrackingReport;
    showTherapist?: boolean;
}) {
    const { months, sections, totals } = report;

    const spansYears = new Set(months.map((month) => month.year)).size > 1;

    // Section and total rows span the columns before the hours figures.
    const leadColumns = showTherapist ? 5 : 4;

    const cellFor = (row: HourTrackingRow, key: string) => {
        const value = row.months[key];

        // `x` for a month with no session, which is a different fact from a
        // month that drew nothing — the paper sheet says the same.
        return value === null || value === undefined ? (
            <span className="text-muted-foreground/60">x</span>
        ) : (
            formatHours(value)
        );
    };

    return (
        <>
            <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Authorized Hours
                    </p>
                    <p className="text-2xl font-bold">
                        {formatHours(totals.allotted_hours)}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Hours Delivered
                    </p>
                    <p className="text-2xl font-bold">
                        {formatHours(totals.used_hours)}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">
                        Hours Remaining
                    </p>
                    <p
                        className={`text-2xl font-bold ${
                            totals.remaining_hours < 0 ? 'text-destructive' : ''
                        }`}
                    >
                        {formatHours(totals.remaining_hours)}
                    </p>
                </Card>
            </div>

            <Card className="rounded-[10px] p-4">
                {sections.length === 0 ? (
                    <p className="py-10 text-center text-muted-foreground">
                        No contracts fall inside this date range.
                    </p>
                ) : (
                    <>
                        {/*
                         * Outside the scroller: the abbreviations are the
                         * sheet's own and need saying once, but the legend
                         * should not slide away when the months are scrolled.
                         */}
                        <p className="mb-3 text-xs text-muted-foreground">
                            T. Hours is the funding type &middot; N. Hours is
                            the number authorized &middot; R. Hours is what
                            remains
                        </p>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[900px] text-sm">
                                <thead>
                                    <tr className="border-b text-xs text-muted-foreground">
                                        <th className="p-2 text-left font-medium">
                                            Client&apos;s Name
                                        </th>
                                        {showTherapist && (
                                            <th className="p-2 text-left font-medium">
                                                Therapist
                                            </th>
                                        )}
                                        <th className="p-2 text-left font-medium">
                                            Contract Start
                                        </th>
                                        <th className="p-2 text-left font-medium">
                                            Contract End
                                        </th>
                                        <th className="p-2 text-center font-medium">
                                            T. Hours
                                        </th>
                                        <th className="p-2 text-center font-medium">
                                            N. Hours
                                        </th>
                                        {months.map((month) => (
                                            <th
                                                key={month.key}
                                                className="p-2 text-center font-medium"
                                            >
                                                {month.label}
                                                {spansYears && (
                                                    <span className="block font-normal">
                                                        {month.year}
                                                    </span>
                                                )}
                                            </th>
                                        ))}
                                        <th className="p-2 text-center font-medium">
                                            R. Hours
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sections.map((section) => (
                                        <Fragment key={section.service}>
                                            <tr className="bg-muted/60">
                                                <td
                                                    className="p-2 font-bold"
                                                    colSpan={leadColumns}
                                                >
                                                    {section.service}
                                                </td>
                                                <td className="p-2 text-center font-bold">
                                                    {formatHours(
                                                        section.allotted_hours,
                                                    )}
                                                </td>
                                                {months.map((month) => (
                                                    <td key={month.key} />
                                                ))}
                                                <td
                                                    className={`p-2 text-center font-bold ${
                                                        section.remaining_hours <
                                                        0
                                                            ? 'text-destructive'
                                                            : ''
                                                    }`}
                                                >
                                                    {formatHours(
                                                        section.remaining_hours,
                                                    )}
                                                </td>
                                            </tr>

                                            {section.rows.map((row) => (
                                                <tr
                                                    key={row.contract_id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="p-2">
                                                        {row.client_name}
                                                    </td>
                                                    {showTherapist && (
                                                        <td className="p-2">
                                                            {row.therapist_name}
                                                        </td>
                                                    )}
                                                    <td className="p-2 whitespace-nowrap">
                                                        {sheetDate(
                                                            row.period_start,
                                                        )}
                                                    </td>
                                                    <td className="p-2 whitespace-nowrap">
                                                        {sheetDate(
                                                            row.period_end,
                                                        )}
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        {row.funding_code ??
                                                            '-'}
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        {formatHours(
                                                            row.allotted_hours,
                                                        )}
                                                    </td>
                                                    {months.map((month) => (
                                                        <td
                                                            key={month.key}
                                                            className="p-2 text-center"
                                                        >
                                                            {cellFor(
                                                                row,
                                                                month.key,
                                                            )}
                                                        </td>
                                                    ))}
                                                    <td
                                                        className={`p-2 text-center ${
                                                            row.remaining_hours <
                                                            0
                                                                ? 'text-destructive'
                                                                : ''
                                                        }`}
                                                    >
                                                        {formatHours(
                                                            row.remaining_hours,
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </Fragment>
                                    ))}

                                    <tr className="bg-muted/40">
                                        <td
                                            className="p-2 font-bold"
                                            colSpan={leadColumns}
                                        >
                                            All services
                                        </td>
                                        <td className="p-2 text-center font-bold">
                                            {formatHours(totals.allotted_hours)}
                                        </td>
                                        {months.map((month) => (
                                            <td key={month.key} />
                                        ))}
                                        <td
                                            className={`p-2 text-center font-bold ${
                                                totals.remaining_hours < 0
                                                    ? 'text-destructive'
                                                    : ''
                                            }`}
                                        >
                                            {formatHours(
                                                totals.remaining_hours,
                                            )}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </Card>
        </>
    );
}
