import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    ArrowRight,
    Calendar,
    Clock,
    DollarSign,
    Dot,
    Download,
    Edit,
    FileDown,
    Heart,
    List,
    Trash,
    User,
    UserCheck,
    X,
} from 'lucide-react';
import { useState } from 'react';

import ApproveIntakeDialog from '@/components/admin/approve-intake-dialog';
import DeleteIntakeModal from '@/components/admin/delete-intake-modal';
import {
    FundingBadge,
    IntakeStatusBadge,
} from '@/components/admin/intake/badges';
import DocumentsTab from '@/components/admin/intake/documents-tab';
import FamilyTab from '@/components/admin/intake/family-tab';
import HistoryTab from '@/components/admin/intake/history-tab';
import NotesTab from '@/components/admin/intake/notes-tab';
import OverviewTab from '@/components/admin/intake/overview-tab';
import IntakePdfViewModal from '@/components/admin/intake-pdf-view-modal';
import UpdateStatusModal from '@/components/admin/update-status-modal';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import { exportIntakeCsv } from '@/lib/intake-csv';
import type { Intake, IntakeStatus, TherapistOption } from '@/types/intake';

interface IntakeShowProps {
    intake: Intake;
    therapists: TherapistOption[];
    statusTransitions: IntakeStatus[];
}

