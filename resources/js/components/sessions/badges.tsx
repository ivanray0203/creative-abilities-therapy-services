import { Badge } from '@/components/ui/badge';
import { sessionServiceNames } from '@/lib/sessions';
import type { ScheduleSession, ScheduleSessionStatus } from '@/types/session';

/** Session status badge, mirrors admin/intake/badges.tsx conventions. */

const STATUS_LABELS: Record<ScheduleSessionStatus, string> = {
    scheduled: 'Scheduled',
    completed: 'Completed',
    cancelled: 'Cancelled',
    inprogress: 'In Progress',
    confirmed: 'Confirmed',
    no_show: 'No Show',
    pending: 'Pending',
    disputed: 'Disputed',
};

const STATUS_COLORS: Record<ScheduleSessionStatus, string> = {
    scheduled: 'bg-blue-100 text-blue-700 border border-blue-400',
    completed: 'bg-green-100 text-green-700 border border-green-400',
    cancelled: 'bg-gray-100 text-gray-700 border border-gray-400',
    inprogress: 'bg-purple-100 text-purple-700 border border-purple-400',
    confirmed: 'bg-teal-100 text-teal-700 border border-teal-400',
    no_show: 'bg-orange-100 text-orange-700 border border-orange-400',
    pending: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    disputed: 'bg-red-100 text-red-700 border border-red-400',
};

export function SessionStatusBadge({
    status,
}: {
    status: ScheduleSessionStatus;
}) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_COLORS[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}

/**
 * One tag per availed service the visit covers — a session can deliver
 * several at once, so a single line of text hid how much a booking included.
 * Renders nothing when the session has no service recorded at all.
 */
export function SessionServiceTags({
    session,
    className = '',
}: {
    session: Pick<
        ScheduleSession,
        'client_services' | 'service' | 'service_name'
    >;
    className?: string;
}) {
    const names = sessionServiceNames(session);

    if (names.length === 0) {
        return null;
    }

    return (
        <div className={`flex flex-wrap gap-1 ${className}`}>
            {names.map((name) => (
                <Badge
                    key={name}
                    variant="secondary"
                    className="rounded-[5px] border border-border bg-muted font-medium text-muted-foreground"
                >
                    {name}
                </Badge>
            ))}
        </div>
    );
}

export {
    STATUS_COLORS as SESSION_STATUS_COLORS,
    STATUS_LABELS as SESSION_STATUS_LABELS,
};
