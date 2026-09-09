import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    Calendar,
    Check,
    Heart,
    LineChart,
    LucideBriefcaseMedical,
    Mail,
    Phone,
    User,
    X,
} from 'lucide-react';

import DocumentsTab from '@/components/admin/intake/documents-tab';
import IntakeDecisionCard from '@/components/therapist/intake-decision-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TherapistLayout from '@/layouts/therapist-layout';
import { capitalize, formatDate } from '@/lib/helpers';
import type { Intake } from '@/types/intake';

interface ScheduleMatchDetail {
    day: string;
    preferred: string;
    therapist_from: string;
    therapist_to: string;
}

interface ScheduleMatch {
    match: boolean;
    matched_days: string[];
    matched_times: string[];
    details: ScheduleMatchDetail[];
    matchLevel: 'none' | 'partial' | 'full';
}

interface PendingReview {
    id: number;
    service: string | null;
}

interface TherapistIntakeShowProps {
    intake: Intake;
    scheduleMatch: ScheduleMatch;
    capacity: { current: number; maximum: number };
    myServices: string[];
    specializationMatches: Record<string, boolean>;
    pendingReviews: PendingReview[];
}

const MATCH_STYLES: Record<
    ScheduleMatch['matchLevel'],
    { card: string; icon: string; text: string; label: string }
> = {
    full: {
        card: 'bg-green-50 border-green-200',
        icon: 'bg-green-200 text-green-700',
        text: 'text-green-800',
        label: 'Fully Compatible',
    },
    partial: {
        card: 'bg-orange-50 border-orange-200',
        icon: 'bg-orange-200 text-orange-700',
        text: 'text-orange-800',
        label: 'Partially Compatible',
    },
    none: {
        card: 'bg-red-50 border-red-200',
        icon: 'bg-red-200 text-red-700',
        text: 'text-red-800',
        label: 'Not Compatible',
    },
};

