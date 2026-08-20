import {
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    format,
    isSameDay,
    isSameMonth,
    startOfMonth,
    startOfWeek,
} from 'date-fns';
import { useState } from 'react';

import SessionListModal from '@/components/sessions/session-list-modal';
import { Card, CardContent } from '@/components/ui/card';
import { toScheduledDisplayDate } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** Reference: cats-frontend/src/components/sessions/SessionsMonthlyView.tsx */
export default function SessionsMonthlyView({
    sessions,
    selectedDate,
    onSelectSession,
}: {
    sessions: ScheduleSession[];
    selectedDate: Date;
    onSelectSession: (session: ScheduleSession) => void;
}) {
    const [listModalDate, setListModalDate] = useState<Date | null>(null);

    const days = eachDayOfInterval({
        start: startOfWeek(startOfMonth(selectedDate)),
        end: endOfWeek(endOfMonth(selectedDate)),
    });

    const sessionsForDay = (day: Date) =>
        sessions
            .filter((session) =>
                isSameDay(toScheduledDisplayDate(session.scheduled_start), day),
            )
            .sort(
                (a, b) =>
                    new Date(a.scheduled_start).getTime() -
                    new Date(b.scheduled_start).getTime(),
            );

    const handleDayClick = (day: Date, daySessions: ScheduleSession[]) => {
        if (daySessions.length === 0) {
            return;
        }

        if (daySessions.length === 1) {
            onSelectSession(daySessions[0]);

            return;
        }

        setListModalDate(day);
    };

    return (
        <>
            <Card className="rounded-[10px]">
                <CardContent className="p-3">
                    <div className="grid grid-cols-7 gap-1 text-center text-xs font-medium text-muted-foreground">
                        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(
                            (label) => (
                                <div key={label} className="py-2">
                                    {label}
                                </div>
                            ),
                        )}
                    </div>

                    <div className="grid grid-cols-7 gap-1">
                        {days.map((day) => {
                            const daySessions = sessionsForDay(day);
                            const inMonth = isSameMonth(day, selectedDate);

                            return (
                                <button
                                    key={day.toISOString()}
                                    type="button"
                                    onClick={() =>
                                        handleDayClick(day, daySessions)
                                    }
                                    className={`min-h-[80px] rounded-[5px] border p-2 text-left align-top ${
                                        inMonth ? '' : 'opacity-40'
                                    } ${isSameDay(day, new Date()) ? 'border-primary' : ''}`}
                                >
                                    <p className="text-xs">
                                        {format(day, 'd')}
                                    </p>
                                    {daySessions.length > 0 && (
                                        <p className="mt-1 rounded-[5px] bg-secondary-orange/10 px-1 text-xs text-primary">
                                            {daySessions.length} session
                                            {daySessions.length > 1 ? 's' : ''}
                                        </p>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            <SessionListModal
                date={listModalDate}
                sessions={listModalDate ? sessionsForDay(listModalDate) : []}
                isOpen={listModalDate !== null}
                onClose={() => setListModalDate(null)}
                onSelectSession={(session) => {
                    setListModalDate(null);
                    onSelectSession(session);
                }}
            />
        </>
    );
}
