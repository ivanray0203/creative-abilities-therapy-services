import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import type { ClientService } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

/**
 * Moves one of the client's availed services to another therapist, from the
 * Therapist tab. The server adds the new therapist to the care team.
 */
export default function ReassignClientServiceModal({
    clientId,
    clientService,
    therapists,
    onClose,
}: {
    clientId: number;
    clientService: ClientService | null;
    therapists: TherapistOption[];
    onClose: () => void;
}) {
    const { data, setData, patch, processing, errors, reset, clearErrors } =
        useForm<{ therapist_id: string }>({ therapist_id: '' });

    useEffect(() => {
        reset();
        clearErrors();
        // Start blank each time a different service is picked.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [clientService?.id]);

    const serviceName = clientService?.service?.name ?? 'Service';
    // Only therapists specialized in this service can take it over.
    const otherTherapists = therapists.filter(
        (therapist) =>
            therapist.id !== clientService?.therapist_id &&
            therapist.specializations?.includes(serviceName),
    );

    const submit = () => {
        if (!clientService) {
            return;
        }

        patch(
            `/admin/clients/${clientId}/services/${clientService.id}/therapist`,
            { preserveScroll: true, onSuccess: onClose },
        );
    };

    return (
        <Dialog
            open={clientService !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Reassign {serviceName}
                    </DialogTitle>
                    <DialogDescription>
                        Currently provided by{' '}
                        {clientService?.therapist
                            ? `${clientService.therapist.first_name} ${clientService.therapist.last_name}`
                            : 'no one'}
                        . Choose who takes it over.
                    </DialogDescription>
                </DialogHeader>

                <Label
                    htmlFor="reassign-service-therapist"
                    className="mb-2 block"
                >
                    New Therapist
                </Label>
                <Select
                    value={data.therapist_id}
                    onValueChange={(value) => setData('therapist_id', value)}
                >
                    <SelectTrigger
                        id="reassign-service-therapist"
                        className="rounded-[10px]"
                    >
                        <SelectValue placeholder="Select therapist..." />
                    </SelectTrigger>
                    <SelectContent>
                        {otherTherapists.map((therapist) => (
                            <SelectItem
                                key={therapist.id}
                                value={String(therapist.id)}
                            >
                                {therapist.first_name} {therapist.last_name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {otherTherapists.length === 0 && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        No other therapist has the {serviceName} specialization.
                        Add it on their team member profile first.
                    </p>
                )}
                {errors.therapist_id && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.therapist_id}
                    </p>
                )}

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={processing || data.therapist_id === ''}
                    >
                        Reassign Service
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
