import { Clock, MapPin, User } from 'lucide-react';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CalendarSession } from '@/types/session';

/**
 * Every session booked on one day, opened from the admin calendar's month
 * grid — the cell itself only has room for a count.
 */
export function CalendarDaySessionsModal({
    open,
    onClose,
    date,
    sessions,
}: {
    open: boolean;
    onClose: () => void;
    /** `YYYY-MM-DD`, or null before a day has been picked. */
    date: string | null;
    /** Already filtered to `date` by the caller. */
    sessions: CalendarSession[];
}) {
    // Split on the parts rather than `new Date(date)`, which reads a bare
    // `YYYY-MM-DD` as UTC midnight and so shows the previous day west of UTC.
    const [year, month, day] = (date ?? '').split('-').map(Number);
    const heading = date
        ? new Date(year, month - 1, day).toLocaleDateString(undefined, {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : '';

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="max-h-[80vh] max-w-lg overflow-y-auto rounded-xl">
                <DialogHeader>
                    <DialogTitle>{heading}</DialogTitle>
                    <DialogDescription>
                        {sessions.length === 0
                            ? 'No sessions booked on this day.'
                            : `${sessions.length} session${sessions.length === 1 ? '' : 's'} booked.`}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    {sessions.map((session) => (
                        <div
                            key={session.id}
                            className={`rounded-[10px] border p-3 ${session.status === 'cancelled' ? 'opacity-60' : ''}`}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <p className="flex items-center gap-2 font-medium">
                                    <Clock className="h-4 w-4 shrink-0" />
                                    {session.time} – {session.endTime}
                                </p>
                                <span className="rounded-[5px] border px-2 py-0.5 text-xs whitespace-nowrap capitalize">
                                    {session.status.replace('_', ' ')}
                                </span>
                            </div>

                            <p className="mt-2 text-sm font-medium">
                                {session.client}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {session.type}
                            </p>

                            <p className="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                                <User className="h-3 w-3 shrink-0" />
                                {session.therapist}
                            </p>
                            <p className="flex items-center gap-2 text-xs text-muted-foreground">
                                <MapPin className="h-3 w-3 shrink-0" />
                                {session.location || 'No location set'}
                            </p>
                        </div>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}
