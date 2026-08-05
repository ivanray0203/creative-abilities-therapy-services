import {
    eachDayOfInterval,
    endOfWeek,
    format,
    isSameDay,
    startOfWeek,
} from 'date-fns';

import { Card, CardContent } from '@/components/ui/card';
import { toScheduledDisplayDate } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** Reference: cats-frontend/src/components/sessions/SessionsWeeklyView.tsx */
export default function SessionsWeeklyView({
    sessions,
    selectedDate,
    onSelectSession,
}: {
    sessions: ScheduleSession[];
    selectedDate: Date;
    onSelectSession: (session: ScheduleSession) => void;
}) {
    const days = eachDayOfInterval({
        start: startOfWeek(selectedDate),
        end: endOfWeek(selectedDate),
    });

    return (
        <div className="grid grid-cols-1 gap-3 md:grid-cols-7">
            {days.map((day) => {
                const daySessions = sessions
                    .filter((session) =>
                        isSameDay(
                            toScheduledDisplayDate(session.scheduled_start),
                            day,
                        ),
                    )
                    .sort(
                        (a, b) =>
                            new Date(a.scheduled_start).getTime() -
                            new Date(b.scheduled_start).getTime(),
                    );

                return (
                    <Card key={day.toISOString()} className="rounded-[10px]">
                        <CardContent className="p-3">
                            <p className="text-center text-xs font-medium text-muted-foreground">
                                {format(day, 'EEE')}
                            </p>
                            <p
                                className={`text-center text-lg font-bold ${isSameDay(day, new Date()) ? 'text-primary' : ''}`}
                            >
                                {format(day, 'd')}
                            </p>

                            <div className="mt-3 grid grid-cols-1 gap-2">
                                {daySessions.length === 0 ? (
                                    <p className="text-center text-xs text-muted-foreground">
                                        —
                                    </p>
                                ) : (
                                    daySessions.map((session) => (
                                        <button
                                            key={session.id}
                                            type="button"
                                            onClick={() =>
                                                onSelectSession(session)
                                            }
                                            className="rounded-[5px] bg-secondary-orange/10 p-2 text-left text-xs hover:bg-secondary-orange/20"
                                        >
                                            <p className="font-medium">
                                                {format(
                                                    toScheduledDisplayDate(
                                                        session.scheduled_start,
                                                    ),
                                                    'h:mm a',
                                                )}
                                            </p>
                                            <p className="truncate text-muted-foreground">
                                                {session.client?.original_intake
                                                    ? `${session.client.original_intake.child_first_name} ${session.client.original_intake.child_last_name}`
                                                    : 'Client'}
                                            </p>
                                        </button>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
