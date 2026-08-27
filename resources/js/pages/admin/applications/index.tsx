import { Link, router } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle,
    Eye,
    Users,
    Video,
    XCircle,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import { ApplicationStatusBadge } from '@/components/admin/application/badges';
import UpdateApplicationStatusModal from '@/components/admin/update-application-status-modal';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate, getInitials } from '@/lib/helpers';
import type {
    Application,
    ApplicationStats,
    ApplicationStatus,
} from '@/types/application';
import type { Paginated } from '@/types/intake';

interface ApplicationsIndexProps {
    applications: Paginated<Application>;
    stats: ApplicationStats;
    filters: { search: string; status: string };
}

/** Admin hiring pipeline, ported from cats-frontend/src/pages/admin/ApplicationsPage.tsx. */
export default function AdminApplicationsIndex({
    applications,
    stats,
    filters,
}: ApplicationsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [statusUpdate, setStatusUpdate] = useState<ApplicationStatus | ''>(
        '',
    );
    const [selectedApplication, setSelectedApplication] =
        useState<Application | null>(null);
    const [modalOpen, setModalOpen] = useState(false);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/applications',
                { search, status: filters.status },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyStatus = (status: string) => {
        router.get(
            '/admin/applications',
            { search, status },
            { preserveState: true, replace: true },
        );
    };

    const openStatusModal = (
        application: Application,
        status: ApplicationStatus,
    ) => {
        setSelectedApplication(application);
        setStatusUpdate(status);
        setModalOpen(true);
    };

    return (
        <div className="space-y-6 p-2 md:p-6">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Applications &amp; Hiring
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Manage contractor applications and hiring process
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-3 gap-2 md:grid-cols-7 md:gap-6">
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light">{stats.total}</p>
                    <p className="text-sm text-muted-foreground">Total</p>
                </Card>
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light text-purple-600">
                        {stats.pending}
                    </p>
                    <p className="text-sm text-muted-foreground">New</p>
                </Card>
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light text-blue-600">
                        {stats.reviewing}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Under Review
                    </p>
                </Card>
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light text-yellow-600">
                        {stats.interview_scheduled}
                    </p>
                    <p className="text-sm text-muted-foreground">Interview</p>
                </Card>
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light text-orange-600">
                        {stats.offer_sent}
                    </p>
                    <p className="text-sm text-muted-foreground">Offer Sent</p>
                </Card>
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light text-green-600">
                        {stats.hired}
                    </p>
                    <p className="text-sm text-muted-foreground">Hired</p>
                </Card>
                <Card className="p-2 text-center md:p-6">
                    <p className="mb-1 font-light text-destructive">
                        {stats.declined}
                    </p>
                    <p className="text-sm text-muted-foreground">Declined</p>
                </Card>
            </div>

            <Card className="p-4">
                <div className="flex flex-row items-start gap-4 md:items-center">
                    <div className="w-full flex-1">
                        <label className="mb-1 block text-sm font-medium text-muted-foreground">
                            Search
                        </label>
                        <Input
                            placeholder="Search by name, role, or email..."
                            className="w-full rounded-[5px]"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>

                    <div className="w-[40%]">
                        <label className="mb-1 block text-sm font-medium text-muted-foreground">
                            Application Status
                        </label>
                        <Select
                            value={filters.status}
                            onValueChange={applyStatus}
                        >
                            <SelectTrigger className="w-full rounded-[5px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Applications
                                </SelectItem>
                                <SelectItem value="pending">New</SelectItem>
                                <SelectItem value="reviewing">
                                    Under Review
                                </SelectItem>
                                <SelectItem value="interview_scheduled">
                                    Interview Scheduled
                                </SelectItem>
                                <SelectItem value="hired">Hired</SelectItem>
                                <SelectItem value="offer_sent">
                                    Offer Sent
                                </SelectItem>
                                <SelectItem value="declined">
                                    Declined
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </Card>

            {applications.data.length > 0 ? (
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {applications.data.map((application) => (
                        <Card key={application.id} className="p-6">
                            <div className="mb-4 flex items-center justify-between gap-4">
                                <Avatar className="h-12 w-12 bg-primary">
                                    <AvatarFallback className="bg-primary text-primary-foreground">
                                        {getInitials(
                                            `${application.first_name} ${application.last_name}`,
                                        )}
                                    </AvatarFallback>
                                </Avatar>
                                <ApplicationStatusBadge
                                    status={application.application_status}
                                />
                            </div>

                            <div className="flex-1">
                                <h3 className="mb-1 font-semibold">
                                    {application.first_name}{' '}
                                    {application.last_name}
                                </h3>
                                <p className="mb-2 text-sm text-muted-foreground">
                                    {application.position_applied}
                                </p>
                            </div>

                            <div className="mb-4 flex gap-1">
                                {[...Array(5)].map((_, i) => (
                                    <span
                                        key={i}
                                        className={
                                            i <
                                            (application.candidate_rating ?? 0)
                                                ? 'text-yellow-500'
                                                : 'text-gray-300'
                                        }
                                    >
                                        ★
                                    </span>
                                ))}
                            </div>

                            <div className="mb-4 flex flex-wrap gap-2">
                                <Badge
                                    variant="outline"
                                    className="rounded-[5px] bg-gray-700 text-gray-100"
                                >
                                    {application.is_working_with_other
                                        ? 'Currently Employed'
                                        : 'Available'}
                                </Badge>
                                {application.drivers_license && (
                                    <Badge
                                        variant="outline"
                                        className="rounded-[5px] bg-gray-700 text-gray-100"
                                    >
                                        Driver&apos;s License
                                    </Badge>
                                )}
                            </div>

                            <div className="mb-4 space-y-2 border-t pt-4 text-sm">
                                <p className="text-muted-foreground">
                                    ✉️ {application.email}
                                </p>
                                <p className="text-muted-foreground">
                                    📞 {application.phone}
                                </p>
                                <p className="text-muted-foreground">
                                    📅 Applied{' '}
                                    {formatDate(application.created_at)}
                                </p>
                            </div>

                            {application.interview_time &&
                                application.application_status ===
                                    'interview_scheduled' && (
                                    <div className="mb-4 rounded border border-yellow-200 bg-yellow-50 p-3">
                                        <p className="mb-1 text-sm font-medium text-yellow-800">
                                            Interview Scheduled
                                        </p>
                                        <p className="flex items-center gap-2 text-sm text-yellow-700">
                                            <Calendar className="h-4 w-4" />{' '}
                                            {application.interview_schedule}
                                        </p>
                                        <p className="mt-2 flex items-center gap-2 text-sm text-yellow-700">
                                            <Video className="h-4 w-4" />
                                            {
                                                application.interview_platform_label
                                            }
                                        </p>
                                    </div>
                                )}

                            <div
                                className={`grid gap-2 ${
                                    application.application_status !==
                                        'hired' &&
                                    application.application_status !==
                                        'declined'
                                        ? 'grid-cols-2'
                                        : 'grid-cols-1'
                                }`}
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 rounded-[5px]"
                                    asChild
                                >
                                    <Link
                                        href={`/admin/applications/${application.id}`}
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        View Profile
                                    </Link>
                                </Button>

                                {application.application_status ===
                                    'pending' && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="flex-1 rounded-[5px]"
                                        onClick={() =>
                                            openStatusModal(
                                                application,
                                                'reviewing',
                                            )
                                        }
                                    >
                                        <Calendar className="mr-2 h-4 w-4" />
                                        Under Review
                                    </Button>
                                )}

                                {application.application_status !== 'hired' &&
                                    application.application_status !==
                                        'declined' &&
                                    application.application_status !==
                                        'pending' &&
                                    (application.application_status ===
                                    'offer_sent' ? (
                                        /*
                                         * Once the offer is out, Decline sits
                                         * beside View Profile and Hire takes
                                         * the row below it — the widest, last
                                         * thing on the card, and inert until
                                         * the candidate has actually signed.
                                         */
                                        <>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                className="flex-1 rounded-[5px]"
                                                onClick={() =>
                                                    openStatusModal(
                                                        application,
                                                        'declined',
                                                    )
                                                }
                                            >
                                                <XCircle className="mr-2 h-4 w-4" />
                                                Decline
                                            </Button>

                                            <div className="col-span-2 flex flex-col gap-1">
                                                <Button
                                                    size="sm"
                                                    className="w-full rounded-[5px] bg-green-600 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                    onClick={() =>
                                                        openStatusModal(
                                                            application,
                                                            'hired',
                                                        )
                                                    }
                                                    disabled={
                                                        !application.offer_accepted_at ||
                                                        !application.signed_offer_letter
                                                    }
                                                    title={
                                                        application.offer_accepted_at &&
                                                        application.signed_offer_letter
                                                            ? undefined
                                                            : 'The candidate has not signed their offer letter yet.'
                                                    }
                                                >
                                                    <CheckCircle className="mr-2 h-4 w-4" />
                                                    Hire
                                                </Button>

                                                {(!application.offer_accepted_at ||
                                                    !application.signed_offer_letter) && (
                                                    <p className="text-center text-xs text-muted-foreground">
                                                        Waiting on the
                                                        candidate&apos;s
                                                        signature
                                                    </p>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="flex-1 rounded-[5px] text-center whitespace-normal"
                                                onClick={() =>
                                                    openStatusModal(
                                                        application,
                                                        'interview_scheduled',
                                                    )
                                                }
                                            >
                                                <Calendar className="mr-2 h-4 w-4 shrink-0" />
                                                <span className="break-words">
                                                    {application.application_status ===
                                                    'interview_scheduled'
                                                        ? 'Reschedule'
                                                        : 'Interview'}
                                                </span>
                                            </Button>

                                            <Button
                                                size="sm"
                                                className="flex-1 rounded-[5px] bg-orange-600 hover:bg-orange-700"
                                                onClick={() =>
                                                    openStatusModal(
                                                        application,
                                                        'offer_sent',
                                                    )
                                                }
                                            >
                                                <CheckCircle className="mr-2 h-4 w-4" />
                                                Send Offer
                                            </Button>

                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                className="flex-1 rounded-[5px]"
                                                onClick={() =>
                                                    openStatusModal(
                                                        application,
                                                        'declined',
                                                    )
                                                }
                                            >
                                                <XCircle className="mr-2 h-4 w-4" />
                                                Decline
                                            </Button>
                                        </>
                                    ))}
                            </div>
                        </Card>
                    ))}
                </div>
            ) : (
                <Card className="w-full p-12">
                    <div className="text-center text-muted-foreground">
                        <Users className="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p className="mb-2 text-lg font-medium">
                            No Applications Found
                        </p>
                        <p className="text-sm">
                            {search || filters.status !== 'all'
                                ? 'Try adjusting your search or filters.'
                                : 'Start by taking applications.'}
                        </p>
                    </div>
                </Card>
            )}

            {applications.last_page > 1 && (
                <div className="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={() =>
                            router.get(
                                '/admin/applications',
                                {
                                    search,
                                    status: filters.status,
                                    page: applications.current_page - 1,
                                },
                                { preserveState: true, replace: true },
                            )
                        }
                        disabled={applications.current_page <= 1}
                    >
                        Previous
                    </Button>
                    <span className="flex items-center px-2">
                        Page {applications.current_page} of{' '}
                        {applications.last_page}
                    </span>
                    <Button
                        size="sm"
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={() =>
                            router.get(
                                '/admin/applications',
                                {
                                    search,
                                    status: filters.status,
                                    page: applications.current_page + 1,
                                },
                                { preserveState: true, replace: true },
                            )
                        }
                        disabled={
                            applications.current_page >= applications.last_page
                        }
                    >
                        Next
                    </Button>
                </div>
            )}

            {selectedApplication && (
                <UpdateApplicationStatusModal
                    application={selectedApplication}
                    targetStatus={statusUpdate}
                    isOpen={modalOpen}
                    onClose={() => setModalOpen(false)}
                />
            )}
        </div>
    );
}

AdminApplicationsIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
