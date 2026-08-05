import { Calendar, Clock } from 'lucide-react';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Intake } from '@/types/intake';

/** Read-only view of the client's original intake availability preferences (reference: cats-frontend/src/modals/ClientScheduleModal.tsx). */
export function ClientScheduleModal({
    open,
    onClose,
    intake,
}: {
    open: boolean;
    onClose: () => void;
    intake: Intake;
}) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="max-w-md rounded-xl">
                <DialogHeader>
                    <DialogTitle>Client Availability</DialogTitle>
                    <DialogDescription>
                        Schedule preferences for{' '}
                        <span className="font-medium">
                            {intake.child_first_name} {intake.child_last_name}
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-2">
                    <div className="flex items-center gap-2 text-sm font-semibold">
                        <Calendar className="h-4 w-4" />
                        Available Days
                    </div>

                    {intake.available_days?.length ? (
                        <div className="flex flex-wrap gap-2">
                            {intake.available_days.map((day) => (
                                <span
                                    key={day}
                                    className="rounded-full bg-secondary px-3 py-1 text-sm text-secondary-foreground"
                                >
                                    {day}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No days provided
                        </p>
                    )}
                </div>

                <div className="mt-4 space-y-2">
                    <div className="flex items-center gap-2 text-sm font-semibold">
                        <Clock className="h-4 w-4" />
                        Preferred Times
                    </div>

                    {intake.preferred_times?.length ? (
                        <div className="flex flex-wrap gap-2">
                            {intake.preferred_times.map((time) => (
                                <span
                                    key={time}
                                    className="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm text-blue-700"
                                >
                                    {time}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No time preferences provided
                        </p>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
