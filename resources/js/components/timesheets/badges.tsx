import { Badge } from '@/components/ui/badge';
import type { TimesheetStatus } from '@/types/timesheet';

/**
 * Where a time sheet stands between its two signatures. The aide signs as
 * they generate, so the only question a badge answers is whether the parent
 * has signed yet.
 */
const STATUS_LABELS: Record<TimesheetStatus, string> = {
    awaiting_client: 'Awaiting Parent',
    signed: 'Signed',
};

const STATUS_CLASSES: Record<TimesheetStatus, string> = {
    awaiting_client: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    signed: 'bg-green-100 text-green-700 border border-green-400',
};

export function TimesheetStatusBadge({ status }: { status: TimesheetStatus }) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_CLASSES[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}
