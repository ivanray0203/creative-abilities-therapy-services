import { CheckCircle2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
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

const IMPORTANT_NOTES = [
    'Disputing a session will notify the administrator',
    'The session will be reviewed by our team',
    'You may be contacted for additional information',
    'This action cannot be undone',
];

/** Reference: cats-frontend/src/modals/DisputeSessionModal.tsx */
export default function DisputeSessionModal({
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
    onConfirm: (reason: string) => void;
}) {
    const [reason, setReason] = useState('');

    const closeAndReset = () => {
        setReason('');
        onClose();
    };

    if (!session) {
        return null;
    }

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle className="text-destructive">
                        Dispute Session
                    </DialogTitle>
                    <DialogDescription>
                        Please provide a detailed reason for disputing this
                        therapy session
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

                    <div>
                        <p className="font-semibold text-destructive">
                            Reason for Dispute *
                        </p>
                        <Textarea
                            className="mt-2"
                            rows={4}
                            placeholder="Please explain why you are disputing this session (e.g., service not provided, incorrect time, therapist did not show up, etc.)"
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                        />
                    </div>

                    <div className="space-y-2 rounded-md border border-red-500 bg-red-100 p-3 text-red-900">
                        <p className="text-xs font-semibold tracking-wide uppercase">
                            Important
                        </p>
                        {IMPORTANT_NOTES.map((item) => (
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
                        onClick={closeAndReset}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        className="rounded"
                        disabled={processing || reason.trim() === ''}
                        onClick={() => onConfirm(reason)}
                    >
                        <CheckCircle2 className="h-4 w-4" /> Submit Dispute
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
