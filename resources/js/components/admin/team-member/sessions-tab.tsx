import { Calendar } from 'lucide-react';

import { SessionStatusBadge } from '@/components/sessions/badges';
import { Card, CardContent } from '@/components/ui/card';
import { formatScheduledDateTime } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** Reference: cats-frontend/src/pages/admin/teamMemberTabs/SessionsTab.tsx */
export default function SessionsTab({
    sessions,
}: {
    sessions: ScheduleSession[];
}) {
    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            {sessions.length > 0 ? (
                sessions.map((session) => (
                    <Card key={session.id} className="rounded-[10px]">
                        <CardContent className="flex flex-row items-center justify-between gap-3 p-5">
                            <div>
                                <p>
                                    {session.client?.original_intake
                                        ? `${session.client.original_intake.child_first_name} ${session.client.original_intake.child_last_name}`
                                        : 'Client'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {formatScheduledDateTime(
                                        session.scheduled_start,
                                    )}
                                </p>
                            </div>
                            <SessionStatusBadge status={session.status} />
                        </CardContent>
                    </Card>
                ))
            ) : (
                <Card className="rounded-[10px]">
                    <CardContent className="flex flex-col items-center gap-3 p-10 text-center text-muted-foreground">
                        <Calendar className="h-8 w-8" />
                        No recent sessions
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
