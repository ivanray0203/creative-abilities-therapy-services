import { Head, Link, router } from '@inertiajs/react';
import { CalendarIcon, PlusIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import {
    SessionServiceTags,
    SessionStatusBadge,
} from '@/components/sessions/badges';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarComponent } from '@/components/ui/calendar';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TherapistLayout from '@/layouts/therapist-layout';
import { formatScheduledTime } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

interface TherapistCalendarProps {
    sessions: ScheduleSession[];
}

function formatTime(value: string | null): string {
    if (!value) {
        return '-';
    }

    return formatScheduledTime(value);
}

function SessionRow({
    session,
    showStartButton,
}: {
    session: ScheduleSession;
    showStartButton?: boolean;
}) {
    const [starting, setStarting] = useState(false);
    const intake = session.client?.original_intake;
    const clientName = intake
        ? `${intake.child_first_name} ${intake.child_last_name}`
        : 'Client';

    const handleStart = () => {
        setStarting(true);
        router.post(
            `/therapist/sessions/${session.id}/start`,
            {},
            { onFinish: () => setStarting(false) },
        );
    };

    return (
        <Card className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-4">
                <div className="min-w-[70px] text-center">
                    <p className="text-xs text-muted-foreground">Time</p>
                    <p className="font-semibold">
                        {formatTime(session.scheduled_start)}
                    </p>
                </div>
                <div className="h-10 w-px bg-border" />
                <div>
                    <p className="font-semibold">{clientName}</p>
                    <SessionServiceTags session={session} className="mt-1" />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <SessionStatusBadge status={session.status} />
                {showStartButton && (
                    <Button
                        size="sm"
                        className="rounded"
                        disabled={session.status !== 'scheduled' || starting}
                        onClick={handleStart}
                    >
                        {session.status !== 'scheduled'
                            ? 'Session Finished'
                            : 'Start Session'}
                    </Button>
                )}
            </div>
        </Card>
    );
}

function EmptyState({ label, hint }: { label: string; hint?: string }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-lg border border-muted/30 bg-muted/10 py-12 text-center">
            <CalendarIcon className="mb-3 h-12 w-12 text-muted-foreground" />
            <p className="text-sm font-medium text-muted-foreground sm:text-base">
                {label}
            </p>
            {hint && (
                <p className="mt-1 text-xs text-muted-foreground sm:text-sm">
                    {hint}
                </p>
            )}
        </div>
    );
}

