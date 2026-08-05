import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Calendar,
    CheckCircle,
    CheckCircle2,
    Clock,
    Dot,
    Play,
    Users,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useAuthUser } from '@/hooks/use-auth-user';
import TherapistLayout from '@/layouts/therapist-layout';
import { formatScheduledTime, timeAgo } from '@/lib/helpers';
import type { Intake } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

interface TherapistDashboardProps {
    stats: {
        todays_sessions: number;
        completed_this_week: number;
        active_clients: number;
        hours_this_month: number;
    };
    pendingReviews: Intake[];
    todaysSessions: ScheduleSession[];
}

/**
 * The intake may request more services than were assigned to this
 * therapist — only show the ones actually assigned to them. A null-service
 * review means the whole intake was assigned as one (legacy/no-services
 * intakes), so every requested service applies in that case.
 */
function assignedServices(intake: Intake): string[] {
    const reviews = intake.therapist_reviews ?? [];

    if (
        reviews.length === 0 ||
        reviews.some((review) => review.service === null)
    ) {
        return intake.services_needed ?? [];
    }

    return reviews.map((review) => review.service as string);
}

export default function TherapistDashboard({
    stats,
    pendingReviews,
    todaysSessions,
}: TherapistDashboardProps) {
    const user = useAuthUser();
    const { activeSession } = usePage().props;
    const [currentTime, setCurrentTime] = useState(new Date());
    const [startingId, setStartingId] = useState<number | null>(null);

    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date()), 1000);

        return () => clearInterval(timer);
    }, []);

    const completedToday = todaysSessions.filter(
        (session) =>
            session.status !== 'scheduled' && session.status !== 'inprogress',
    ).length;

    const handleStart = (session: ScheduleSession) => {
        setStartingId(session.id);
        router.post(
            `/therapist/sessions/${session.id}/start`,
            {},
            {
                onFinish: () => setStartingId(null),
            },
        );
    };

    return (
        <>
            <Head title="Dashboard" />

            <div className="p-3 md:p-8">
                <div className="mb-6">
                    <h1 className="text-3xl font-bold">
                        Welcome back, {user.first_name} {user.last_name}!
                    </h1>
                    <p className="text-muted-foreground">
                        {currentTime.toLocaleDateString('en-US', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        })}
                    </p>
                </div>

                <Card className="mb-8 bg-gradient-to-br from-primary/90 to-primary p-8 text-primary-foreground">
                    <div className="mb-2 flex items-center justify-center gap-2">
                        <Clock className="h-5 w-5" />
                        <p className="text-sm opacity-90">Current Time</p>
                    </div>
                    <div className="text-center text-5xl font-bold">
                        {currentTime.toLocaleTimeString('en-US')}
                    </div>
                </Card>

                <div className="mb-8 grid gap-6 md:grid-cols-4">
                    <Card className="p-6">
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Today's Sessions
                            </p>
                            <Calendar className="h-5 w-5 text-primary" />
                        </div>
                        <p className="text-3xl font-bold">
                            {stats.todays_sessions}
                        </p>
                    </Card>

                    <Card className="p-6">
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Completed This Week
                            </p>
                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                        </div>
                        <p className="text-3xl font-bold">
                            {stats.completed_this_week}
                        </p>
                    </Card>

                    <Card className="p-6">
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Active Clients
                            </p>
                            <Users className="h-5 w-5 text-purple-500" />
                        </div>
                        <p className="text-3xl font-bold">
                            {stats.active_clients}
                        </p>
                    </Card>

                    <Card className="p-6">
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Hours This Month
                            </p>
                            <Clock className="h-5 w-5 text-orange-500" />
                        </div>
                        <p className="text-3xl font-bold">
                            {stats.hours_this_month}
                        </p>
                    </Card>
                </div>

                {pendingReviews.length > 0 && (
                    <Card className="mb-5 border-primary bg-secondary-orange/5 p-6">
                        <div className="mb-6 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Bell className="h-5 w-5 text-primary" />
                                <div>
                                    <h2 className="text-xl font-semibold">
                                        Pending Intake Reviews
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        New client intakes assigned to you for
                                        review and acceptance
                                    </p>
                                </div>
                            </div>
                            <Badge className="rounded-[5px] bg-orange-100 text-orange-800">
                                Action Required
                            </Badge>
                        </div>

                        <div className="space-y-4">
                            {pendingReviews.map((intake) => (
                                <Card key={intake.id} className="p-4">
                                    <p className="font-semibold">
                                        {intake.child_first_name}{' '}
                                        {intake.child_last_name} (Age{' '}
                                        {intake.age})
                                    </p>
                                    <p className="flex flex-row items-center text-sm text-muted-foreground">
                                        INT-00{intake.id} <Dot /> Assigned{' '}
                                        {timeAgo(
                                            intake.therapist_review_history?.[0]
                                                ?.decided_at ??
                                                intake.created_at,
                                        )}
                                    </p>

                                    <div className="mt-3 grid grid-cols-2 gap-5 md:grid-cols-4">
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Services Requested
                                            </p>
                                            {assignedServices(intake).length >
                                            0 ? (
                                                assignedServices(intake).map(
                                                    (service) => (
                                                        <Badge
                                                            key={service}
                                                            className="rounded-[5px] bg-gray-500 text-white"
                                                        >
                                                            {service}
                                                        </Badge>
                                                    ),
                                                )
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No services needed
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Diagnosis
                                            </p>
                                            {intake.diagnosis &&
                                            intake.diagnosis.length > 0 ? (
                                                intake.diagnosis.map((diag) => (
                                                    <Badge
                                                        key={diag}
                                                        className="rounded-[5px] border border-gray-300 bg-white text-charcoal-gray"
                                                    >
                                                        {diag}
                                                    </Badge>
                                                ))
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No diagnosis
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Availability
                                            </p>
                                            {intake.available_days &&
                                            intake.available_days.length > 0 ? (
                                                <p className="text-sm break-words text-muted-foreground">
                                                    {intake.available_days.join(
                                                        ', ',
                                                    )}
                                                </p>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No available days
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Preferred Times
                                            </p>
                                            {intake.preferred_times &&
                                            intake.preferred_times.length >
                                                0 ? (
                                                <p className="text-sm break-words text-muted-foreground">
                                                    {intake.preferred_times.join(
                                                        ', ',
                                                    )}
                                                </p>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No preferred times
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-3 flex justify-end gap-2">
                                        <Button
                                            className="rounded-[5px]"
                                            asChild
                                        >
                                            <Link
                                                href={`/therapist/intake/${intake.id}`}
                                            >
                                                <CheckCircle /> Review & Decide
                                            </Link>
                                        </Button>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </Card>
                )}

                <Card className="mt-5 p-6">
                    <div className="mb-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Calendar className="h-5 w-5 text-primary" />
                                <div>
                                    <h2 className="text-xl font-semibold">
                                        Today's Schedule
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {todaysSessions.length} session
                                        {todaysSessions.length !== 1
                                            ? 's'
                                            : ''}{' '}
                                        scheduled
                                    </p>
                                </div>
                            </div>

                            <div className="text-right">
                                <p className="text-sm text-muted-foreground">
                                    Progress
                                </p>
                                <p className="text-lg font-semibold">
                                    {completedToday}/{todaysSessions.length}
                                </p>
                            </div>
                        </div>

                        <div className="h-2 w-full rounded-full bg-secondary">
                            <div
                                className="h-2 rounded-full bg-primary transition-all duration-300"
                                style={{
                                    width: `${
                                        todaysSessions.length > 0
                                            ? (completedToday /
                                                  todaysSessions.length) *
                                              100
                                            : 0
                                    }%`,
                                }}
                            />
                        </div>
                    </div>

                    <div className="space-y-4">
                        {todaysSessions.map((session) => {
                            const startTime = formatScheduledTime(
                                session.scheduled_start,
                            );
                            const intake = session.client?.original_intake;

                            return (
                                <Card
                                    key={session.id}
                                    className="flex items-center justify-between p-4"
                                >
                                    <div className="flex items-center gap-4">
                                        <div className="min-w-[80px] text-center">
                                            <p className="text-xs text-muted-foreground">
                                                Time
                                            </p>
                                            <p className="font-semibold">
                                                {startTime}
                                            </p>
                                        </div>

                                        <div className="h-10 w-px bg-border" />

                                        <div>
                                            <p className="font-semibold">
                                                {intake
                                                    ? `${intake.child_first_name} ${intake.child_last_name}`
                                                    : 'Client'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {session.service?.name ||
                                                    session.service_name ||
                                                    'Service'}
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        onClick={() => handleStart(session)}
                                        disabled={
                                            !!activeSession ||
                                            session.status !== 'scheduled' ||
                                            startingId === session.id
                                        }
                                    >
                                        <Play className="mr-2 h-4 w-4" />
                                        {session.status !== 'scheduled'
                                            ? 'Session Finished'
                                            : 'Start Session'}
                                    </Button>
                                </Card>
                            );
                        })}

                        {todaysSessions.length === 0 && (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No sessions scheduled for today.
                            </p>
                        )}
                    </div>
                </Card>
            </div>
        </>
    );
}

TherapistDashboard.layout = (page: React.ReactElement) => (
    <TherapistLayout>{page}</TherapistLayout>
);
