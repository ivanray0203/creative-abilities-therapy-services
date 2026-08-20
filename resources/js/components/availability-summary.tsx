import { days } from '@/lib/content/intake-taxonomy';

interface AvailabilitySummaryProps {
    slots?: Record<string, string[]> | null;
    /** Shown for intakes submitted before the grid existed. */
    fallbackDays?: string[] | null;
    fallbackTimes?: string[] | null;
}

/**
 * Read-only view of an intake's availability.
 *
 * Prefers the day x time grid, which says which times work on which days.
 * Intakes submitted before the grid only have the two independent lists, so
 * those are shown instead rather than implying a precision they never had.
 */
export default function AvailabilitySummary({
    slots,
    fallbackDays,
    fallbackTimes,
}: AvailabilitySummaryProps) {
    const selectedDays = days.filter((day) => (slots?.[day] ?? []).length > 0);

    if (selectedDays.length > 0) {
        return (
            <div className="grid gap-2">
                {selectedDays.map((day) => (
                    <div
                        key={day}
                        className="flex items-center gap-2 overflow-x-auto"
                    >
                        <p className="w-24 shrink-0 text-xs text-muted-foreground">
                            {day}s
                        </p>
                        {(slots?.[day] ?? []).map((time) => (
                            <p
                                key={time}
                                className="shrink-0 rounded-[5px] border px-2 py-0.5 text-xs whitespace-nowrap"
                            >
                                {time}
                            </p>
                        ))}
                    </div>
                ))}
            </div>
        );
    }

    const legacyDays = fallbackDays ?? [];
    const legacyTimes = fallbackTimes ?? [];

    if (legacyDays.length === 0 && legacyTimes.length === 0) {
        return <p className="text-sm text-muted-foreground">Not provided</p>;
    }

    return (
        <div className="grid gap-5">
            <div>
                <p className="text-xs text-muted-foreground">Available Days</p>
                <div className="flex flex-row flex-wrap gap-3">
                    {legacyDays.map((day) => (
                        <p
                            key={day}
                            className="rounded-[5px] border p-1 text-sm"
                        >
                            {day}
                        </p>
                    ))}
                </div>
            </div>
            <div>
                <p className="text-xs text-muted-foreground">Preferred Times</p>
                <div className="flex flex-row flex-wrap gap-3">
                    {legacyTimes.map((time) => (
                        <p
                            key={time}
                            className="rounded-[5px] border p-1 text-sm"
                        >
                            {time}
                        </p>
                    ))}
                </div>
            </div>
        </div>
    );
}
