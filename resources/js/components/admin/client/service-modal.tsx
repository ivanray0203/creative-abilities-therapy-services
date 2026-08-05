import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ClientService } from '@/types/client';

/** Reference: cats-frontend/src/modals/ServiceModal.tsx — read-only service detail popup. */
export default function ServiceModal({
    clientService,
    isOpen,
    onClose,
}: {
    clientService: ClientService | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    if (!clientService) {
        return null;
    }

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        {clientService.service?.name ?? 'Service'}
                    </DialogTitle>
                </DialogHeader>

                <div className="grid grid-cols-2 gap-5">
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Therapist
                        </p>
                        <p>
                            {clientService.therapist
                                ? `${clientService.therapist.first_name} ${clientService.therapist.last_name}`
                                : '-'}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Frequency
                        </p>
                        <p>{clientService.frequency || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Duration
                        </p>
                        <p>{clientService.duration || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Start Date
                        </p>
                        <p>{clientService.start_date || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Funding Source
                        </p>
                        <p>{clientService.funding_source || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            No. of Sessions
                        </p>
                        <p>{clientService.no_sessions}</p>
                    </div>
                    <div className="col-span-2">
                        <p className="text-xs text-muted-foreground">Goals</p>
                        <p>{clientService.goals || '-'}</p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
