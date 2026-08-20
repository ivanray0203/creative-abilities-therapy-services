import { isSameDay } from 'date-fns';
import { Clock, MapPin } from 'lucide-react';

import {
    SessionServiceTags,
    SessionStatusBadge,
} from '@/components/sessions/badges';
import { Card, CardContent } from '@/components/ui/card';
import { formatScheduledTime, toScheduledDisplayDate } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** Reference: cats-frontend/src/components/sessions/SessionsDailyView.tsx */
export default function SessionsDailyView({
    sessions,
    selectedDate,
    viewMode,
    onSelectSession,
}: {
    sessions: ScheduleSession[];
    selectedDate: Date;
    viewMode: 'card' | 'table';
    onSelectSession: (session: ScheduleSession) => void;
}) {
    const dayServices = sessions
        .filter((session) =>
            isSameDay(
                toScheduledDisplayDate(session.scheduled_start),
                selectedDate,
            ),
        )
        .sort(
            (a, b) =>
                new Date(a.scheduled_start).getTime() -
                new Date(b.scheduled_start).getTime(),
        );

    if (dayServices.length === 0) {
        return (
            <Card className="rounded-[10px]">
                <CardContent className="p-10 text-center text-muted-foreground">
                    No sessions scheduled for this day
                </CardContent>
            </Card>
        );
    }

    if (viewMode === 'table') {
        return (
            <Card className="rounded-[10px]">
                <CardContent className="overflow-x-auto p-5">
                    <table className="w-full">
                        <thead className="border-b text-left text-sm text-muted-foreground">
                            <tr>
                                <th className="pb-2">Time</th>
                                <th className="pb-2">Client</th>
                                <th className="pb-2">Therapist</th>
                                <th className="pb-2">Service</th>
                                <th className="pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {dayServices.map((session) => (
                                <tr
                                    key={session.id}
                                    className="cursor-pointer border-b last:border-0 hover:bg-charcoal-gray/5"
                                    onClick={() => onSelectSession(session)}
                                >
                                    <td className="py-3">
                                        {formatScheduledTime(
                                            session.scheduled_start,
                                        )}
                                    </td>
                                    <td className="py-3">
                                        {session.client?.original_intake
                                            ? `${session.client.original_intake.child_first_name} ${session.client.original_intake.child_last_name}`
                                            : '-'}
                                    </td>
                                    <td className="py-3">
                                        {session.therapist
                                            ? `${session.therapist.first_name} ${session.therapist.last_name}`
                                            : '-'}
                                    </td>
                                    <td className="py-3">
                                        <SessionServiceTags session={session} />
                                    </td>
                                    <td className="py-3">
                                        <SessionStatusBadge
                                            status={session.status}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            {dayServices.map((session) => (
                <Card
                    key={session.id}
                    className="cursor-pointer rounded-[10px] hover:shadow-md"
                    onClick={() => onSelectSession(session)}
                >
                    <CardContent className="p-5">
                        <div className="flex items-start justify-between">
                            <p className="font-medium">
                                {session.client?.original_intake
                                    ? `${session.client.original_intake.child_first_name} ${session.client.original_intake.child_last_name}`
                                    : 'Client'}
                            </p>
                            <SessionStatusBadge status={session.status} />
                        </div>
                        <SessionServiceTags
                            session={session}
                            className="mt-2"
                        />
                        <div className="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock className="h-4 w-4" />
                            {formatScheduledTime(session.scheduled_start)}
                        </div>
                        <div className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin className="h-4 w-4" />
                            {session.location || '-'}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
