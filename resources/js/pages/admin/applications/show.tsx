import { Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeftIcon,
    Calendar,
    CheckCircle,
    CircleX,
    ClipboardCheck,
    DollarSign,
    FileSignature,
    Clock as ClockIcon,
    Heart,
    UserCheck2,
    Video,
} from 'lucide-react';
import { useState } from 'react';

import ApplicationTab from '@/components/admin/application/application-tab';
import AvailabilityTab from '@/components/admin/application/availability-tab';
import { ApplicationStatusBadge } from '@/components/admin/application/badges';
import DocumentsTab from '@/components/admin/application/documents-tab';
import NotesTab from '@/components/admin/application/notes-tab';
import OverviewTab from '@/components/admin/application/overview-tab';
import UpdateApplicationStatusModal from '@/components/admin/update-application-status-modal';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import { capitalize, formatDate, getInitials } from '@/lib/helpers';
import type {
    Application,
    ApplicationOnboarding,
    ApplicationStatus,
} from '@/types/application';

interface ApplicationShowProps {
    application: Application;
    statusTransitions: ApplicationStatus[];
    onboarding: ApplicationOnboarding | null;
}

/** Admin application detail page, ported from cats-frontend/src/pages/admin/ApplicationDetailPage.tsx. */
export default function AdminApplicationShow({
    application,
    statusTransitions,
    onboarding,
}: ApplicationShowProps) {
    const [statusUpdate, setStatusUpdate] = useState<ApplicationStatus | ''>(
        '',
    );
    const [modalOpen, setModalOpen] = useState(false);

    const canReview =
        application.application_status === 'pending' &&
        statusTransitions.includes('reviewing');
    const isOnboarding = application.application_status === 'onboarding';
    const canDecide =
        application.application_status !== 'pending' &&
        application.application_status !== 'onboarding' &&
        application.application_status !== 'hired' &&
        application.application_status !== 'declined';
    const offerSent = application.application_status === 'offer_sent';
    const offerSigned = Boolean(
        application.offer_accepted_at && application.signed_offer_letter,
    );
    const missingDocuments = onboarding?.missing_documents ?? [];
    const documentsComplete = isOnboarding && missingDocuments.length === 0;

    const openStatusModal = (status: ApplicationStatus) => {
        setStatusUpdate(status);
        setModalOpen(true);
    };

    const updateRating = (rating: number) => {
        router.patch(
            `/admin/applications/${application.id}/rating`,
            { candidate_rating: rating },
            { preserveScroll: true },
        );
    };

    return (
        <div className="space-y-6 p-2 md:p-6">
            <div className="mt-2 flex flex-col items-start justify-between gap-4 md:mt-0 md:flex-row md:items-center">
                <div className="flex flex-row gap-3">
                    <Avatar className="h-14 w-14 bg-primary md:h-20 md:w-20">
                        <AvatarFallback className="bg-cyan-600 text-sm text-primary-foreground md:text-3xl">
                            {getInitials(
                                `${application.first_name} ${application.last_name}`,
                            )}
                        </AvatarFallback>
                    </Avatar>

                    <div>
                        <p className="text-xl md:text-3xl">
                            {application.first_name} {application.last_name}
                        </p>
                        <p className="text-sm text-muted-foreground md:text-xl">
                            {application.position_applied}
                        </p>
                        <div className="mt-2 flex flex-row items-center gap-3">
                            <ApplicationStatusBadge
                                status={application.application_status}
                            />

                            <div className="flex items-center gap-1">
                                {[...Array(5)].map((_, i) => {
                                    const starValue = i + 1;

                                    return (
                                        <span
                                            key={i}
                                            onClick={() =>
                                                updateRating(starValue)
                                            }
                                            className={`cursor-pointer text-xs md:text-xl ${
                                                starValue <=
                                                (application.candidate_rating ??
                                                    0)
                                                    ? 'text-yellow-500'
                                                    : 'text-gray-300'
                                            }`}
                                        >
                                            ★
                                        </span>
                                    );
                                })}
                                <p className="ml-1 text-xs text-muted-foreground md:text-base">
                                    ({application.candidate_rating ?? 0}/5)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-3 md:flex-row">
                    <Button variant="ghost" className="hidden md:flex" asChild>
                        <Link href="/admin/applications">
                            <ArrowLeftIcon /> Back
                        </Link>
                    </Button>

                    {canReview && (
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex-1 rounded-[5px]"
                            onClick={() => openStatusModal('reviewing')}
                        >
                            <Calendar className="mr-2 h-4 w-4" />
                            Under Review
                        </Button>
                    )}

                    {canDecide && (
                        <div className="flex flex-col gap-2 md:flex-row">
                            <Button
                                variant="outline"
                                className="rounded-[5px]"
                                onClick={() =>
                                    openStatusModal('interview_scheduled')
                                }
                            >
                                <Calendar />
                                {application.application_status ===
                                'interview_scheduled'
                                    ? 'Reschedule Interview'
                                    : 'Schedule Interview'}
                            </Button>

                            {!offerSent && (
                                <Button
                                    variant="outline"
                                    className="rounded-[5px] bg-orange-600 text-white hover:bg-white hover:text-orange-600"
                                    onClick={() =>
                                        openStatusModal('offer_sent')
                                    }
                                >
                                    <FileSignature />
                                    Send Offer
                                </Button>
                            )}

                            {offerSent && (
                                <>
                                    <Button
                                        variant="outline"
                                        className="rounded-[5px]"
                                        onClick={() =>
                                            openStatusModal('offer_sent')
                                        }
                                    >
                                        <FileSignature />
                                        Resend Offer
                                    </Button>

                                    <Button
                                        variant="outline"
                                        className="rounded-[5px] bg-cyan-600 text-white hover:bg-white hover:text-cyan-600 disabled:opacity-50"
                                        onClick={() =>
                                            openStatusModal('onboarding')
                                        }
                                        disabled={!offerSigned}
                                        title={
                                            offerSigned
                                                ? undefined
                                                : 'The candidate has not signed their offer letter yet.'
                                        }
                                    >
                                        <ClipboardCheck />
                                        Start Onboarding
                                    </Button>
                                </>
                            )}

                            <Button
                                variant="outline"
                                className="rounded-[5px] bg-red-600 text-white hover:bg-white hover:text-red-600"
                                onClick={() => openStatusModal('declined')}
                            >
                                <CircleX />
                                Decline
                            </Button>
                        </div>
                    )}

                    {isOnboarding && (
                        <div className="flex flex-col gap-2 md:flex-row">
                            <Button
                                variant="outline"
                                className="rounded-[5px] bg-green-600 text-white hover:bg-white hover:text-green-600 disabled:opacity-50"
                                onClick={() => openStatusModal('hired')}
                                disabled={!documentsComplete}
                                title={
                                    documentsComplete
                                        ? undefined
                                        : 'The candidate has not uploaded every required document yet.'
                                }
                            >
                                <UserCheck2 />
                                Hire
                            </Button>

                            <Button
                                variant="outline"
                                className="rounded-[5px] bg-red-600 text-white hover:bg-white hover:text-red-600"
                                onClick={() => openStatusModal('declined')}
                            >
                                <CircleX />
                                Decline
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            {application.application_status === 'interview_scheduled' && (
                <div className="flex flex-col justify-between gap-3 rounded-[5px] border border-yellow-400 bg-yellow-50 p-4 md:flex-row md:items-center">
                    <div>
                        <p className="flex flex-row items-center gap-4 text-yellow-800">
                            <Calendar className="h-4 w-4" /> Interview Scheduled
                        </p>
                        <div className="ml-8 flex flex-row flex-wrap items-center gap-3 text-sm text-yellow-800">
                            <Calendar className="h-3 w-3" />
                            <p>{application.interview_schedule}</p>
                            <Video className="h-3 w-3" />
                            <p>{application.interview_platform_label}</p>
                        </div>
                        {application.interview_platform === 'video' &&
                            !application.interview_meeting_link && (
                                <p className="mt-1 ml-8 text-xs text-yellow-800">
                                    No Google Meet link was generated. Connect
                                    the Google account under Google Drive
                                    settings and reschedule to create one.
                                </p>
                            )}
                    </div>

                    {application.interview_meeting_link && (
                        <Button
                            variant="outline"
                            className="rounded-[5px] border-yellow-500 text-yellow-700"
                            asChild
                        >
                            <a
                                href={application.interview_meeting_link}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Video />
                                Join Google Meet
                            </a>
                        </Button>
                    )}
                </div>
            )}

            {offerSent && (
                <div className="flex flex-col justify-between gap-3 rounded-[5px] border border-orange-400 bg-orange-50 p-4 md:flex-row md:items-center">
                    <div>
                        <p className="flex flex-row items-center gap-4 text-orange-800">
                            <FileSignature className="h-4 w-4" />
                            {offerSigned
                                ? 'Offer signed'
                                : 'Offer sent — awaiting signature'}
                        </p>
                        <div className="ml-8 flex flex-row flex-wrap items-center gap-3 text-sm text-orange-800">
                            <span>
                                Sent {formatDate(application.offer_sent_at)}
                            </span>
                            {offerSigned ? (
                                <span>
                                    Signed{' '}
                                    {formatDate(application.offer_accepted_at)}
                                </span>
                            ) : (
                                <span>
                                    Expires{' '}
                                    {formatDate(application.offer_expires_at)}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-row gap-2">
                        {application.offer_letter && (
                            <Button
                                variant="outline"
                                className="rounded-[5px] border-orange-500 text-orange-600"
                                asChild
                            >
                                <a
                                    href={application.offer_letter}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    Letter
                                </a>
                            </Button>
                        )}
                        {application.signed_offer_letter && (
                            <Button
                                variant="outline"
                                className="rounded-[5px] border-orange-500 text-orange-600"
                                asChild
                            >
                                <a
                                    href={application.signed_offer_letter}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    Signed copy
                                </a>
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {onboarding && (
                <div className="rounded-[5px] border border-cyan-400 bg-cyan-50 p-4 text-cyan-900">
                    <div className="flex flex-col justify-between gap-2 md:flex-row md:items-center">
                        <div>
                            <p className="flex flex-row items-center gap-4">
                                <ClipboardCheck className="h-4 w-4" />
                                {isOnboarding
                                    ? documentsComplete
                                        ? 'Onboarding documents complete — ready to hire'
                                        : 'Onboarding in progress — awaiting documents'
                                    : 'Onboarding documents'}
                            </p>
                            {application.onboarding_started_at && (
                                <p className="ml-8 text-sm">
                                    Account created{' '}
                                    {formatDate(
                                        application.onboarding_started_at,
                                    )}
                                    . The candidate can only see their profile
                                    until they are hired.
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-3 ml-8 grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm font-medium">
                                Required documents
                            </p>
                            {onboarding.required_documents.length === 0 ? (
                                <p className="text-sm">
                                    This position lists no required documents.
                                </p>
                            ) : (
                                <ul className="mt-1 space-y-1 text-sm">
                                    {onboarding.required_documents.map(
                                        (doc) => {
                                            const missing =
                                                missingDocuments.includes(doc);

                                            return (
                                                <li
                                                    key={doc}
                                                    className="flex items-center gap-2"
                                                >
                                                    {missing ? (
                                                        <AlertTriangle className="h-3.5 w-3.5 text-red-600" />
                                                    ) : (
                                                        <CheckCircle className="h-3.5 w-3.5 text-green-600" />
                                                    )}
                                                    <span
                                                        className={
                                                            missing
                                                                ? 'text-red-700'
                                                                : undefined
                                                        }
                                                    >
                                                        {doc}
                                                    </span>
                                                </li>
                                            );
                                        },
                                    )}
                                </ul>
                            )}
                        </div>

                        <div>
                            <p className="text-sm font-medium">
                                Uploaded ({onboarding.documents.length})
                            </p>
                            {onboarding.documents.length === 0 ? (
                                <p className="text-sm">Nothing uploaded yet.</p>
                            ) : (
                                <ul className="mt-1 space-y-1 text-sm">
                                    {onboarding.documents.map((doc) => (
                                        <li
                                            key={doc.id}
                                            className="flex flex-wrap items-center gap-2"
                                        >
                                            <span>{doc.title}</span>
                                            <span className="text-xs text-cyan-700">
                                                {doc.doc_type}
                                                {doc.uploaded_at
                                                    ? ` • ${formatDate(doc.uploaded_at)}`
                                                    : ''}
                                            </span>
                                            {doc.drive_web_view && (
                                                <a
                                                    href={doc.drive_web_view}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-xs underline"
                                                >
                                                    View
                                                </a>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {application.application_status === 'hired' && (
                <div className="flex flex-row items-center justify-between rounded-[5px] border border-orange-400 bg-orange-50 p-4">
                    <div>
                        <p className="flex flex-row items-center gap-4 text-orange-800">
                            <CheckCircle className="h-4 w-4" /> Added to Team
                            Management
                        </p>
                        <div className="ml-8 flex flex-row items-center gap-3 text-sm text-orange-800">
                            <p>
                                This candidate was added to Team Management on{' '}
                                {formatDate(application.hire_date)}
                            </p>
                        </div>
                    </div>

                    <Button
                        variant="outline"
                        className="rounded-[5px] border-orange-500 text-orange-600"
                        asChild
                    >
                        <Link href="/admin/team">Go to Team</Link>
                    </Button>
                </div>
            )}

            <div className="grid gap-2 md:grid-cols-2 md:gap-6 lg:grid-cols-4">
                <Card className="p-6">
                    <div className="flex items-center gap-3">
                        <div className="rounded-full bg-yellow-100 p-3">
                            <ClockIcon className="h-6 w-6 text-yellow-600" />
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Applied
                            </p>
                            <p>{formatDate(application.created_at)}</p>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center gap-3">
                        <div className="rounded-full bg-blue-100 p-3">
                            <DollarSign className="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Status
                            </p>
                            <p>
                                {application.application_status ===
                                'interview_scheduled'
                                    ? 'Interview Scheduled'
                                    : application.application_status ===
                                        'offer_sent'
                                      ? 'Offer Sent'
                                      : capitalize(
                                            application.application_status,
                                        )}
                            </p>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center gap-3">
                        <div className="rounded-full bg-violet-100 p-3">
                            <Heart className="h-6 w-6 text-violet-600" />
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Preferred Start Date
                            </p>
                            <p>
                                {formatDate(application.preferred_start_date)}
                            </p>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center gap-3">
                        <div className="rounded-full bg-orange-100 p-3">
                            <Calendar className="h-6 w-6 text-orange-600" />
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Expected Salary
                            </p>
                            <p>{application.expected_salary || '-'}</p>
                        </div>
                    </div>
                </Card>
            </div>

            <Tabs defaultValue="overview" className="space-y-4">
                <TabsList className="flex w-full">
                    <TabsTrigger
                        value="overview"
                        className="flex-1 text-center"
                    >
                        Overview
                    </TabsTrigger>
                    <TabsTrigger
                        value="application"
                        className="flex-1 text-center"
                    >
                        Application
                    </TabsTrigger>
                    <TabsTrigger
                        value="availability"
                        className="flex-1 text-center"
                    >
                        Availability
                    </TabsTrigger>
                    <TabsTrigger
                        value="documents"
                        className="flex-1 text-center"
                    >
                        Documents
                        {onboarding ? ` (${onboarding.documents.length})` : ''}
                    </TabsTrigger>
                    <TabsTrigger
                        value="notes"
                        className="hidden flex-1 text-center md:block"
                    >
                        Notes
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="overview">
                    <OverviewTab application={application} />
                </TabsContent>
                <TabsContent value="application">
                    <ApplicationTab application={application} />
                </TabsContent>
                <TabsContent value="availability">
                    <AvailabilityTab application={application} />
                </TabsContent>
                <TabsContent value="documents">
                    <DocumentsTab
                        application={application}
                        onboarding={onboarding}
                    />
                </TabsContent>
                <TabsContent value="notes">
                    <NotesTab application={application} />
                </TabsContent>
            </Tabs>

            <UpdateApplicationStatusModal
                application={application}
                targetStatus={statusUpdate}
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
            />
        </div>
    );
}

AdminApplicationShow.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
