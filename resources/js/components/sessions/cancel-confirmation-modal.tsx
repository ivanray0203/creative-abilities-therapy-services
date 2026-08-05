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
import type { ScheduleSession } from '@/types/session';

interface CancelForm {
    cancel_reason: string;
}

/** Reference: cats-frontend/src/modals/CancelConfirmationModal.tsx */
export default function CancelConfirmationModal({
    session,
    isAdmin,
    isOpen,
    onClose,
}: {
    session: ScheduleSession | null;
    isAdmin: boolean;
    isOpen: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<CancelForm>({ cancel_reason: '' });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        if (!session) {
            return;
        }

        const prefix = isAdmin ? '/admin' : '/therapist';

        post(`${prefix}/sessions/${session.id}/cancel`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold text-primary">
                        Cancel Session
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    Please provide a reason for cancelling this session. This
                    action can&apos;t be reversed.
                </p>

                <div>
                    <Label htmlFor="cancel-reason">Cancellation Reason *</Label>
                    <Textarea
                        id="cancel-reason"
                        className="mt-2 rounded-[10px]"
                        rows={3}
                        value={data.cancel_reason}
                        onChange={(event) =>
                            setData('cancel_reason', event.target.value)
                        }
                    />
                    {errors.cancel_reason && (
                        <p className="mt-1 text-sm text-destructive">
                            {errors.cancel_reason}
                        </p>
                    )}
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={closeAndReset}
                    >
                        Keep Session
                    </Button>
                    <Button
                        variant="destructive"
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={
                            processing || data.cancel_reason.trim() === ''
                        }
                    >
                        Cancel Session
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
