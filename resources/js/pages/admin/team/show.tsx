import { Head, Link } from '@inertiajs/react';
import { Edit, Settings, Star, TrendingUp } from 'lucide-react';
import { useState } from 'react';

import ManageTeamAccessModal from '@/components/admin/manage-team-access-modal';
import { EmploymentStatusBadge } from '@/components/admin/team-member/badges';
import ClientsTab from '@/components/admin/team-member/clients-tab';
import DocumentsTab from '@/components/admin/team-member/documents-tab';
import OverviewTab from '@/components/admin/team-member/overview-tab';
import ScheduleTab from '@/components/admin/team-member/schedule-tab';
import SessionsTab from '@/components/admin/team-member/sessions-tab';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import { getInitials } from '@/lib/helpers';
import type { TeamMemberShowProps } from '@/types/team-member';

/** Admin team member detail page, ported from cats-frontend/src/pages/admin/TeamMemberDetailPage.tsx. */
export default function AdminTeamShow({
    teamMember,
    caseload,
    clients,
    recentSessions,
    documents,
    missingDocuments,
}: TeamMemberShowProps) {
    const [accessModalOpen, setAccessModalOpen] = useState(false);

    return (
        <>
            <Head
                title={`${teamMember.user?.first_name ?? ''} ${teamMember.user?.last_name ?? ''}`}
            />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <Avatar className="h-16 w-16 bg-primary">
                            <AvatarFallback className="bg-primary text-lg text-primary-foreground">
                                {getInitials(
                                    `${teamMember.user?.first_name ?? ''} ${teamMember.user?.last_name ?? ''}`,
                                )}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 className="text-xl font-bold sm:text-2xl">
                                {teamMember.user?.first_name}{' '}
                                {teamMember.user?.last_name}
                            </h1>
                            <div className="mt-1 flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    {teamMember.position}
                                </span>
                                <EmploymentStatusBadge
                                    status={teamMember.employment_status}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => setAccessModalOpen(true)}
                        >
                            <Settings /> Manage Access and Status
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            asChild
                        >
                            <Link href={`/admin/team/edit/${teamMember.id}`}>
                                <Edit /> Edit Profile
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Current Caseload
                        </p>
                        <p className="text-2xl font-bold">
                            {caseload} / {teamMember.maximum_caseload}
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="text-xs text-muted-foreground">
                            Sessions Completed
                        </p>
                        <p className="text-lg font-medium text-muted-foreground">
                            Coming Soon
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Star className="h-3 w-3" /> Avg. Rating
                        </p>
                        <p className="text-lg font-medium text-muted-foreground">
                            Coming Soon
                        </p>
                    </Card>
                    <Card className="p-4">
                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingUp className="h-3 w-3" /> Documentation
                            Rate
                        </p>
                        <p className="text-lg font-medium text-muted-foreground">
                            Coming Soon
                        </p>
                    </Card>
                </div>

                <Tabs defaultValue="overview">
                    <TabsList className="flex w-full flex-wrap justify-start">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="clients">Clients</TabsTrigger>
                        <TabsTrigger
                            value="sessions"
                            className="hidden sm:block"
                        >
                            Recent Sessions
                        </TabsTrigger>
                        <TabsTrigger value="documents">
                            Documents ({documents.length})
                        </TabsTrigger>
                        <TabsTrigger value="schedule">Schedule</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <OverviewTab teamMember={teamMember} />
                    </TabsContent>
                    <TabsContent value="clients">
                        <ClientsTab clients={clients} />
                    </TabsContent>
                    <TabsContent value="sessions">
                        <SessionsTab sessions={recentSessions} />
                    </TabsContent>
                    <TabsContent value="documents">
                        <DocumentsTab
                            teamMember={teamMember}
                            documents={documents}
                            missingDocuments={missingDocuments}
                        />
                    </TabsContent>
                    <TabsContent value="schedule">
                        <ScheduleTab teamMember={teamMember} />
                    </TabsContent>
                </Tabs>
            </div>

            <ManageTeamAccessModal
                teamMember={teamMember}
                isOpen={accessModalOpen}
                onClose={() => setAccessModalOpen(false)}
            />
        </>
    );
}

AdminTeamShow.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
