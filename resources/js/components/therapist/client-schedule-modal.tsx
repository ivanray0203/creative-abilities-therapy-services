import { Calendar } from 'lucide-react';

import AvailabilitySummary from '@/components/availability-summary';
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
                        Availability
                    </div>

                    <AvailabilitySummary
                        slots={intake.availability_slots}
                        fallbackDays={intake.available_days}
                        fallbackTimes={intake.preferred_times}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
