import { Head, router } from '@inertiajs/react';
import {
    AlertCircle,
    AlertTriangle,
    Calendar as CalendarIcon,
    CheckCircle2,
    Clock,
    Eye,
    MapPin,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import DisputeSessionModal from '@/components/client/dispute-session-modal';
import SessionDetailModal from '@/components/client/session-detail-modal';
import VerifySessionModal from '@/components/client/verify-session-modal';
import {
    SessionServiceTags,
    SessionStatusBadge,
} from '@/components/sessions/badges';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarComponent } from '@/components/ui/calendar';
import { Card } from '@/components/ui/card';
import ClientLayout from '@/layouts/client-layout';
import {
    formatScheduledTime,
    getInitials,
    toScheduledDisplayDate,
} from '@/lib/helpers';
import type { ScheduleSession, ScheduleSessionStatus } from '@/types/session';

interface ClientCalendarProps {
    sessions: ScheduleSession[];
}

type StatusFilter = 'all' | ScheduleSessionStatus;

const FILTERS: { id: StatusFilter; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'scheduled', label: 'Scheduled' },
    { id: 'pending', label: 'Pending' },
    { id: 'confirmed', label: 'Verified' },
    { id: 'disputed', label: 'Disputed' },
    { id: 'cancelled', label: 'Cancelled' },
];

/** `start_time`/`end_time` are real UTC instants (when the session actually
 * started/ended) — unlike `scheduled_start`/`scheduled_end`, local-time
 * formatting is correct here. */
