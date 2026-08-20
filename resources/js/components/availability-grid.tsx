import { days, times } from '@/lib/content/intake-taxonomy';
import type { AvailabilitySlots } from '@/lib/content/intake-taxonomy';

interface AvailabilityGridProps {
    slots: AvailabilitySlots;
    onToggle: (day: string, time: string) => void;
    /** Namespaces the checkbox ids when two grids share a page. */
    idPrefix?: string;
}

/** Column headers read as plurals ("Mondays"); the stored value stays singular. */
const dayHeading = (day: string) => `${day}s`;

/**
 * Availability as a time-of-day x day-of-week grid, so an applicant can say
 * which times work on which days rather than two independent lists.
 *
 * Rows are times and columns are days, matching the intake form's layout. The
 * grid scrolls horizontally on narrow screens instead of forcing the page to.
 */
export default function AvailabilityGrid({
    slots,
    onToggle,
    idPrefix = 'availability',
}: AvailabilityGridProps) {
    /** e.g. "availability-wednesday-evenings-4pm-7pm". */
    const cellId = (day: string, time: string) =>
        `${idPrefix}-${day}-${time}`
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');

    return (
        <div id={idPrefix + '-grid'} className="mt-3 overflow-x-auto">
            <table className="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr>
                        <th className="border-b p-2 text-left font-medium" />
                        {days.map((day) => (
                            <th
                                key={day}
                                scope="col"
                                className="border-b p-2 text-center font-medium text-charcoal-gray"
                            >
                                {dayHeading(day)}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {times.map((time) => (
                        <tr key={time}>
                            <th
                                scope="row"
                                className="border-b p-2 text-left font-normal whitespace-nowrap"
                            >
                                {time}
                            </th>
                            {days.map((day) => (
                                <td
                                    key={day}
                                    className="border-b p-2 text-center"
                                >
                                    <input
                                        type="checkbox"
                                        id={cellId(day, time)}
                                        aria-label={`${time} on ${dayHeading(day)}`}
                                        checked={(slots[day] ?? []).includes(
                                            time,
                                        )}
                                        onChange={() => onToggle(day, time)}
                                        className="h-5 w-5 accent-primary"
                                    />
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
