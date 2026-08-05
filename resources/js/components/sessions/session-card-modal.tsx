import { Link, router, usePage } from '@inertiajs/react';
import { Calendar, Clock, MapPin, Play, User, UserCog, X } from 'lucide-react';
import { useState } from 'react';

import { SessionStatusBadge } from '@/components/sessions/badges';
import CancelConfirmationModal from '@/components/sessions/cancel-confirmation-modal';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatScheduledDate, formatScheduledTime } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

const CANCELLABLE_STATUSES = ['scheduled', 'confirmed', 'pending'];

/** Reference: cats-frontend/src/modals/SessionCardModal.tsx */
export default function SessionCardModal({
    session,
    isAdmin,
    isOpen,
    onClose,
}: {
    session: ScheduleSession | null;
    isAdmin: boolean;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [cancelOpen, setCancelOpen] = useState(false);
    const [starting, setStarting] = useState(false);
    const { activeSession } = usePage().props;

    if (!session) {
        return null;
    }

    const intake = session.client?.original_intake;
    const editHref = `${isAdmin ? '/admin' : '/therapist'}/sessions/${session.id}/edit`;
    const canCancel = CANCELLABLE_STATUSES.includes(session.status);
    const canStart = !isAdmin && session.status === 'scheduled';

    const handleStart = () => {
        setStarting(true);
        router.post(
            `/therapist/sessions/${session.id}/start`,
            {},
            { onFinish: () => setStarting(false), onSuccess: onClose },
        );
    };

    return (
        <>
            <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
                <DialogContent className="w-full max-w-lg p-6">
                    <DialogHeader>
                        <div className="flex items-center justify-between">
                            <DialogTitle className="text-xl font-bold">
                                {intake
                                    ? `${intake.child_first_name} ${intake.child_last_name}`
                                    : 'Session'}
                            </DialogTitle>
                            <SessionStatusBadge status={session.status} />
                        </div>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-5 text-sm">
                        <div className="col-span-2 flex items-center gap-3">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <p>
                                {formatScheduledDate(session.scheduled_start)}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Clock className="h-4 w-4 text-muted-foreground" />
                            <p>
                                {formatScheduledTime(session.scheduled_start)}{' '}
                                –{' '}
                                {formatScheduledTime(session.scheduled_end)}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                            <p>{session.location || '-'}</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <UserCog className="h-4 w-4 text-muted-foreground" />
                            <p>
                                {session.therapist
                                    ? `${session.therapist.first_name} ${session.therapist.last_name}`
                                    : '-'}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <User className="h-4 w-4 text-muted-foreground" />
                            <p>
                                {session.service?.name ||
                                    session.service_name ||
                                    '-'}
                            </p>
                        </div>

                        {session.notes && (
                            <div className="col-span-2 border-t pt-3">
                                <p className="text-xs text-muted-foreground">
                                    Notes
                                </p>
                                <p>{session.notes}</p>
                            </div>
                        )}

                        {session.status === 'cancelled' &&
                            session.cancel_reason && (
                                <div className="col-span-2 rounded-[5px] border border-red-400 bg-red-100 p-3 text-red-700">
                                    <p className="flex items-center gap-2 text-xs font-medium">
                                        <X className="h-3 w-3" /> Cancellation
                                        Reason
                                    </p>
                                    <p className="mt-1">
                                        {session.cancel_reason}
                                    </p>
                                </div>
                            )}
                    </div>

                    <DialogFooter className="mt-6">
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={onClose}
                        >
                            Close
                        </Button>
                        {canCancel && (
                            <Button
                                variant="destructive"
                                className="rounded-[10px]"
                                onClick={() => setCancelOpen(true)}
                            >
                                Cancel Session
                            </Button>
                        )}
                        {canStart && (
                            <Button
                                className="rounded-[10px]"
                                onClick={handleStart}
                                disabled={!!activeSession || starting}
                            >
                                <Play className="h-4 w-4" /> Start Session
                            </Button>
                        )}
                        <Button className="rounded-[10px]" asChild>
                            <Link href={editHref}>Reschedule</Link>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <CancelConfirmationModal
                session={session}
                isAdmin={isAdmin}
                isOpen={cancelOpen}
                onClose={() => {
                    setCancelOpen(false);
                    onClose();
                }}
            />
        </>
    );
}