export default function TherapistIntakeShow({
    intake,
    scheduleMatch,
    capacity,
    myServices,
    specializationMatches,
    pendingReviews,
}: TherapistIntakeShowProps) {
    const remaining = capacity.maximum - capacity.current;
    const matchStyle = MATCH_STYLES[scheduleMatch.matchLevel];

    let capacityColor = 'bg-green-50 border-green-100';
    let capacityIcon = 'bg-green-200 text-green-700';

    if (remaining <= 0) {
        capacityColor = 'bg-red-50 border-red-200';
        capacityIcon = 'bg-red-200 text-red-700';
    } else if (remaining <= 3) {
        capacityColor = 'bg-orange-50 border-orange-200';
        capacityIcon = 'bg-orange-200 text-orange-700';
    }

    return (
        <>
            <Head
                title={`${intake.child_first_name} ${intake.child_last_name}`}
            />

            <div className="space-y-4 p-2 md:p-6">
                <div className="flex items-center justify-between">
                    <Link
                        href="/therapist"
                        className="hidden items-center gap-2 text-sm text-muted-foreground hover:text-foreground md:flex"
                    >
                        <ArrowLeftIcon className="h-4 w-4" /> Back to Dashboard
                    </Link>
                    {pendingReviews.length > 0 && (
                        <Badge className="rounded-[5px] bg-yellow-100 text-yellow-800">
                            Pending your Acceptance
                        </Badge>
                    )}
                </div>

                <div>
                    <p className="text-2xl font-semibold">
                        {intake.child_first_name} {intake.child_last_name}
                    </p>
                    <div className="mt-1 flex flex-col gap-2 text-sm text-muted-foreground md:flex-row md:items-center">
                        <span>
                            Intake ID: INT-
                            {intake.id.toString().padStart(3, '0')}
                        </span>
                        <span>Submitted: {formatDate(intake.created_at)}</span>
                        <span>Age: {intake.age}</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card className={`border p-4 sm:p-6 ${capacityColor}`}>
                        <div className="flex items-center gap-3">
                            <div className={`rounded-full p-3 ${capacityIcon}`}>
                                <LineChart className="h-5 w-5 sm:h-6 sm:w-6" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground sm:text-sm">
                                    Your Capacity
                                </p>
                                <p className="text-sm sm:text-base">
                                    {capacity.current}/{capacity.maximum}{' '}
                                    clients
                                </p>
                                <p className="mt-1 text-xs">
                                    {remaining} slots available
                                </p>
                            </div>
                        </div>
                    </Card>

                    <Card className={`border p-4 sm:p-6 ${matchStyle.card}`}>
                        <div className="flex items-center gap-3">
                            <div
                                className={`rounded-full p-3 ${matchStyle.icon}`}
                            >
                                <Calendar className="h-5 w-5 sm:h-6 sm:w-6" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground sm:text-sm">
                                    Schedule Match
                                </p>
                                <p
                                    className={`text-sm font-semibold sm:text-base ${matchStyle.text}`}
                                >
                                    {matchStyle.label}
                                </p>
                                <p
                                    className={`mt-1 text-xs ${matchStyle.text}`}
                                >
                                    {scheduleMatch.matchLevel === 'none' &&
                                        'No alignment found'}
                                    {scheduleMatch.matchLevel !== 'none' &&
                                        scheduleMatch.matched_days.length > 0 &&
                                        `Aligned: ${scheduleMatch.matched_days.join(', ')}`}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <Card className="border-green-600 bg-green-50 p-4 sm:p-6">
                        <div className="flex items-center gap-3">
                            <div className="rounded-full bg-cyan-800 p-3">
                                <Heart className="h-5 w-5 text-white sm:h-6 sm:w-6" />
                            </div>
                            <div>
                                <p className="text-xs text-charcoal-gray sm:text-sm">
                                    Service Requested
                                </p>
                                <p className="text-sm sm:text-base">
                                    {myServices.length} service(s)
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                <Card className="border-green-600 bg-green-50">
                    <CardContent className="p-6">
                        <div className="flex items-center gap-3">
                            <Heart className="h-6 w-6 text-cyan-800" />
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Your Assigned Services vs Your Specialties:
                                </p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {myServices.map((service) => {
                                        const hasSpecialization =
                                            specializationMatches[service];

                                        return (
                                            <Badge
                                                key={service}
                                                className={`flex items-center gap-1 rounded-[5px] border ${
                                                    hasSpecialization
                                                        ? 'border-green-700 bg-green-100 text-green-700'
                                                        : 'border-gray-400 bg-gray-200 text-gray-600'
                                                }`}
                                            >
                                                {hasSpecialization ? (
                                                    <Check className="h-3 w-3" />
                                                ) : (
                                                    <X className="h-3 w-3" />
                                                )}
                                                {service}
                                            </Badge>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList className="flex w-full">
                        <TabsTrigger value="overview" className="flex-1">
                            Overview
                        </TabsTrigger>
                        <TabsTrigger value="schedule" className="flex-1">
                            Scheduling Fit
                        </TabsTrigger>
                        <TabsTrigger value="documents" className="flex-1">
                            Documents ({intake.documents?.length ?? 0})
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <div className="grid gap-2 md:grid-cols-2 md:p-5">
                            <Card className="rounded-[10px]">
                                <CardContent className="p-5">
                                    <p className="flex items-center gap-3">
                                        <User className="text-cyan-600" /> Child
                                        Information
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
                                            <p>
                                                {capitalize(
                                                    intake.gender || '',
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Date of Birth
                                            </p>
                                            <p>
                                                {formatDate(
                                                    intake.date_of_birth,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Age
                                            </p>
                                            <p>{intake.age} years old</p>
                                        </div>
                                        <div className="col-span-2">
                                            <p className="text-xs text-muted-foreground">
                                                Address
                                            </p>
                                            <p>
                                                {[
                                                    intake.street_address,
                                                    intake.address_line_2,
                                                    intake.city,
                                                    intake.state_province,
                                                    intake.postal_code,
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ') || '-'}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="rounded-[10px]">
                                <CardContent className="p-5">
                                    <p className="flex items-center gap-3">
                                        <User className="text-cyan-600" />{' '}
                                        Primary Parent/Guardian
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Main contact for communication
                                    </p>
                                    <div className="mt-10 grid grid-cols-1 gap-5">
                                        <div className="border-b pb-3">
                                            <p className="text-xs text-muted-foreground">
                                                Name & Relationship
                                            </p>
                                            <p>
                                                {intake.primary_parent_name} (
                                                {capitalize(
                                                    intake.primary_relationship_to_child ||
                                                        '',
                                                )}
                                                )
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Mail className="h-4 w-4 text-muted-foreground" />
                                            <p>{intake.primary_parent_email}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Phone className="h-4 w-4 text-muted-foreground" />
                                            <p>{intake.primary_parent_phone}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid grid-cols-1 gap-2 md:p-5">
                            <Card className="rounded-[10px]">
                                <CardContent className="p-5">
                                    <p className="flex items-center gap-3">
                                        <LucideBriefcaseMedical className="text-cyan-600" />{' '}
                                        Medical & Developmental History
                                    </p>
                                    <div className="mt-10 border-b pb-5">
                                        <p className="text-xs text-muted-foreground">
                                            Diagnosis
                                        </p>
                                        <div className="mt-3 flex flex-wrap gap-3">
                                            {intake.diagnosis?.length ? (
                                                intake.diagnosis.map((diag) => (
                                                    <p
                                                        key={diag}
                                                        className="rounded-[5px] bg-gray-500 p-1 text-sm text-white"
                                                    >
                                                        {diag}
                                                    </p>
                                                ))
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No diagnosis
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="mt-10 grid grid-cols-2 gap-5">
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Medical Conditions
                                            </p>
                                            <p>
                                                {intake.medical_conditions ||
                                                    '-'}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Languages Spoken at Home
                                            </p>
                                            <p>
                                                {intake.languages_spoken_at_home ||
                                                    '-'}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid grid-cols-1 gap-2 md:p-5">
                            <Card className="rounded-[10px]">
                                <CardContent className="p-5">
                                    <p className="flex items-center gap-3">
                                        <LucideBriefcaseMedical className="text-cyan-600" />{' '}
                                        Therapy Goals
                                    </p>
                                    <div className="mt-10 grid grid-cols-1 gap-5">
                                        <div className="border-b pb-4">
                                            <p className="text-xs text-muted-foreground">
                                                Therapy Goals
                                            </p>
                                            <p>
                                                {intake.theraphy_goals ||
                                                    'No information provided'}
                                            </p>
                                        </div>
                                        <div className="border-b pb-4">
                                            <p className="text-xs text-muted-foreground">
                                                Additional Information
                                            </p>
                                            <p>
                                                {intake.additional_information ||
                                                    'No information provided'}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="schedule">
                        <div className="grid grid-cols-1 gap-2 md:p-5">
                            <Card className="rounded-[10px]">
                                <CardContent className="space-y-4 p-5">
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Requested Days
                                        </p>
                                        <p>
                                            {intake.available_days?.join(
                                                ', ',
                                            ) || 'No days provided'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Requested Times
                                        </p>
                                        <p>
                                            {intake.preferred_times?.join(
                                                ', ',
                                            ) || 'No times provided'}
                                        </p>
                                    </div>

                                    {scheduleMatch.details.length > 0 && (
                                        <div>
                                            <p className="mb-2 text-xs text-muted-foreground">
                                                Matching Slots
                                            </p>
                                            <div className="space-y-2">
                                                {scheduleMatch.details.map(
                                                    (detail, index) => (
                                                        <div
                                                            key={index}
                                                            className="rounded-[5px] border border-green-300 bg-green-50 p-3 text-sm"
                                                        >
                                                            {detail.day} —{' '}
                                                            {detail.preferred}{' '}
                                                            (you're available{' '}
                                                            {
                                                                detail.therapist_from
                                                            }
                                                            –
                                                            {
                                                                detail.therapist_to
                                                            }
                                                            )
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="documents">
                        <DocumentsTab intake={intake} isPreview />
                    </TabsContent>
                </Tabs>

                {pendingReviews.map((review) => (
                    <IntakeDecisionCard
                        key={review.id}
                        intakeId={intake.id}
                        service={review.service}
                    />
                ))}
            </div>
        </>
    );
}

TherapistIntakeShow.layout = (page: React.ReactElement) => (
    <TherapistLayout>{page}</TherapistLayout>
);
