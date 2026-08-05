import { Badge } from '@/components/ui/badge';
import type { ApplicationStatus } from '@/types/application';

/** Application status badge, mirrors admin/intake/badges.tsx conventions. */

const STATUS_LABELS: Record<ApplicationStatus, string> = {
    pending: 'New',
    reviewing: 'Under Review',
    interview_scheduled: 'Interview Scheduled',
    shortlisted: 'Shortlisted',
    hired: 'Hired',
    declined: 'Declined',
};

const STATUS_CLASSES: Record<ApplicationStatus, string> = {
    pending: 'bg-purple-100 text-purple-700 border border-purple-400',
    reviewing: 'bg-blue-100 text-blue-700 border border-blue-400',
    interview_scheduled:
        'bg-yellow-100 text-yellow-800 border border-yellow-400',
    shortlisted: 'bg-teal-100 text-teal-700 border border-teal-400',
    hired: 'bg-green-100 text-green-700 border border-green-400',
    declined: 'bg-red-100 text-red-700 border border-red-400',
};

export function ApplicationStatusBadge({
    status,
}: {
    status: ApplicationStatus;
}) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_CLASSES[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}
