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

interface CareTeamForm {
    therapist_id: string;
    action: 'add';
}

/** Reference: cats-frontend/src/modals/AddTherapistModal.tsx — adds a secondary care-team member. */
export default function AddTherapistModal({
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
    const { data, setData, patch, processing, errors, reset, clearErrors } =
        useForm<CareTeamForm>({
            therapist_id: '',
            action: 'add',
        });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const currentCareTeamIds = new Set(
        (client.care_team ?? []).map((therapist) => therapist.id),
    );
    const availableTherapists = therapists.filter(
        (therapist) => !currentCareTeamIds.has(therapist.id),
    );

    const submit = () => {
        patch(`/admin/clients/${client.id}/care-team`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Add Therapist to Care Team
                    </DialogTitle>
                </DialogHeader>

                <Label htmlFor="care-team-therapist" className="mb-2 block">
                    Select Therapist
                </Label>
                <Select
                    value={data.therapist_id}
                    onValueChange={(value) => setData('therapist_id', value)}
                >
                    <SelectTrigger
                        id="care-team-therapist"
                        className="rounded-[10px]"
                    >
                        <SelectValue placeholder="Search therapist..." />
                    </SelectTrigger>
                    <SelectContent>
                        {availableTherapists.map((therapist) => (
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
                        Add to Care Team
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
