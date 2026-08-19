import { UserCheck } from 'lucide-react';

import { INTAKE_STATUS_CLASSES } from '@/components/intake/status-badge';
import { Badge } from '@/components/ui/badge';
import { fundingSourceLabel } from '@/lib/content/intake-taxonomy';
import type { Intake, IntakeStatusVariant } from '@/types/intake';

/**
 * Badge rendering for the admin intake pipeline, ported from
 * cats-frontend/src/lib/helpers.tsx (`getStatusBadge` / `getFundingBadge`).
 * The status/therapist-review merge that the reference performed client-side
 * is resolved server-side, so this only maps the resolved variant to classes.
 *
 * The five plain intake statuses come from the shared map so admin, therapist
 * and client views can't drift; only the two therapist-review variants are
 * specific to this pipeline.
 */

const STATUS_VARIANT_CLASSES: Record<IntakeStatusVariant, string> = {
    ...INTAKE_STATUS_CLASSES,
    therapist_pending: 'bg-cyan-50 text-cyan-800 border border-cyan-600',
    therapist_rejected: 'bg-red-100 text-red-700 border border-red-600',
};

export function IntakeStatusBadge({ intake }: { intake: Intake }) {
    const isTherapistBadge =
        intake.status_variant === 'therapist_pending' ||
        intake.status_variant === 'therapist_rejected';

    return (
        <Badge
            className={`max-w-[200px] gap-1 rounded-[5px] text-xs break-words md:text-sm ${STATUS_VARIANT_CLASSES[intake.status_variant]}`}
        >
            {isTherapistBadge && <UserCheck className="h-3 w-3" />}
            <span className="sm:hidden">{intake.status_short_label}</span>
            <span className="hidden sm:inline">{intake.status_label}</span>
        </Badge>
    );
}

/**
 * Colour per funding source. The wording comes from the shared label map, so
 * a badge always spells the programme out in full rather than abbreviating it.
 */
const FUNDING_BADGE_CLASSES: Record<string, string> = {
    'BDS-FSCD': 'bg-green-100 text-green-700 border border-green-400',
    'Counselling-FSCD': 'bg-blue-100 text-blue-700 border border-blue-400',
    'SS-FSCD': 'bg-purple-100 text-purple-700 border border-purple-400',
    Insurance: 'bg-red-100 text-red-700 border border-red-400',
};

const PRIVATE_PAY_CLASSES =
    'bg-yellow-100 text-yellow-800 border border-yellow-400';

export function FundingBadge({
    fundingSource,
}: {
    fundingSource: string | null;
}) {
    const className =
        (fundingSource && FUNDING_BADGE_CLASSES[fundingSource]) ||
        PRIVATE_PAY_CLASSES;

    return (
        <Badge
            className={`max-w-[280px] rounded-[5px] text-left break-words whitespace-normal ${className}`}
        >
            {/* A missing source has always read as private pay here. */}
            {fundingSourceLabel(fundingSource ?? 'private')}
        </Badge>
    );
}
