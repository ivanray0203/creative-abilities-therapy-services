import { useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Complaint } from '@/types/complaint';

/** Reference: cats-frontend/src/modals/ResolveComplaintModal.tsx */
export default function ResolveComplaintModal({
    complaint,
    isOpen,
    onClose,
}: {
    complaint: Complaint | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({ admin_response: '' });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    if (!complaint) {
        return null;
    }

    const childName = complaint.client?.original_intake
        ? `${complaint.client.original_intake.child_first_name} ${complaint.client.original_intake.child_last_name}`
        : 'Client';

    const submit = () => {
        post(`/admin/messages/${complaint.id}/resolve`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full max-w-lg p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Resolve{' '}
                        {complaint.type === 'disputes'
                            ? 'Dispute'
                            : 'Complaint'}
                    </DialogTitle>
                </DialogHeader>

                <div className="rounded-[5px] border p-3 text-sm">
                    <p className="font-medium">{complaint.subject}</p>
                    <p className="text-muted-foreground">
                        {childName}
                        {complaint.therapist &&
                            ` • ${complaint.therapist.first_name} ${complaint.therapist.last_name}`}
                    </p>
                </div>

                <div>
                    <Label htmlFor="resolution-text">Resolution *</Label>
                    <Textarea
                        id="resolution-text"
                        rows={4}
                        value={data.admin_response}
                        className="mt-2 rounded-[10px]"
                        onChange={(event) =>
                            setData('admin_response', event.target.value)
                        }
                    />
                    {errors.admin_response && (
                        <p className="mt-1 text-sm text-destructive">
                            {errors.admin_response}
                        </p>
                    )}
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={closeAndReset}
                    >
                        Cancel
                    </Button>
                    <Button
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={processing}
                    >
                        Resolve
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
