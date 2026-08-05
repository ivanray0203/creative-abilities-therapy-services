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
import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

interface AssignTherapistForm {
    therapist_id: string;
}

/**
 * Reference: cats-frontend/src/modals/AssignTherapistModal.tsx
 *
 * Posts to `assign-therapist` for a first-time assignment (no timeline
 * entry) or `reassign-therapist` once the client already has a primary
 * therapist (writes a timeline entry via Client::reassignPrimaryTherapist).
 */
export default function AssignTherapistModal({
    client,
    therapists,
    isOpen,
    onClose,
}: {
    client: Client;
    therapists: TherapistOption[];
    isOpen: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<AssignTherapistForm>({
            therapist_id: client.primary_therapist_id
                ? String(client.primary_therapist_id)
                : '',
        });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        const endpoint = client.primary_therapist_id
            ? `/admin/clients/${client.id}/reassign-therapist`
            : `/admin/clients/${client.id}/assign-therapist`;

        post(endpoint, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        {client.primary_therapist_id
                            ? 'Reassign Primary Therapist'
                            : 'Assign Primary Therapist'}
                    </DialogTitle>
                </DialogHeader>

                <Label htmlFor="assign-therapist" className="mb-2 block">
                    Select Therapist*
                </Label>
                <Select
                    value={data.therapist_id}
                    onValueChange={(value) => setData('therapist_id', value)}
                >
                    <SelectTrigger
                        id="assign-therapist"
                        className="rounded-[10px]"
                    >
                        <SelectValue placeholder="Search therapist..." />
                    </SelectTrigger>
                    <SelectContent>
                        {therapists.map((therapist) => (
                            <SelectItem
                                key={therapist.id}
                                value={String(therapist.id)}
                            >
                                {therapist.first_name} {therapist.last_name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
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
                        Confirm
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
