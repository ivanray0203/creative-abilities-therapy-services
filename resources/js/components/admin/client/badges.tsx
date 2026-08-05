import { Badge } from '@/components/ui/badge';
import type { ClientStatus } from '@/types/client';

/** Badge rendering for the admin client pipeline, mirrors admin/intake/badges.tsx. */

const STATUS_LABELS: Record<ClientStatus, string> = {
    active: 'Active',
    paused: 'Paused',
    completed: 'Completed',
    inactive: 'Inactive',
    archive: 'Archived',
};

const STATUS_CLASSES: Record<ClientStatus, string> = {
    active: 'bg-green-100 text-green-700 border border-green-400',
    paused: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    completed: 'bg-blue-100 text-blue-700 border border-blue-400',
    inactive: 'bg-gray-100 text-gray-700 border border-gray-400',
    archive: 'bg-red-100 text-red-700 border border-red-400',
};

export function ClientStatusBadge({ status }: { status: ClientStatus }) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_CLASSES[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}

export { FundingBadge } from '@/components/admin/intake/badges';
