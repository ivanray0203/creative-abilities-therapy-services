import { useForm } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { useEffect } from 'react';

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
import { Textarea } from '@/components/ui/textarea';
import type { Intake, IntakeStatus, TherapistOption } from '@/types/intake';

/** Reference: cats-frontend/src/modals/UpdateStatusModal.tsx */
const STATUS_TITLES: Record<string, string> = {
    pending: 'Pending',
    under_review: 'Under Review',
    waitlist: 'Waitlist',
    denied: 'Denied',
    approved: 'Approved',
};

const STATUS_DESCRIPTIONS: Record<string, string> = {
    pending: 'The intake is still pending and awaiting action.',
    under_review:
        'This will move the intake to Under Review for detailed evaluation.',
    waitlist:
        'This will add the intake to the waitlist. The client will be notified.',
    denied: 'This will deny the intake application. The client will be notified.',
    approved:
        'Assign a therapist to review this intake. The therapist will approve or reject the application.',
};

interface TherapistAssignment {
    service: string | null;
    therapist_id: string;
}

interface UpdateStatusForm {
    status: string;
    note: string;
    therapist_assignments: TherapistAssignment[];
}

export default function UpdateStatusModal({
    intake,
    therapists,
    targetStatus,
    isOpen,
    onClose,
}: {
    intake: Intake;
    therapists: TherapistOption[];
    targetStatus: IntakeStatus | '';
    isOpen: boolean;
    onClose: () => void;
}) {
    const {
        data,
        setData,
        patch,
        processing,
        errors,
        reset,
        clearErrors,
        transform,
    } = useForm<UpdateStatusForm>({
        status: targetStatus,
        note: '',
        therapist_assignments: [],
    });

    const services = intake.services_needed ?? [];

    useEffect(() => {
        if (isOpen && targetStatus === 'approved') {
            setData(
                'therapist_assignments',
                services.length > 0
                    ? services.map((service) => ({ service, therapist_id: '' }))
                    : [{ service: null, therapist_id: '' }],
            );
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isOpen, targetStatus]);

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const setAssignmentTherapist = (index: number, therapistId: string) => {
        setData(
            'therapist_assignments',
            data.therapist_assignments.map((assignment, i) =>
                i === index
                    ? { ...assignment, therapist_id: therapistId }
                    : assignment,
            ),
        );
    };

    const therapistsForService = (service: string | null) => {
        if (!service) {
            return therapists;
        }

        return therapists.filter((therapist) =>
            therapist.specializations?.includes(service),
        );
    };

    const canSubmit =
        targetStatus !== 'approved' ||
        data.therapist_assignments.some(
            (assignment) => assignment.therapist_id !== '',
        );

    const submit = () => {
        transform((form) => ({
            ...form,
            status: targetStatus,
            therapist_assignments: form.therapist_assignments.filter(
                (assignment) => assignment.therapist_id !== '',
            ),
        }));

        patch(`/admin/intake/${intake.id}/status`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Change Status to{' '}
                        {STATUS_TITLES[targetStatus] ?? targetStatus}
                    </DialogTitle>
                </DialogHeader>

                <p className="mb-4 text-sm text-muted-foreground">
                    {STATUS_DESCRIPTIONS[targetStatus] ?? 'Updating status...'}
                </p>

                {targetStatus === 'approved' && (
                    <>
                        {data.therapist_assignments.map((assignment, index) => {
                            const options = therapistsForService(
                                assignment.service,
                            );

                            return (
                                <div
                                    key={
                                        assignment.service ?? '__whole_intake__'
                                    }
                                    className="mb-4"
                                >
                                    <Label className="mb-2 block">
                                        {assignment.service ??
                                            'Select Therapist'}
                                    </Label>
                                    <Select
                                        value={assignment.therapist_id}
                                        onValueChange={(value) =>
                                            setAssignmentTherapist(index, value)
                                        }
                                        disabled={options.length === 0}
                                    >
                                        <SelectTrigger className="rounded-[10px]">
                                            <SelectValue placeholder="Search therapist..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.map((therapist) => (
                                                <SelectItem
                                                    key={therapist.id}
                                                    value={String(therapist.id)}
                                                >
                                                    {therapist.first_name}{' '}
                                                    {therapist.last_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {assignment.service &&
                                        options.length === 0 && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                No therapist is tagged with this
                                                specialization — this service
                                                will be left unassigned for now.
                                            </p>
                                        )}
                                </div>
                            );
                        })}
                        {errors.therapist_assignments && (
                            <p className="text-sm text-destructive">
                                {errors.therapist_assignments}
                            </p>
                        )}

                        <div className="rounded border border-blue-400 bg-blue-100 p-3">
                            <p className="flex flex-row gap-3 text-sm text-blue-800">
                                <AlertCircleIcon />
                                Each assigned therapist will be able to approve
                                or reject their service based on their capacity
                                and expertise.
                            </p>
                        </div>
                    </>
                )}

                <Label htmlFor="status-notes">Notes (Optional)</Label>
                <Textarea
                    id="status-notes"
                    value={data.note}
                    onChange={(event) => setData('note', event.target.value)}
                    placeholder="Add any notes about this status change..."
                    className="mb-4"
                />
                {errors.status && (
                    <p className="text-sm text-destructive">{errors.status}</p>
                )}

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={processing || !canSubmit}
                    >
                        Confirm
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
