import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Lock, UserPlus } from 'lucide-react';

import IntakeApplicationForm from '@/components/forms/intake-application-form';
import { Button } from '@/components/ui/button';
import ClientLayout from '@/layouts/client-layout';
import type { ConsentDocument } from '@/types/consent';

interface ClientIntakePageProps {
    requiredConsents: ConsentDocument[];
    prefill: Record<string, string | null>;
}

/**
 * Household details that belong to the parent's account rather than to any
 * one child, so they're shown read-only and carried over from the record
 * already on file.
 */
const ACCOUNT_LEVEL_FIELDS = [
    'primary_parent_name',
    'primary_parent_phone',
    'primary_parent_email',
    'primary_parent_email_confirm',
    'primary_relationship_to_child',
    'primary_contact_method',
    'emergency_contact_name',
    'emergency_contact_phone',
    'emergency_contact_relationship',
] as const;

/**
 * Lets a signed-in parent register another child (Phase 17). Reuses the
 * public intake form, pointed at the authenticated endpoint and pre-filled
 * with the parent/address details already on file so only the new child's
 * information needs entering.
 */
export default function ClientIntake({
    requiredConsents,
    prefill,
}: ClientIntakePageProps) {
    // Drop nulls so absent values fall back to the form's own defaults
    // rather than overwriting them with null.
    const initialValues = Object.fromEntries(
        Object.entries(prefill).filter(([, value]) => value !== null),
    );

    // Lock only what actually arrived pre-filled. These fields are all
    // required, so locking an empty one — a parent with no phone on file, say
    // — would leave the form impossible to submit.
    const lockedFields = ACCOUNT_LEVEL_FIELDS.filter((field) =>
        Boolean(initialValues[field]),
    );

    return (
        <>
            <Head title="Register Another Child" />

            <div className="space-y-6 p-6">
                <Button
                    asChild
                    variant="ghost"
                    className="-ml-2 h-auto gap-2 p-2 text-muted-foreground"
                >
                    <Link href="/client/intake">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Intakes
                    </Link>
                </Button>

                <div>
                    <div className="flex items-center gap-2">
                        <UserPlus className="h-6 w-6 text-primary" />
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Register Another Child
                        </h1>
                    </div>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Your contact details are filled in from your account —
                        you only need to complete the new child's information.
                    </p>
                </div>

                <div className="flex items-center gap-2 rounded-sm bg-background p-3 shadow-sm">
                    <Lock className="h-4 w-4 shrink-0 text-primary" />
                    <span className="text-xs text-muted-foreground sm:text-sm">
                        All information is kept strictly confidential
                    </span>
                </div>

                <IntakeApplicationForm
                    requiredConsents={requiredConsents}
                    submitUrl="/client/intake"
                    prefill={initialValues}
                    draftKey="CatsClientIntakeDraft"
                    lockedFields={lockedFields}
                />
            </div>
        </>
    );
}

ClientIntake.layout = (page: React.ReactElement) => (
    <ClientLayout>{page}</ClientLayout>
);