export default function TherapistCalendar({
    sessions,
}: TherapistCalendarProps) {
    const [date, setDate] = useState<Date | undefined>(new Date());

    const sessionsForSelectedDate = useMemo(() => {
        if (!date) {
            return [];
        }

        return sessions.filter((session) => {
            const sessionDate = new Date(session.scheduled_start);

            return (
                sessionDate.getUTCFullYear() === date.getFullYear() &&
                sessionDate.getUTCMonth() === date.getMonth() &&
                sessionDate.getUTCDate() === date.getDate()
            );
        });
    }, [sessions, date]);

    const upcomingSessions = sessionsForSelectedDate.filter(
        (session) =>
            session.status === 'scheduled' || session.status === 'inprogress',
    );
    const pendingSessions = sessionsForSelectedDate.filter(
        (session) => session.status === 'pending',
    );
    const completedSessions = sessionsForSelectedDate.filter(
        (session) =>
            session.status === 'completed' || session.status === 'confirmed',
    );

    return (
        <>
            <Head title="My Calendar" />

            <div className="space-y-6 p-4 md:p-8">
                <div className="flex flex-row items-start justify-between">
                    <div>
                        <div className="mb-4 flex flex-col sm:mb-6 sm:flex-row sm:items-center sm:gap-2">
                            <CalendarIcon className="h-6 w-6 text-primary" />
                            <h1 className="mt-2 text-2xl font-bold sm:mt-0 sm:text-3xl">
                                My Calendar
                            </h1>
                        </div>
                        <p className="mb-6 text-sm text-muted-foreground sm:text-base">
                            View and manage your client appointments
                        </p>
                    </div>

                    <Button className="gap-2 rounded" asChild>
                        <Link href="/therapist/sessions/create">
                            <PlusIcon className="h-4 w-4" />
                            Schedule a session
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col gap-4 lg:flex-row lg:gap-6">
                    <Card className="mx-auto max-w-full p-4 sm:p-6 lg:mx-0 lg:max-w-[325px]">
                        <h3 className="mb-2 text-sm font-semibold sm:text-base">
                            Select Date
                        </h3>
                        <p className="mb-4 text-xs text-muted-foreground sm:text-sm">
                            View appointments for a specific day
                        </p>

                        <CalendarComponent
                            mode="single"
                            selected={date}
                            onSelect={setDate}
                        />

                        <div className="mt-4 space-y-2 text-xs sm:mt-6 sm:text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Total Sessions:
                                </span>
                                <span className="font-semibold">
                                    {sessionsForSelectedDate.length}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Upcoming:
                                </span>
                                <span className="font-semibold text-primary">
                                    {upcomingSessions.length}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Completed/Confirmed:
                                </span>
                                <span className="font-semibold text-green-600">
                                    {completedSessions.length}
                                </span>
                            </div>
                        </div>
                    </Card>

                    <div className="flex-1">
                        <Card className="p-4 sm:p-6">
                            <div className="mb-4 sm:mb-6">
                                <h3 className="text-base font-semibold sm:text-lg">
                                    Appointments for{' '}
                                    {date?.toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                </h3>
                                <p className="text-xs text-muted-foreground sm:text-sm">
                                    {sessionsForSelectedDate.length} sessions
                                    scheduled
                                </p>
                            </div>

                            <Tabs defaultValue="upcoming" className="w-full">
                                <TabsList className="grid w-full grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-0">
                                    <TabsTrigger value="upcoming">
                                        Upcoming ({upcomingSessions.length})
                                    </TabsTrigger>
                                    <TabsTrigger value="pending">
                                        Pending ({pendingSessions.length})
                                    </TabsTrigger>
                                    <TabsTrigger value="completed">
                                        Completed/Confirmed (
                                        {completedSessions.length})
                                    </TabsTrigger>
                                </TabsList>

                                <TabsContent
                                    value="upcoming"
                                    className="mt-4 space-y-4"
                                >
                                    {upcomingSessions.length > 0 ? (
                                        upcomingSessions.map((session) => (
                                            <SessionRow
                                                key={session.id}
                                                session={session}
                                                showStartButton
                                            />
                                        ))
                                    ) : (
                                        <EmptyState label="No upcoming sessions for this day" />
                                    )}
                                </TabsContent>

                                <TabsContent
                                    value="pending"
                                    className="mt-4 space-y-4"
                                >
                                    {pendingSessions.length > 0 ? (
                                        pendingSessions.map((session) => (
                                            <SessionRow
                                                key={session.id}
                                                session={session}
                                            />
                                        ))
                                    ) : (
                                        <EmptyState
                                            label="No pending sessions for this day"
                                            hint="Once you finish a session, it will appear here."
                                        />
                                    )}
                                </TabsContent>

                                <TabsContent
                                    value="completed"
                                    className="mt-4 space-y-4"
                                >
                                    {completedSessions.length > 0 ? (
                                        completedSessions.map((session) => (
                                            <SessionRow
                                                key={session.id}
                                                session={session}
                                            />
                                        ))
                                    ) : (
                                        <EmptyState
                                            label="No completed sessions for this day"
                                            hint="Once a session has been paid or confirmed, it will appear here."
                                        />
                                    )}
                                </TabsContent>
                            </Tabs>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

TherapistCalendar.layout = (page: React.ReactNode) => (
    <TherapistLayout>{page}</TherapistLayout>
);
