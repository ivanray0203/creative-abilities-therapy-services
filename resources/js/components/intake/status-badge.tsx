import { Badge } from '@/components/ui/badge';
import type { IntakeStatus } from '@/types/intake';

/**
 * Single source of truth for intake status colours, shared by the admin
 * pipeline, the therapist review queue, and the client's own intake list.
 *
 * The admin pipeline additionally renders therapist-review variants — see
 * components/admin/intake/badges.tsx, which composes these classes rather
 * than restating them.
 */
export const INTAKE_STATUS_CLASSES: Record<IntakeStatus, string> = {
    pending: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    under_review: 'bg-blue-100 text-blue-700 border border-blue-400',
    waitlist: 'bg-purple-100 text-purple-700 border border-purple-400',
    approved: 'bg-green-100 text-green-700 border border-green-400',
    denied: 'bg-red-100 text-red-700 border border-red-400',
};

/**
 * Labels lean on plain language, since clients read these too — "Not
 * Accepted" rather than "Denied".
 */
export const INTAKE_STATUS_LABELS: Record<IntakeStatus, string> = {
    pending: 'Pending',
    under_review: 'Under Review',
    waitlist: 'Waitlisted',
    approved: 'Approved',
    denied: 'Not Accepted',
};

export function IntakeStatusBadge({ status }: { status: IntakeStatus }) {
    return (
        <Badge className={`rounded-[5px] ${INTAKE_STATUS_CLASSES[status]}`}>
            {INTAKE_STATUS_LABELS[status]}
        </Badge>
    );
}
