import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle, Clock, Eye, Plus } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';

import { ComplaintStatusBadge } from '@/components/complaints/badges';
import ComplaintDetailModal from '@/components/complaints/complaint-detail-modal';
import ResolveComplaintModal from '@/components/complaints/resolve-complaint-modal';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import ClientLayout from '@/layouts/client-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type {
    Complaint,
    ComplaintFilters,
    ComplaintStats,
    ComplaintType,
} from '@/types/complaint';
import type { Paginated } from '@/types/intake';

interface ComplaintsIndexProps {
    complaints: Paginated<Complaint>;
    stats: ComplaintStats;
    filters: ComplaintFilters;
    role: 'admin' | 'therapist' | 'client';
}

const BASE_PATHS: Record<ComplaintsIndexProps['role'], string> = {
    admin: '/admin/messages',
    therapist: '/therapist/complaints',
    client: '/client/complaints',
};

/**
 * Shared complaints/disputes list. For admin, this is "Complaints &
 * Disputes" (MessagesPage) with review/resolve actions; for
 * client/therapist it's their own filed complaints.
 */
export default function ComplaintsIndex({
    complaints,
    stats,
    filters,
    role,
}: ComplaintsIndexProps) {
    const [activeTab, setActiveTab] = useState<ComplaintType>('complaints');
    const [selectedComplaint, setSelectedComplaint] =
        useState<Complaint | null>(null);
    const [detailOpen, setDetailOpen] = useState(false);
    const [resolveOpen, setResolveOpen] = useState(false);

    const isAdmin = role === 'admin';
    const basePath = BASE_PATHS[role];

    const applyTab = (type: ComplaintType) => {
        setActiveTab(type);
        router.get(
            basePath,
            { ...filters, type },
            { preserveState: true, replace: true },
        );
    };

    const startReview = (complaint: Complaint) => {
        router.post(
            `/admin/messages/${complaint.id}/start-review`,
            {},
            { preserveScroll: true },
        );
    };

    const viewDetail = (complaint: Complaint) => {
        setSelectedComplaint(complaint);
        setDetailOpen(true);
    };

    const openResolve = (complaint: Complaint) => {
        setSelectedComplaint(complaint);
        setResolveOpen(true);
    };

    return (
        <>
            <Head title={isAdmin ? 'Complaints & Disputes' : 'My Complaints'} />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            {isAdmin
                                ? 'Complaints & Disputes'
                                : 'My Complaints'}
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            {isAdmin
                                ? 'Review and resolve filed complaints and session disputes'
                                : 'Complaints you have filed'}
                        </p>
                    </div>
                    {!isAdmin && (
                        <Button className="rounded-[10px]" asChild>
                            <Link href={`${basePath}/create`}>
                                <Plus /> File a Complaint
                            </Link>
                        </Button>
                    )}
                </div>

                {isAdmin && (
                    <div className="grid grid-cols-3 gap-3">
                        <Card className="p-4">
                            <p className="text-xs text-muted-foreground">New</p>
                            <p className="text-2xl font-bold">
                                {stats[activeTab].open}
                            </p>
                        </Card>
                        <Card className="p-4">
                            <p className="text-xs text-muted-foreground">
                                Under Review
                            </p>
                            <p className="text-2xl font-bold">
                                {stats[activeTab].under_review}
                            </p>
                        </Card>
                        <Card className="p-4">
                            <p className="text-xs text-muted-foreground">
                                Total
                            </p>
                            <p className="text-2xl font-bold">
                                {stats[activeTab].total}
                            </p>
                        </Card>
                    </div>
                )}

                <Tabs
                    value={activeTab}
                    onValueChange={(value) => applyTab(value as ComplaintType)}
                >
                    <TabsList>
                        <TabsTrigger value="complaints">
                            Complaints ({stats.complaints.total})
                        </TabsTrigger>
                        <TabsTrigger value="disputes">
                            Disputes ({stats.disputes.total})
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value={activeTab} className="mt-4">
                        {complaints.data.length > 0 ? (
                            <div className="grid grid-cols-1 gap-4">
                                {complaints.data.map((complaint) => {
                                    const childName = complaint.client
                                        ?.original_intake
                                        ? `${complaint.client.original_intake.child_first_name} ${complaint.client.original_intake.child_last_name}`
                                        : 'Client';

                                    return (
                                        <Card
                                            key={complaint.id}
                                            className="p-5"
                                        >
                                            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-semibold">
                                                            {complaint.subject}
                                                        </p>
                                                        <ComplaintStatusBadge
                                                            status={
                                                                complaint.status
                                                            }
                                                        />
                                                    </div>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        Filed by{' '}
                                                        {complaint.complained_by ===
                                                        'client'
                                                            ? childName
                                                            : complaint.therapist
                                                              ? `${complaint.therapist.first_name} ${complaint.therapist.last_name}`
                                                              : 'Therapist'}
                                                    </p>
                                                    <p className="mt-2 line-clamp-2 text-sm">
                                                        {complaint.description}
                                                    </p>
                                                </div>

                                                <div className="flex shrink-0 flex-wrap gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="rounded-[5px]"
                                                        onClick={() =>
                                                            viewDetail(
                                                                complaint,
                                                            )
                                                        }
                                                    >
                                                        <Eye className="h-4 w-4" />{' '}
                                                        View
                                                    </Button>
                                                    {isAdmin &&
                                                        complaint.status ===
                                                            'open' && (
                                                            <Button
                                                                size="sm"
                                                                className="rounded-[5px]"
                                                                onClick={() =>
                                                                    startReview(
                                                                        complaint,
                                                                    )
                                                                }
                                                            >
                                                                <Clock className="h-4 w-4" />{' '}
                                                                Start Review
                                                            </Button>
                                                        )}
                                                    {isAdmin &&
                                                        complaint.status ===
                                                            'under_review' && (
                                                            <Button
                                                                size="sm"
                                                                className="rounded-[5px] bg-green-600 hover:bg-green-700"
                                                                onClick={() =>
                                                                    openResolve(
                                                                        complaint,
                                                                    )
                                                                }
                                                            >
                                                                <CheckCircle className="h-4 w-4" />{' '}
                                                                Resolve
                                                            </Button>
                                                        )}
                                                </div>
                                            </div>
                                        </Card>
                                    );
                                })}
                            </div>
                        ) : (
                            <Card className="p-12 text-center text-muted-foreground">
                                No {activeTab} found
                            </Card>
                        )}
                    </TabsContent>
                </Tabs>
            </div>

            <ComplaintDetailModal
                complaint={selectedComplaint}
                isOpen={detailOpen}
                onClose={() => setDetailOpen(false)}
            />
            {isAdmin && (
                <ResolveComplaintModal
                    complaint={selectedComplaint}
                    isOpen={resolveOpen}
                    onClose={() => setResolveOpen(false)}
                />
            )}
        </>
    );
}

/**
 * Picks the layout via `usePage()` rather than the `page.props` argument
 * Inertia passes to `.layout()` — that argument comes back `undefined`
 * during client-side page swaps, which crashed navigation entirely when
 * read synchronously here.
 */
function ComplaintsLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: ComplaintsIndexProps['role'] }>().props;

    if (role === 'admin') {
        return <AdminLayout>{children}</AdminLayout>;
    }

    if (role === 'therapist') {
        return <TherapistLayout>{children}</TherapistLayout>;
    }

    return <ClientLayout>{children}</ClientLayout>;
}

ComplaintsIndex.layout = (page: React.ReactNode) => (
    <ComplaintsLayout>{page}</ComplaintsLayout>
);
