import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';

import { TimesheetStatusBadge } from '@/components/timesheets/badges';
import { formatDate } from '@/lib/helpers';
import type { Timesheet } from '@/types/timesheet';

/** The child a form was generated for, as the table shows it. */
function childName(timesheet: Timesheet): string {
    const intake = timesheet.client?.original_intake;

    return intake
        ? `${intake.child_first_name} ${intake.child_last_name}`
        : `Client #${timesheet.client_id}`;
}

interface ClientGroup {
    clientId: number;
    name: string;
    timesheets: Timesheet[];
}

/**
 * The page's forms gathered under the child each was generated for.
 *
 * Insertion order is kept rather than re-sorted: the server already orders
 * the page by child name so a family is never split across two pages, and
 * re-sorting here would only undo that.
 */
function groupByClient(timesheets: Timesheet[]): ClientGroup[] {
    const groups = new Map<number, ClientGroup>();

    for (const timesheet of timesheets) {
        const group = groups.get(timesheet.client_id);

        if (group) {
            group.timesheets.push(timesheet);
            continue;
        }

        groups.set(timesheet.client_id, {
            clientId: timesheet.client_id,
            name: childName(timesheet),
            timesheets: [timesheet],
        });
    }

    return [...groups.values()];
}

function totalHours(timesheets: Timesheet[]): number {
    return timesheets.reduce(
        (sum, timesheet) => sum + Number(timesheet.total_hours),
        0,
    );
}

/** The generated time sheets, gathered by child. */
export default function TimesheetsTable({
    timesheets,
    basePath,
    showAide = false,
    groupedByClient = false,
}: {
    timesheets: Timesheet[];
    basePath: string;
    /** Admins see the whole team's forms and need them told apart. */
    showAide?: boolean;
    /**
     * Gather the rows under a heading per child. Off for a parent, who is
     * only ever shown the one child the portal switcher has selected.
     */
    groupedByClient?: boolean;
}) {
    if (timesheets.length === 0) {
        return (
            <div className="py-8 text-center text-muted-foreground">
                No timesheets found
            </div>
        );
    }

    // Ungrouped, the child is a column of its own; grouped, it is the heading.
    const columnCount = 5 + (showAide ? 1 : 0) + (groupedByClient ? 0 : 1);
    const groups = groupedByClient
        ? groupByClient(timesheets)
        : [{ clientId: 0, name: '', timesheets }];

    return (
        <div className="w-full overflow-x-auto">
            <table className="w-full">
                <thead className="border-b text-left text-sm text-muted-foreground">
                    <tr>
                        <th className="pb-3">Timesheet #</th>
                        {!groupedByClient && (
                            <th className="hidden pb-3 md:table-cell">
                                Client
                            </th>
                        )}
                        {showAide && (
                            <th className="hidden pb-3 md:table-cell">Aide</th>
                        )}
                        <th className="pb-3">Period</th>
                        <th className="pb-3">Total Hours</th>
                        <th className="pb-3">Status</th>
                        <th className="pb-3">Actions</th>
                    </tr>
                </thead>

                {groups.map((group) => (
                    <tbody key={group.clientId}>
                        {groupedByClient && (
                            <tr className="border-b bg-muted/40">
                                <td
                                    colSpan={columnCount}
                                    className="py-2 text-sm font-semibold"
                                >
                                    {group.name}
                                    <span className="ml-2 font-normal text-muted-foreground">
                                        {group.timesheets.length} timesheet
                                        {group.timesheets.length === 1
                                            ? ''
                                            : 's'}{' '}
                                        ·{' '}
                                        {totalHours(group.timesheets).toFixed(
                                            2,
                                        )}{' '}
                                        hrs
                                    </span>
                                </td>
                            </tr>
                        )}

                        {group.timesheets.map((timesheet) => (
                            <tr
                                key={timesheet.id}
                                className="border-b last:border-0"
                            >
                                <td className="py-4">
                                    {timesheet.timesheet_number}
                                </td>
                                {!groupedByClient && (
                                    <td className="hidden py-4 md:table-cell">
                                        {childName(timesheet)}
                                    </td>
                                )}
                                {showAide && (
                                    <td className="hidden py-4 md:table-cell">
                                        {timesheet.therapist
                                            ? `${timesheet.therapist.first_name} ${timesheet.therapist.last_name}`
                                            : '-'}
                                    </td>
                                )}
                                <td className="py-4">
                                    {formatDate(timesheet.period_start)} –{' '}
                                    {formatDate(timesheet.period_end)}
                                </td>
                                <td className="py-4 font-medium">
                                    {Number(timesheet.total_hours).toFixed(2)}
                                </td>
                                <td className="py-4">
                                    <TimesheetStatusBadge
                                        status={timesheet.status}
                                    />
                                </td>
                                <td className="py-4">
                                    <Link
                                        href={`${basePath}/${timesheet.id}`}
                                        className="flex items-center gap-1 text-sm text-primary"
                                    >
                                        <Eye className="h-4 w-4" /> View
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                ))}
            </table>
        </div>
    );
}
