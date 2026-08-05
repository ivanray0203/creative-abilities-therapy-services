import { useForm } from '@inertiajs/react';
import { Loader2, UserCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TherapistOption } from '@/types/intake';

/**
 * Admin direct-approve — promotes an intake straight to a Client.
 * Ported from cats-frontend/src/components/ApproveIntakeDialog.tsx, whose
 * trigger the reference left commented out. This phase wires the trigger for
 * real on the detail page so the approval workflow is reachable and testable.
 */
export default function ApproveIntakeDialog({
    intakeId,
    childName,
    therapists,
    isOpen,
    onClose,
}: {
    intakeId: number;
    childName: string;
    therapists: TherapistOption[];
    isOpen: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({ therapist_id: '' });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        post(`/admin/intake/${intakeId}/approve`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <UserCheck className="h-5 w-5 text-primary" />
                        Approve Intake &amp; Assign Therapist
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    Approve <strong>{childName}</strong>&apos;s intake
                    application and assign a therapist. This will move the
                    intake to the clients list.
                </p>

                <div className="space-y-4 py-4">
                    <div className="space-y-2">
                        <Label htmlFor="approve-therapist">
                            Select Therapist
                        </Label>
                        <Select
                            value={data.therapist_id}
                            onValueChange={(value) =>
                                setData('therapist_id', value)
                            }
                        >
                            <SelectTrigger id="approve-therapist">
                                <SelectValue placeholder="Choose a therapist..." />
                            </SelectTrigger>
                            <SelectContent>
                                {therapists.map((therapist) => (
                                    <SelectItem
                                        key={therapist.id}
                                        value={String(therapist.id)}
                                    >
                                        {therapist.first_name}{' '}
                                        {therapist.last_name} ({therapist.email}
                                        )
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.therapist_id && (
                            <p className="text-sm text-destructive">
                                {errors.therapist_id}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex justify-end gap-3">
                    <Button
                        variant="outline"
                        onClick={closeAndReset}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={submit}
                        disabled={processing || !data.therapist_id}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Approving...
                            </>
                        ) : (
                            'Approve & Assign'
                        )}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
