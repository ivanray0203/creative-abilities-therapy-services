import { SessionStatusBadge } from '@/components/sessions/badges';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatScheduledTime } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** Reference: cats-frontend/src/modals/SessionListModal.tsx — day cell with >1 session. */
export default function SessionListModal({
    date,
    sessions,
    isOpen,
    onClose,
    onSelectSession,
}: {
    date: Date | null;
    sessions: ScheduleSession[];
    isOpen: boolean;
    onClose: () => void;
    onSelectSession: (session: ScheduleSession) => void;
}) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-lg font-semibold">
                        {date ? date.toLocaleDateString() : 'Sessions'}
                    </DialogTitle>
                </DialogHeader>

                <div className="grid grid-cols-1 gap-3">
                    {sessions.map((session) => (
                        <button
                            key={session.id}
                            type="button"
                            onClick={() => onSelectSession(session)}
                            className="flex flex-row items-center justify-between rounded-[5px] border p-3 text-left hover:bg-charcoal-gray/5"
                        >
                            <div>
                                <p>
                                    {session.client?.original_intake
                                        ? `${session.client.original_intake.child_first_name} ${session.client.original_intake.child_last_name}`
                                        : 'Client'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {formatScheduledTime(
                                        session.scheduled_start,
                                    )}
                                </p>
                            </div>
                            <SessionStatusBadge status={session.status} />
                        </button>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}
