import { Badge } from '@/components/ui/badge';
import type { ComplaintStatus } from '@/types/complaint';

/** Complaint/dispute status badge, mirrors admin/intake/badges.tsx conventions. */

const STATUS_LABELS: Record<ComplaintStatus, string> = {
    open: 'New',
    under_review: 'Under Review',
    resolved: 'Resolved',
};

const STATUS_CLASSES: Record<ComplaintStatus, string> = {
    open: 'bg-purple-100 text-purple-700 border border-purple-400',
    under_review: 'bg-blue-100 text-blue-700 border border-blue-400',
    resolved: 'bg-green-100 text-green-700 border border-green-400',
};

export function ComplaintStatusBadge({ status }: { status: ComplaintStatus }) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_CLASSES[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}
