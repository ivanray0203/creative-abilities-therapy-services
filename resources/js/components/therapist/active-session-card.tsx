import { router } from '@inertiajs/react';
import { Square } from 'lucide-react';
import { useEffect, useState } from 'react';

import { SessionStatusBadge } from '@/components/sessions/badges';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { formatScheduledTime } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** `start_time` is a real UTC instant (when the session actually started) —
 * unlike `scheduled_start`, local-time formatting is correct here. */
function formatActualTime(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatElapsed(startTime: string): string {
    const diff = Date.now() - new Date(startTime).getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    return [hours, minutes, seconds]
        .map((part) => part.toString().padStart(2, '0'))
        .join(':');
}

/** Rendered inside TherapistLayout whenever the `activeSession` shared prop is present. */
export default function ActiveSessionCard({
    activeSession,
}: {
    activeSession: ScheduleSession;
}) {
    const [elapsed, setElapsed] = useState('00:00:00');
    const [notes, setNotes] = useState(activeSession.notes ?? '');
    const [ending, setEnding] = useState(false);

    useEffect(() => {
        if (!activeSession.start_time) {
            return;
        }

        setElapsed(formatElapsed(activeSession.start_time));
        const timer = setInterval(() => {
            setElapsed(formatElapsed(activeSession.start_time as string));
        }, 1000);

        return () => clearInterval(timer);
    }, [activeSession.start_time]);

    const intake = activeSession.client?.original_intake;
    const clientName = intake
        ? `${intake.child_first_name} ${intake.child_last_name}`
        : 'Client';
    const serviceName =
        activeSession.service?.name || activeSession.service_name || 'Service';

    const handleEnd = () => {
        setEnding(true);
        router.post(
            `/therapist/sessions/${activeSession.id}/end`,
            { notes },
            { onFinish: () => setEnding(false) },
        );
    };

    return (
        <Card className="space-y-4 border-2 border-primary p-6">
            <div className="flex items-start justify-between">
                <div>
                    <div className="mb-1 flex items-center gap-2">
                        <span className="h-3 w-3 animate-pulse rounded-full bg-red-500" />
                        <p className="font-semibold">Active Session</p>
                    </div>
                    <p className="text-muted-foreground">
                        Client: {clientName} — Service: {serviceName}
                    </p>
                </div>
                <SessionStatusBadge status={activeSession.status} />
            </div>

            <div className="grid grid-cols-3 gap-4 text-sm text-muted-foreground">
                <div>
                    <p className="text-xs">Scheduled Time</p>
                    <p className="text-lg font-medium">
                        {formatScheduledTime(activeSession.scheduled_start)}
                    </p>
                </div>
                <div>
                    <p className="text-xs">Start Time</p>
                    <p className="text-lg font-medium">
                        {formatActualTime(activeSession.start_time)}
                    </p>
                </div>
                <div>
                    <p className="text-xs">Elapsed Time</p>
                    <p className="text-lg font-medium text-primary">
                        {elapsed}
                    </p>
                </div>
            </div>

            <div className="flex flex-col gap-1">
                <label className="text-sm font-semibold">Session Notes</label>
                <Textarea
                    rows={3}
                    value={notes}
                    onChange={(event) => setNotes(event.target.value)}
                />
            </div>

            <Button
                variant="destructive"
                className="w-full rounded-[10px]"
                disabled={ending}
                onClick={handleEnd}
            >
                <Square className="h-4 w-4" /> End Session
            </Button>
        </Card>
    );
}
