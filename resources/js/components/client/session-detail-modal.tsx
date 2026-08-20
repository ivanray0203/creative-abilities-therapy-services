import { AlertCircle, Calendar, Clock, MapPin, UserCog } from 'lucide-react';

import {
    SessionServiceTags,
    SessionStatusBadge,
} from '@/components/sessions/badges';
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

/** Read-only detail view — reference: cats-frontend/src/modals/SessionCardModal.tsx, minus the reschedule/cancel/start actions clients don't get. */
export default function SessionDetailModal({
    session,
    isOpen,
    onClose,
}: {
    session: ScheduleSession | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    if (!session) {
        return null;
    }

    const therapistName = session.therapist
        ? `${session.therapist.first_name} ${session.therapist.last_name}`
        : '-';

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-lg p-6">
                <DialogHeader>
                    <div className="flex items-center justify-between">
                        <DialogTitle className="text-xl font-bold">
                            Session Details
                        </DialogTitle>
                        <SessionStatusBadge status={session.status} />
                    </div>
                    <SessionServiceTags session={session} className="mt-2" />
                </DialogHeader>

                <div className="grid grid-cols-2 gap-5 text-sm">
                    <div className="col-span-2 flex items-center gap-3">
                        <Calendar className="h-4 w-4 text-muted-foreground" />
                        <p>{formatScheduledDate(session.scheduled_start)}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Clock className="h-4 w-4 text-muted-foreground" />
                        <p>
                            {formatScheduledTime(session.scheduled_start)} –{' '}
                            {formatScheduledTime(session.scheduled_end)}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <MapPin className="h-4 w-4 text-muted-foreground" />
                        <p>{session.location || '-'}</p>
                    </div>
                    <div className="col-span-2 flex items-center gap-3">
                        <UserCog className="h-4 w-4 text-muted-foreground" />
                        <p>{therapistName}</p>
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
                                    <AlertCircle className="h-3 w-3" />{' '}
                                    Cancellation Reason
                                </p>
                                <p className="mt-1">{session.cancel_reason}</p>
                            </div>
                        )}

                    {session.status === 'disputed' &&
                        session.dispute_reason && (
                            <div className="col-span-2 rounded-[5px] border border-red-400 bg-red-100 p-3 text-red-700">
                                <p className="flex items-center gap-2 text-xs font-medium">
                                    <AlertCircle className="h-3 w-3" /> Dispute
                                    Reason
                                </p>
                                <p className="mt-1">{session.dispute_reason}</p>
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
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
