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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TherapistOption } from '@/types/intake';

interface ReassignServiceForm {
    service: string;
    therapist_id: string;
}

/**
 * Sends a declined service back out for review, to a different therapist.
 *
 * Posts to the intake's `send-to-therapist` endpoint rather than anything
 * client-side: the refusal lives on the intake's approval row, and that
 * endpoint is what flips it back to `reassign` and notifies the new
 * therapist. Their approval then attaches the service to this existing
 * client — no second promotion.
 */
export default function ReassignServiceModal({
    intakeId,
    service,
    therapists,
    declinedByTherapistId,
    isOpen,
    onClose,
}: {
    intakeId: number;
    service: string | null;
    therapists: TherapistOption[];
    /**
     * Whoever declined. They stay selectable — a refusal is often about
     * timing or caseload, so an admin may well re-ask after a conversation —
     * but the option says so, to make picking them a deliberate act.
     */
    declinedByTherapistId?: number | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
        transform,
    } = useForm<ReassignServiceForm>({
        service: service ?? '',
        therapist_id: '',
    });

    /*
     * useForm captures its initial values once, on mount — and this modal
     * mounts with no service selected, so the form kept posting an empty one.
     * Laravel then converted that to null and the intake gained a stray
     * whole-intake review instead of the declined service being reassigned.
     * Taking the service from the prop at submit time keeps the two in step.
     */
    transform((values) => ({ ...values, service: service ?? '' }));

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        post(`/admin/intake/${intakeId}/send-to-therapist`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Reassign {service ?? 'Service'}
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    The service goes back out for approval. Once the new
                    therapist accepts, it is added to this client&apos;s
                    services.
                </p>

                <Label htmlFor="reassign-therapist" className="mt-4 mb-2 block">
                    Select Therapist*
                </Label>
                <Select
                    value={data.therapist_id}
                    onValueChange={(value) => setData('therapist_id', value)}
                >
                    <SelectTrigger
                        id="reassign-therapist"
                        className="rounded-[10px]"
                    >
                        <SelectValue placeholder="Search therapist..." />
                    </SelectTrigger>
                    <SelectContent>
                        {therapists.length === 0 && (
                            <p className="p-3 text-sm text-muted-foreground">
                                No therapists available.
                            </p>
                        )}
                        {therapists.map((therapist) => (
                            <SelectItem
                                key={therapist.id}
                                value={String(therapist.id)}
                            >
                                {therapist.first_name} {therapist.last_name}
                                {therapist.id === declinedByTherapistId
                                    ? ' — previously declined'
                                    : ''}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.therapist_id && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.therapist_id}
                    </p>
                )}
                {errors.service && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.service}
                    </p>
                )}

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={
                            processing || data.therapist_id === '' || !service
                        }
                    >
                        Send for Approval
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
