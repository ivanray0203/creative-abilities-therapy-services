import { Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    Calendar,
    CheckCircle,
    CircleX,
    DollarSign,
    Clock as ClockIcon,
    Heart,
    Timer,
    UserCheck2,
    Video,
} from 'lucide-react';
import { useState } from 'react';

import ApplicationTab from '@/components/admin/application/application-tab';
import AvailabilityTab from '@/components/admin/application/availability-tab';
import { ApplicationStatusBadge } from '@/components/admin/application/badges';
import NotesTab from '@/components/admin/application/notes-tab';
import OverviewTab from '@/components/admin/application/overview-tab';
import UpdateApplicationStatusModal from '@/components/admin/update-application-status-modal';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import { capitalize, getInitials } from '@/lib/helpers';
import type { Application, ApplicationStatus } from '@/types/application';

interface ApplicationShowProps {
    application: Application;
    statusTransitions: ApplicationStatus[];
}

/** Admin application detail page, ported from cats-frontend/src/pages/admin/ApplicationDetailPage.tsx. */
export default function AdminApplicationShow({
    application,
    statusTransitions,
}: ApplicationShowProps) {
    const [statusUpdate, setStatusUpdate] = useState<ApplicationStatus | ''>(
        '',
    );
    const [modalOpen, setModalOpen] = useState(false);

    const canReview =
        application.application_status === 'pending' &&
        statusTransitions.includes('reviewing');
    const canDecide =
        application.application_status !== 'pending' &&
        application.application_status !== 'hired' &&
        application.application_status !== 'declined';

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

                            <Button
                                variant="outline"
                                className="rounded-[5px] bg-orange-600 text-white hover:bg-white hover:text-orange-600"
                                onClick={() => openStatusModal('hired')}
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
                <div className="rounded-[5px] border border-yellow-400 bg-yellow-50 p-4">
                    <p className="flex flex-row items-center gap-4 text-yellow-800">
                        <Calendar className="h-4 w-4" /> Interview Scheduled
                    </p>
                    <div className="ml-8 flex flex-row items-center gap-3 text-sm text-yellow-800">
                        <Calendar className="h-3 w-3" />
                        <p>{application.interview_date}</p>
                        <Timer className="h-3 w-3" />
                        <p>{application.interview_time}</p>
                        <Video className="h-3 w-3" />
                        <p>{capitalize(application.interview_platform)}</p>
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
                                {application.hire_date}
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
                            <p>{application.created_at.split('T')[0]}</p>
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
                            <p>{application.preferred_start_date || '-'}</p>
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
