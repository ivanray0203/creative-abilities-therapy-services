import { Download } from 'lucide-react';

import { ComplaintStatusBadge } from '@/components/complaints/badges';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatScheduledDateTime } from '@/lib/helpers';
import type { Complaint } from '@/types/complaint';

/** Reference: cats-frontend/src/modals/ComplaintModal.tsx */
export default function ComplaintDetailModal({
    complaint,
    isOpen,
    onClose,
}: {
    complaint: Complaint | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    if (!complaint) {
        return null;
    }

    const childName = complaint.client?.original_intake
        ? `${complaint.client.original_intake.child_first_name} ${complaint.client.original_intake.child_last_name}`
        : 'Client';
    const therapistName = complaint.therapist
        ? `${complaint.therapist.first_name} ${complaint.therapist.last_name}`
        : '-';

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-lg overflow-y-auto p-6">
                <DialogHeader>
                    <div className="flex items-center justify-between">
                        <DialogTitle className="text-xl font-bold">
                            {complaint.subject}
                        </DialogTitle>
                        <ComplaintStatusBadge status={complaint.status} />
                    </div>
                </DialogHeader>

                <div className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p className="text-xs text-muted-foreground">Client</p>
                        <p>{childName}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Therapist
                        </p>
                        <p>{therapistName}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Category
                        </p>
                        <p className="capitalize">{complaint.category}</p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Filed By
                        </p>
                        <p className="capitalize">{complaint.complained_by}</p>
                    </div>
                    {complaint.session && (
                        <div className="col-span-2">
                            <p className="text-xs text-muted-foreground">
                                Session
                            </p>
                            <p>
                                {formatScheduledDateTime(
                                    complaint.session.scheduled_start,
                                )}
                            </p>
                        </div>
                    )}
                </div>

                <div className="border-t pt-3">
                    <p className="text-xs text-muted-foreground">Description</p>
                    <p className="mt-1 text-sm">{complaint.description}</p>
                </div>

                {complaint.drive_web_view && (
                    <a
                        href={complaint.drive_web_view}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 text-sm text-primary"
                    >
                        <Download className="h-4 w-4" /> View Attachment
                    </a>
                )}

                {complaint.status === 'resolved' &&
                    complaint.admin_response && (
                        <div className="rounded-[5px] border border-green-400 bg-green-50 p-4">
                            <p className="text-xs font-medium text-green-800">
                                Resolution
                            </p>
                            <p className="mt-1 text-sm text-green-800">
                                {complaint.admin_response}
                            </p>
                        </div>
                    )}

                {complaint.consent_given && complaint.consent_info && (
                    <div className="border-t pt-3 text-xs text-muted-foreground">
                        <p>Consent: {complaint.consent_info}</p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
