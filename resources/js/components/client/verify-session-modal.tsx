import { CheckCircle2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatScheduledTime } from '@/lib/helpers';
import type { ScheduleSession } from '@/types/session';

/** `start_time`/`end_time` are real UTC instants (when the session actually
 * started/ended) — unlike `scheduled_start`/`scheduled_end`, local-time
 * formatting is correct here. */
function formatActualTime(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

const CHECKLIST = [
    'The therapist was present at the specified time',
    'The service was provided as scheduled',
    'The session details are accurate',
];

/** Reference: cats-frontend/src/modals/VerifySessionModal.tsx */
export default function VerifySessionModal({
    session,
    isOpen,
    processing,
    onClose,
    onConfirm,
}: {
    session: ScheduleSession | null;
    isOpen: boolean;
    processing: boolean;
    onClose: () => void;
    onConfirm: () => void;
}) {
    if (!session) {
        return null;
    }

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Verify Session</DialogTitle>
                    <DialogDescription>
                        Please confirm the details of this therapy session
                    </DialogDescription>
                </DialogHeader>

                <div className="mt-4 space-y-4 text-sm">
                    <div className="grid grid-cols-1 gap-3">
                        <div className="flex justify-between border-b border-border pb-2">
                            <p className="text-muted-foreground">
                                Scheduled Time
                            </p>
                            <p className="font-medium">
                                {formatScheduledTime(session.scheduled_start)}{' '}
                                – {formatScheduledTime(session.scheduled_end)}
                            </p>
                        </div>
                        <div className="flex justify-between border-b border-border pb-2">
                            <p className="text-muted-foreground">
                                Actual Start
                            </p>
                            <p className="font-medium">
                                {formatActualTime(session.start_time)}
                            </p>
                        </div>
                        <div className="flex justify-between border-b border-border pb-2">
                            <p className="text-muted-foreground">Actual End</p>
                            <p className="font-medium">
                                {formatActualTime(session.end_time)}
                            </p>
                        </div>
                        <div className="flex justify-between border-b border-border pb-2">
                            <p className="text-muted-foreground">Location</p>
                            <p className="font-medium">
                                {session.location || '-'}
                            </p>
                        </div>
                    </div>

                    <div className="space-y-2 rounded-md border border-blue-200 bg-blue-100 p-3 text-blue-900">
                        <p className="text-xs font-semibold tracking-wide uppercase">
                            Verification Confirms
                        </p>
                        {CHECKLIST.map((item) => (
                            <div key={item} className="flex items-start gap-2">
                                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                                <span className="text-sm">{item}</span>
                            </div>
                        ))}
                    </div>
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded"
                        onClick={onClose}
                    >
                        Cancel
                    </Button>
                    <Button
                        className="rounded bg-cyan-700 hover:bg-cyan-800"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        <CheckCircle2 className="h-4 w-4" /> Confirm & Verify
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
