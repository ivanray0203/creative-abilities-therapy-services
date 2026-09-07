import { Badge } from '@/components/ui/badge';
import type { EmploymentStatus } from '@/types/team-member';

/** Employment status badge, mirrors admin/intake/badges.tsx conventions. */

const STATUS_LABELS: Record<EmploymentStatus, string> = {
    onboarding: 'Onboarding',
    active: 'Active',
    inactive: 'Inactive',
    on_leave: 'On Leave',
    terminated: 'Terminated',
    archived: 'Archived',
};

const STATUS_CLASSES: Record<EmploymentStatus, string> = {
    onboarding: 'bg-cyan-100 text-cyan-700 border border-cyan-400',
    active: 'bg-green-100 text-green-700 border border-green-400',
    inactive: 'bg-gray-100 text-gray-700 border border-gray-400',
    on_leave: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    terminated: 'bg-red-100 text-red-700 border border-red-400',
    archived: 'bg-purple-100 text-purple-700 border border-purple-400',
};

export function EmploymentStatusBadge({
    status,
}: {
    status: EmploymentStatus;
}) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_CLASSES[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}
