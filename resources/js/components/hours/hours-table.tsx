import { router } from '@inertiajs/react';
import { Trash } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/helpers';
import type { TimesheetEntry } from '@/types/timesheet';
import { HOUR_COLUMNS } from '@/types/timesheet';

/** The child a day was logged against, as the table shows it. */
function childName(entry: TimesheetEntry): string {
    const intake = entry.client?.original_intake;

    return intake
        ? `${intake.child_first_name} ${intake.child_last_name}`
        : `Client #${entry.client_id}`;
}

function dayTotal(entry: TimesheetEntry): number {
    return HOUR_COLUMNS.reduce(
        (sum, column) => sum + Number(entry[column.entryKey]),
        0,
    );
}

/**
 * The hours ledger: one row per child per day. A row is "Not sheeted" until
 * a generated timesheet claims it, after which it shows the form it went out
 * on and can no longer be removed.
 */
export default function HoursTable({ entries }: { entries: TimesheetEntry[] }) {
    if (entries.length === 0) {
        return (
            <div className="py-8 text-center text-muted-foreground">
                No hours logged yet
            </div>
        );
    }

    const remove = (entry: TimesheetEntry) => {
        if (
            !confirm(
                `Remove the hours logged for ${formatDate(entry.entry_date)}?`,
            )
        ) {
            return;
        }

        router.delete(`/therapist/hours/${entry.id}`, { preserveScroll: true });
    };

    return (
        <div className="w-full overflow-x-auto">
            <table className="w-full">
                <thead className="border-b text-left text-sm text-muted-foreground">
                    <tr>
                        <th className="pb-3">Date</th>
                        <th className="pb-3">Client</th>
                        {HOUR_COLUMNS.map((column) => (
                            <th
                                key={column.rowKey}
                                className="hidden pb-3 md:table-cell"
                            >
                                {column.label}
                            </th>
                        ))}
                        <th className="pb-3">Total</th>
                        <th className="pb-3">Status</th>
                        <th className="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {entries.map((entry) => (
                        <tr key={entry.id} className="border-b last:border-0">
                            <td className="py-4">
                                {formatDate(entry.entry_date)}
                            </td>
                            <td className="py-4">{childName(entry)}</td>
                            {HOUR_COLUMNS.map((column) => (
                                <td
                                    key={column.rowKey}
                                    className="hidden py-4 md:table-cell"
                                >
                                    {Number(entry[column.entryKey]).toFixed(2)}
                                </td>
                            ))}
                            <td className="py-4 font-medium">
                                {dayTotal(entry).toFixed(2)}
                            </td>
                            <td className="py-4">
                                {entry.timesheet_id ? (
                                    <Badge variant="secondary">
                                        {entry.timesheet?.timesheet_number ??
                                            'On a timesheet'}
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">Not sheeted</Badge>
                                )}
                            </td>
                            <td className="py-4">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    className="rounded-[10px] text-red-700 hover:bg-red-600 hover:text-white"
                                    disabled={entry.timesheet_id !== null}
                                    onClick={() => remove(entry)}
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