function formatActualTime(value: string): string {
    return new Date(value).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function ClientCalendar({ sessions }: ClientCalendarProps) {
    const [date, setDate] = useState<Date | undefined>(new Date());
    const [activeFilter, setActiveFilter] = useState<StatusFilter>('all');
    const [detailSession, setDetailSession] = useState<ScheduleSession | null>(
        null,
    );
    const [verifySession, setVerifySession] = useState<ScheduleSession | null>(
        null,
    );
    const [disputeSession, setDisputeSession] =
        useState<ScheduleSession | null>(null);
    const [processing, setProcessing] = useState(false);

    const sessionsForSelectedDate = useMemo(() => {
        if (!date) {
            return [];
        }

        return sessions.filter(
            (session) =>
                toScheduledDisplayDate(
                    session.scheduled_start,
                ).toDateString() === date.toDateString(),
        );
    }, [sessions, date]);

    const filteredSessions =
        activeFilter === 'all'
            ? sessionsForSelectedDate
            : sessionsForSelectedDate.filter(
                  (session) => session.status === activeFilter,
              );

    const counts = {
        pending: sessionsForSelectedDate.filter(
            (session) => session.status === 'pending',
        ).length,
        verified: sessionsForSelectedDate.filter(
            (session) => session.status === 'confirmed',
        ).length,
        disputed: sessionsForSelectedDate.filter(
            (session) => session.status === 'disputed',
        ).length,
    };

    const handleVerify = () => {
        if (!verifySession) {
            return;
        }

        setProcessing(true);
        router.post(
            `/client/sessions/${verifySession.id}/verify`,
            {},
            {
                onFinish: () => setProcessing(false),
                onSuccess: () => setVerifySession(null),
            },
        );
    };

    const handleDispute = (reason: string) => {
        if (!disputeSession) {
            return;
        }

        setProcessing(true);
        router.post(
            `/client/sessions/${disputeSession.id}/dispute`,
            { dispute_reason: reason },
            {
                onFinish: () => setProcessing(false),
                onSuccess: () => setDisputeSession(null),
            },
        );
    };

    return (
        <>
            <Head title="My Calendar" />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-3">
                    <CalendarIcon className="h-8 w-8 text-primary" />
                    <div>
                        <h1 className="text-3xl font-bold">Calendar</h1>
                        <p className="text-muted-foreground">
                            View and verify your therapy sessions
                        </p>
                    </div>
                </div>

                {counts.disputed > 0 && (
                    <Alert className="border-red-200 bg-red-50">
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                        <AlertDescription className="text-red-800">
                            {counts.disputed} disputed session(s) under review
                        </AlertDescription>
                    </Alert>
                )}

                {counts.pending > 0 && (
                    <Alert className="border-yellow-200 bg-yellow-50">
                        <AlertCircle className="h-4 w-4 text-yellow-600" />
                        <AlertDescription className="text-yellow-800">
                            {counts.pending} session(s) pending your
                            verification
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-col gap-4 lg:flex-row lg:gap-6">
                    <Card className="mx-auto max-w-full p-4 sm:p-6 lg:mx-0 lg:max-w-[325px]">
                        <h3 className="mb-2 font-semibold">Calendar</h3>
                        <p className="mb-4 text-sm text-muted-foreground">
                            Select a date to view sessions
                        </p>

                        <CalendarComponent
                            mode="single"
                            selected={date}
                            onSelect={setDate}
                        />

                        <div className="mt-6 space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Pending
                                </span>
                                <Badge className="bg-yellow-500/10 text-yellow-600">
                                    {counts.pending}
                                </Badge>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Verified
                                </span>
                                <Badge className="bg-green-500/10 text-green-600">
                                    {counts.verified}
                                </Badge>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Disputed
                                </span>
                                <Badge className="bg-red-500/10 text-red-600">
                                    {counts.disputed}
                                </Badge>
                            </div>
                        </div>
                    </Card>

                    <Card className="flex-1 p-6">
                        <div className="mb-5 border-b pb-5">
                            <h3 className="text-lg font-semibold">Sessions</h3>
                            <p className="text-muted-foreground">
                                {date?.toLocaleDateString('en-US', {
                                    weekday: 'long',
                                    month: 'long',
                                    day: 'numeric',
                                })}
                            </p>
                        </div>

                        <div className="mb-6 flex flex-wrap gap-2">
                            {FILTERS.map((filter) => (
                                <Button
                                    key={filter.id}
                                    size="sm"
                                    variant={
                                        activeFilter === filter.id
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className="rounded px-4"
                                    onClick={() => setActiveFilter(filter.id)}
                                >
                                    {filter.label}
                                </Button>
                            ))}
                        </div>

                        {filteredSessions.length === 0 ? (
                            <div className="flex flex-col items-center py-12 text-center">
                                <CalendarIcon className="mb-3 h-10 w-10 text-muted-foreground" />
                                <p className="font-medium">
                                    No sessions on this date
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Try selecting another day
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {filteredSessions.map((session) => {
                                    const therapistName = session.therapist
                                        ? `${session.therapist.first_name} ${session.therapist.last_name}`
                                        : 'Therapist';

                                    return (
                                        <div
                                            key={session.id}
                                            className="space-y-3 rounded-lg border p-4"
                                        >
                                            <div className="flex justify-between">
                                                <div className="flex gap-3">
                                                    <Avatar>
                                                        <AvatarFallback>
                                                            {getInitials(
                                                                therapistName,
                                                            )}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p className="font-medium">
                                                            {therapistName}
                                                        </p>
                                                        <SessionServiceTags
                                                            session={session}
                                                            className="mt-1"
                                                        />
                                                    </div>
                                                </div>
                                                <SessionStatusBadge
                                                    status={session.status}
                                                />
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <p className="text-xs text-muted-foreground">
                                                        Scheduled
                                                    </p>
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="h-3 w-3" />
                                                        {formatScheduledTime(
                                                            session.scheduled_start,
                                                        )}{' '}
                                                        –{' '}
                                                        {formatScheduledTime(
                                                            session.scheduled_end,
                                                        )}
                                                    </div>
                                                </div>
                                                {session.start_time &&
                                                    session.end_time && (
                                                        <div>
                                                            <p className="text-xs text-muted-foreground">
                                                                Actual
                                                            </p>
                                                            <div className="flex items-center gap-1">
                                                                <Clock className="h-3 w-3" />
                                                                {formatActualTime(
                                                                    session.start_time,
                                                                )}{' '}
                                                                –{' '}
                                                                {formatActualTime(
                                                                    session.end_time,
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}
                                            </div>

                                            {session.location && (
                                                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                    <MapPin className="h-3 w-3" />
                                                    {session.location}
                                                </div>
                                            )}

                                            {session.status === 'confirmed' && (
                                                <div className="flex items-center gap-2 pt-2 text-green-800">
                                                    <CheckCircle2 className="h-4 w-4" />
                                                    <p className="text-sm">
                                                        Visit Verified by you
                                                    </p>
                                                </div>
                                            )}

                                            {session.status === 'disputed' && (
                                                <div className="rounded border border-red-600 bg-red-200 p-4 text-red-800">
                                                    <div className="flex items-center gap-2">
                                                        <AlertCircle className="h-4 w-4" />
                                                        <p className="text-sm">
                                                            Session Disputed
                                                        </p>
                                                    </div>
                                                    <p className="text-xs">
                                                        This session is under
                                                        review by our team. You
                                                        will be notified once
                                                        the review is complete.
                                                    </p>
                                                </div>
                                            )}

                                            <div className="flex gap-2 pt-2">
                                                <Button
                                                    className="min-w-[200px] rounded bg-primary"
                                                    onClick={() =>
                                                        setDetailSession(
                                                            session,
                                                        )
                                                    }
                                                >
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    View
                                                </Button>
                                                {session.status ===
                                                    'pending' && (
                                                    <>
                                                        <Button
                                                            className="flex-1 rounded bg-cyan-700 hover:bg-cyan-800"
                                                            onClick={() =>
                                                                setVerifySession(
                                                                    session,
                                                                )
                                                            }
                                                        >
                                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                                            Verify
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            className="flex-1 rounded border-red-500 text-red-600"
                                                            onClick={() =>
                                                                setDisputeSession(
                                                                    session,
                                                                )
                                                            }
                                                        >
                                                            <XCircle className="mr-2 h-4 w-4" />
                                                            Dispute
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </Card>
                </div>
            </div>

            <VerifySessionModal
                session={verifySession}
                isOpen={verifySession !== null}
                processing={processing}
                onClose={() => setVerifySession(null)}
                onConfirm={handleVerify}
            />

            <DisputeSessionModal
                session={disputeSession}
                isOpen={disputeSession !== null}
                processing={processing}
                onClose={() => setDisputeSession(null)}
                onConfirm={handleDispute}
            />

            <SessionDetailModal
                session={detailSession}
                isOpen={detailSession !== null}
                onClose={() => setDetailSession(null)}
            />
        </>
    );
}

ClientCalendar.layout = (page: React.ReactNode) => (
    <ClientLayout>{page}</ClientLayout>
);
