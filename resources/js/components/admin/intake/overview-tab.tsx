import {
    Calendar,
    Check,
    CheckCircle,
    DollarSign,
    Heart,
    LucideBriefcaseMedical,
    User,
    Users2Icon,
} from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import { capitalize } from '@/lib/helpers';
import type { Intake } from '@/types/intake';

const FSCD_CONSENTS = [
    {
        title: 'FSCD Worker Communication',
        description:
            'Consent to communicate with FSCD worker regarding services, progress, and billing',
    },
    {
        title: 'Reports Sharing',
        description:
            'Consent to sharing reports and session notes with the FSCD program as required',
    },
    {
        title: 'Non-Approved Costs Acknowledgment',
        description:
            'Understanding of responsibility for any costs not approved by FSCD (e.g., top-ups, cancellations)',
    },
];

/** Reference: cats-frontend/src/pages/admin/intake/Overview.tsx */
export default function OverviewTab({
    intake,
}: {
    intake: Intake;
    isPreview?: boolean;
}) {
    const funding = intake.funding_source_info ?? {};
    const isInsurance = intake.funding_source === 'Insurance';
    const isPrivate = intake.funding_source === 'private';
    const isFscd = !isInsurance && !isPrivate;

    return (
        <>
            <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <User className="text-primary" /> Child Information
                        </p>

                        <div className="mt-10 grid grid-cols-2 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Full Name
                                </p>
                                <p>
                                    {intake.child_first_name}{' '}
                                    {intake.child_last_name}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Gender
                                </p>
                                <p>{capitalize(intake.gender)}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Date of Birth
                                </p>
                                <p>{intake.date_of_birth}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Age
                                </p>
                                <p>{intake.age} years old</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Address
                                </p>
                                <p>
                                    {intake.street_address}{' '}
                                    {intake.address_line_2} {intake.city}{' '}
                                    {intake.state_province} {intake.postal_code}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <User className="text-primary" /> Educational
                            Background
                        </p>

                        <div className="mt-10 grid gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Grade Level
                                </p>
                                <p>{intake.grade_level || '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    School Name
                                </p>
                                <p>{intake.school_name || '-'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <LucideBriefcaseMedical className="text-primary" />{' '}
                            Medical &amp; Developmental History
                        </p>

                        <div className="mt-10 border-b pb-5">
                            <p className="text-xs text-muted-foreground">
                                Diagnosis
                            </p>
                            <div className="mt-3 flex flex-row flex-wrap gap-3">
                                {(intake.diagnosis ?? []).map((diagnosis) => (
                                    <p
                                        key={diagnosis}
                                        className="rounded-[5px] bg-gray-500 p-1 text-sm text-white"
                                    >
                                        {diagnosis}
                                    </p>
                                ))}
                            </div>
                        </div>

                        <div className="mt-10 grid grid-cols-2 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Medical Conditions
                                </p>
                                <p>{intake.medical_conditions || '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Languages Spoken at Home
                                </p>
                                <p>{intake.languages_spoken_at_home || '-'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Heart className="text-primary" /> Service Needed
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-5 px-5">
                            {(intake.services_needed ?? []).map((service) => (
                                <div
                                    key={service}
                                    className="flex flex-row gap-3 rounded-[5px] bg-charcoal-gray/10 p-1"
                                >
                                    <Check className="text-primary" />
                                    <p>{service}</p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Calendar className="text-primary" /> Availability
                        </p>

                        <div className="mt-10 grid gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Available Days
                                </p>
                                <div className="flex flex-row flex-wrap gap-3">
                                    {(intake.available_days ?? []).map(
                                        (day) => (
                                            <p
                                                key={day}
                                                className="rounded-[5px] border p-1 text-sm"
                                            >
                                                {day}
                                            </p>
                                        ),
                                    )}
                                </div>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Preferred Times
                                </p>
                                <div className="flex flex-row flex-wrap gap-3">
                                    {(intake.preferred_times ?? []).map(
                                        (time) => (
                                            <p
                                                key={time}
                                                className="rounded-[5px] border p-1 text-sm"
                                            >
                                                {time}
                                            </p>
                                        ),
                                    )}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <div>
                            <p className="flex flex-row items-center gap-3">
                                <DollarSign className="text-primary" /> Funding
                                Information
                            </p>
                            <p className="text-muted-foreground">
                                Complete funding details for this intake
                                application
                            </p>
                        </div>

                        <p className="mt-10 font-bold text-primary">
                            Basic Information
                        </p>
                        <div className="grid grid-cols-2 gap-5 border-b pb-3 md:grid-cols-4">
                            {isFscd && (
                                <>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Funding source
                                        </p>
                                        <p>{intake.funding_source || '-'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            FSCD Worker Name
                                        </p>
                                        <p>
                                            {funding.FSCD_case_worker_name ||
                                                '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            FSCD Worker Email
                                        </p>
                                        <p className="max-w-full break-words">
                                            {funding.FSCD_case_worker_email ||
                                                '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Contract Start Date
                                        </p>
                                        <p>
                                            {funding.FSCD_approval_start_date ||
                                                '-'}
                                        </p>
                                    </div>
                                </>
                            )}

                            {isInsurance && (
                                <>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Funding source
                                        </p>
                                        <p>{intake.funding_source || '-'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Insurance Provider
                                        </p>
                                        <p>
                                            {funding.insurance_provider || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Policy / Group Number
                                        </p>
                                        <p>{funding.policy_number || '-'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Certificate / ID Number
                                        </p>
                                        <p>
                                            {funding.certificate_number || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Policy Holder Name
                                        </p>
                                        <p>
                                            {funding.policy_holder_name || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Policy Holder Date Of Birth
                                        </p>
                                        <p>
                                            {funding.policy_holder_date_of_birth ||
                                                '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Pre-Authorization
                                        </p>
                                        <p>
                                            {funding.pre_authorization_obtained ||
                                                '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Used Annual Maximum
                                        </p>
                                        <p>
                                            {funding.used_annual_maximum || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Authorization Start Date
                                        </p>
                                        <p>
                                            {funding.authorization_start_date ||
                                                '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Authorization End Date
                                        </p>
                                        <p>
                                            {funding.authorization_end_date ||
                                                '-'}
                                        </p>
                                    </div>
                                </>
                            )}

                            {isPrivate && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Funding source
                                    </p>
                                    <p>Private Pay</p>
                                </div>
                            )}
                        </div>

                        {isFscd && (
                            <div className="mt-5">
                                <p className="flex flex-row gap-3 text-primary">
                                    <CheckCircle className="text-primary" />{' '}
                                    Required FSCD Consents
                                </p>

                                <div className="mt-5 rounded-sm border border-secondary-orange/30 bg-secondary/5 p-5">
                                    {FSCD_CONSENTS.map((consent) => (
                                        <div
                                            key={consent.title}
                                            className="mt-2 flex flex-row items-center gap-3 first:mt-0"
                                        >
                                            <CheckCircle className="text-charcoal-gray/30" />
                                            <div>
                                                <p>{consent.title}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {consent.description}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Users2Icon className="text-primary" /> Other
                            Programs &amp; Agencies
                        </p>
                        <div className="mt-10">
                            <p className="text-sm">
                                {intake.receiving_services_desc ||
                                    'Not currently receiving services from other programs'}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <LucideBriefcaseMedical className="text-primary" />{' '}
                            Goals &amp; Additional Information
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            <div className="border-b pb-4">
                                <p className="text-xs text-muted-foreground">
                                    Therapy Goals
                                </p>
                                <p>{intake.theraphy_goals || '-'}</p>
                            </div>
                            <div className="border-b pb-4">
                                <p className="text-xs text-muted-foreground">
                                    Additional Information
                                </p>
                                <p>{intake.additional_information || '-'}</p>
                            </div>
                            <div className="border-b pb-4">
                                <p className="text-xs text-muted-foreground">
                                    Referral Source
                                </p>
                                <p>{intake.referral_source || '-'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