/** Reference: cats-frontend/src/pages/admin/IntakeDetailPage.tsx */
export default function AdminIntakeShow({
    intake,
    therapists,
    statusTransitions,
}: IntakeShowProps) {
    const [changeStatusOpen, setChangeStatusOpen] = useState(false);
    const [targetStatus, setTargetStatus] = useState<IntakeStatus | ''>('');
    const [pdfOpen, setPdfOpen] = useState(false);
    const [approveOpen, setApproveOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const openStatusModal = (status: IntakeStatus) => {
        setTargetStatus(status);
        setChangeStatusOpen(false);
    };

    const canAssignTherapist = statusTransitions.includes('approved');

    return (
        <>
            <Head title={`Intake ${intake.reference_number ?? intake.id}`} />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <Button variant="ghost" asChild>
                        <Link href="/admin/intake">
                            <ArrowLeftIcon /> Back to Intakes
                        </Link>
                    </Button>

                    <div className="flex flex-row gap-3">
                        <Button
                            variant="outline"
                            className="rounded-[5px]"
                            onClick={() => exportIntakeCsv(intake)}
                        >
                            <FileDown />
                            Export CSV
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-[5px]"
                            onClick={() => setPdfOpen(true)}
                        >
                            <Download />
                            Export PDF
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-[5px]"
                            asChild
                        >
                            <Link href={`/admin/intake/${intake.id}/edit`}>
                                <Edit />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-[5px] text-red-700 hover:bg-red-600 hover:text-white"
                            onClick={() => setDeleteOpen(true)}
                        >
                            <Trash />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="flex flex-row justify-between">
                    <div>
                        <div className="flex flex-col gap-2 md:flex-row md:items-center">
                            <p className="text-2xl font-semibold">
                                {intake.child_first_name}{' '}
                                {intake.child_last_name}
                            </p>
                            <IntakeStatusBadge intake={intake} />
                        </div>
                        <div className="flex flex-col gap-1 md:flex-row">
                            <p className="text-sm text-muted-foreground md:text-base">
                                Intake ID: INT - {intake.id}
                            </p>
                            <Dot className="hidden md:block" />
                            <p className="text-sm text-muted-foreground md:text-base">
                                Submitted: {intake.created_at?.split('T')[0]}
                            </p>
                            <Dot className="hidden md:block" />
                            <p className="text-sm text-muted-foreground md:text-base">
                                Age: {intake.age}
                            </p>
                        </div>
                    </div>

                    <div className="relative">
                        {!intake.approved_as_client && (
                            <div className="flex gap-3">
                                <Button
                                    className="rounded-[5px]"
                                    onClick={() =>
                                        setChangeStatusOpen(!changeStatusOpen)
                                    }
                                >
                                    Change Status <ArrowRight />
                                </Button>

                                {/*
                                    The reference left its ApproveIntakeDialog trigger commented out.
                                    Phase 6's acceptance criteria require the direct-approve workflow
                                    to be reachable, so the trigger is wired here for real.
                                */}
                                <Button
                                    variant="outline"
                                    className="rounded-[5px]"
                                    onClick={() => setApproveOpen(true)}
                                >
                                    <UserCheck />
                                    Approve as Client
                                </Button>
                            </div>
                        )}

                        {changeStatusOpen && (
                            <div className="absolute top-12 right-0 z-20 w-[280px] rounded-[10px] border border-gray-200 bg-white p-3 shadow-md">
                                <div className="border-b border-gray-200 pb-1">
                                    <p>Update Intake Status</p>
                                </div>

                                <p className="mt-3 text-sm font-bold">
                                    Current Status:
                                </p>
                                <IntakeStatusBadge intake={intake} />

                                <div className="mt-2 flex flex-col items-start">
                                    {statusTransitions.includes(
                                        'under_review',
                                    ) && (
                                        <Button
                                            variant="ghost"
                                            className="rounded-[10px] font-normal hover:bg-secondary-orange/10 hover:text-black"
                                            onClick={() =>
                                                openStatusModal('under_review')
                                            }
                                        >
                                            <List className="text-blue-500" />
                                            Move to Under Review
                                        </Button>
                                    )}

                                    {canAssignTherapist && (
                                        <Button
                                            variant="ghost"
                                            className="rounded-[10px] font-normal hover:bg-secondary-orange/10 hover:text-black"
                                            onClick={() =>
                                                openStatusModal('approved')
                                            }
                                        >
                                            <User className="text-green-500" />
                                            {intake.status === 'approved'
                                                ? 'Re-Assign to Therapist'
                                                : 'Assign to Therapist'}
                                        </Button>
                                    )}

                                    {statusTransitions.includes('waitlist') && (
                                        <Button
                                            variant="ghost"
                                            className="rounded-[10px] font-normal hover:bg-secondary-orange/10 hover:text-black"
                                            onClick={() =>
                                                openStatusModal('waitlist')
                                            }
                                        >
                                            <List className="text-violet-500" />
                                            Add to Waitlist
                                        </Button>
                                    )}

                                    {statusTransitions.includes('denied') && (
                                        <Button
                                            variant="ghost"
                                            className="rounded-[10px] font-normal hover:bg-secondary-orange/10 hover:text-black"
                                            onClick={() =>
                                                openStatusModal('denied')
                                            }
                                        >
                                            <X className="text-red-500" />
                                            Deny Application
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <SummaryCard
                        label="Primary Contact"
                        tone="bg-yellow-100"
                        icon={<Clock className="h-6 w-6 text-yellow-600" />}
                    >
                        <p>{intake.primary_parent_name}</p>
                    </SummaryCard>
                    <SummaryCard
                        label="Funding Source"
                        tone="bg-blue-100"
                        icon={<DollarSign className="h-6 w-6 text-blue-600" />}
                    >
                        <FundingBadge fundingSource={intake.funding_source} />
                    </SummaryCard>
                    <SummaryCard
                        label="Service Requested"
                        tone="bg-violet-100"
                        icon={<Heart className="h-6 w-6 text-violet-600" />}
                    >
                        <p>{(intake.services_needed ?? []).length} service/s</p>
                    </SummaryCard>
                    <SummaryCard
                        label="Days Available"
                        tone="bg-orange-100"
                        icon={<Calendar className="h-6 w-6 text-orange-600" />}
                    >
                        <p>{(intake.available_days ?? []).length} days/ week</p>
                    </SummaryCard>
                </div>

                <p className="block text-center text-sm text-muted-foreground md:hidden">
                    *** You can view more details on larger Screens***
                </p>

                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList className="flex w-full">
                        <TabsTrigger
                            value="overview"
                            className="flex-none px-3 py-2 text-center text-xs whitespace-nowrap sm:text-sm md:text-base"
                        >
                            Overview
                        </TabsTrigger>
                        <TabsTrigger
                            value="family"
                            className="flex-none px-3 py-2 text-center text-xs whitespace-nowrap sm:text-sm md:text-base"
                        >
                            Family Details
                        </TabsTrigger>
                        <TabsTrigger
                            value="documents"
                            className="flex-none px-3 py-2 text-center text-xs whitespace-nowrap sm:text-sm md:text-base"
                        >
                            Documents ({(intake.documents ?? []).length})
                        </TabsTrigger>
                        <TabsTrigger
                            value="history"
                            className="hidden flex-none px-3 py-2 text-center text-xs whitespace-nowrap sm:text-sm md:block md:text-base"
                        >
                            History
                        </TabsTrigger>
                        <TabsTrigger
                            value="notes"
                            className="flex-none px-3 py-2 text-center text-xs whitespace-nowrap sm:text-sm md:text-base"
                        >
                            Notes
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <OverviewTab intake={intake} />
                    </TabsContent>
                    <TabsContent value="family">
                        <FamilyTab intake={intake} />
                    </TabsContent>
                    <TabsContent value="documents">
                        <DocumentsTab intake={intake} />
                    </TabsContent>
                    <TabsContent value="history">
                        <HistoryTab intake={intake} />
                    </TabsContent>
                    <TabsContent value="notes">
                        <NotesTab intake={intake} />
                    </TabsContent>
                </Tabs>
            </div>

            <UpdateStatusModal
                intake={intake}
                therapists={therapists}
                targetStatus={targetStatus}
                isOpen={targetStatus !== ''}
                onClose={() => setTargetStatus('')}
            />

            <IntakePdfViewModal
                intake={intake}
                isOpen={pdfOpen}
                onClose={() => setPdfOpen(false)}
            />

            <ApproveIntakeDialog
                intakeId={intake.id}
                childName={`${intake.child_first_name} ${intake.child_last_name}`}
                therapists={therapists}
                isOpen={approveOpen}
                onClose={() => setApproveOpen(false)}
            />

            <DeleteIntakeModal
                intakeId={intake.id}
                isOpen={deleteOpen}
                onClose={() => setDeleteOpen(false)}
            />
        </>
    );
}

function SummaryCard({
    label,
    tone,
    icon,
    children,
}: {
    label: string;
    tone: string;
    icon: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <Card className="p-6">
            <div className="flex items-center gap-3">
                <div className={`rounded-full p-3 ${tone}`}>{icon}</div>
                <div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    {children}
                </div>
            </div>
        </Card>
    );
}

AdminIntakeShow.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
