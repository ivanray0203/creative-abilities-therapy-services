import { useForm } from '@inertiajs/react';
import { Phone, User, Video } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Application, ApplicationStatus } from '@/types/application';

const TITLES: Record<string, string> = {
    reviewing: 'Update Application Status',
    interview_scheduled: 'Schedule Interview',
    declined: 'Decline Application',
    hired: 'Hire Candidate',
};

const DESCRIPTIONS: Record<string, string> = {
    reviewing: 'This will move the application to Under Review.',
    interview_scheduled: 'Schedule an interview with',
    declined: 'Are you sure you want to decline the application from',
    hired: 'Are you sure you want to hire',
};

const CONFIRM_LABELS: Record<string, string> = {
    reviewing: 'Update',
    interview_scheduled: 'Confirm Interview',
    declined: 'Decline',
    hired: 'Hire',
};

interface UpdateStatusForm {
    application_status: ApplicationStatus | '';
    note: string;
    hourly_rate: string;
    interview_date: string;
    interview_time: string;
    interview_platform: string;
}

/** Reference: cats-frontend/src/modals/UpdateApplicationStatus.tsx */
export default function UpdateApplicationStatusModal({
    application,
    targetStatus,
    isOpen,
    onClose,
}: {
    application: Application;
    targetStatus: ApplicationStatus | '';
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
        application_status: targetStatus,
        note: '',
        hourly_rate: '',
        interview_date: '',
        interview_time: '',
        interview_platform: 'video',
    });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        transform((form) => ({ ...form, application_status: targetStatus }));

        patch(`/admin/applications/${application.id}/status`, {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle>
                        <p className="text-xl font-bold">
                            {TITLES[targetStatus] ?? 'Update Status'}
                        </p>
                        <p className="mb-4 text-sm font-normal text-muted-foreground">
                            {DESCRIPTIONS[targetStatus]}{' '}
                            {application.first_name} {application.last_name}
                        </p>
                    </DialogTitle>
                </DialogHeader>

                {targetStatus === 'hired' && (
                    <div>
                        <Label htmlFor="hourly-rate">Hourly Rate *</Label>
                        <Input
                            id="hourly-rate"
                            type="number"
                            min={0}
                            step="0.01"
                            className="rounded-[5px]"
                            placeholder="50"
                            value={data.hourly_rate}
                            onChange={(event) =>
                                setData('hourly_rate', event.target.value)
                            }
                        />
                        {errors.hourly_rate && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.hourly_rate}
                            </p>
                        )}
                    </div>
                )}

                {targetStatus === 'interview_scheduled' ? (
                    <div className="space-y-3">
                        <div>
                            <Label htmlFor="interview-date">
                                Interview Date *
                            </Label>
                            <Input
                                id="interview-date"
                                type="date"
                                className="rounded-[5px]"
                                value={data.interview_date}
                                onChange={(event) =>
                                    setData(
                                        'interview_date',
                                        event.target.value,
                                    )
                                }
                            />
                            {errors.interview_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.interview_date}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="interview-time">
                                Interview Time
                            </Label>
                            <Input
                                id="interview-time"
                                type="time"
                                className="rounded-[5px]"
                                value={data.interview_time}
                                onChange={(event) =>
                                    setData(
                                        'interview_time',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <Label htmlFor="interview-platform">
                                Interview Type
                            </Label>
                            <Select
                                value={data.interview_platform}
                                onValueChange={(value) =>
                                    setData('interview_platform', value)
                                }
                            >
                                <SelectTrigger
                                    id="interview-platform"
                                    className="w-full rounded-[5px]"
                                >
                                    <SelectValue placeholder="Select Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="video">
                                        <div className="flex items-center gap-2">
                                            <Video className="h-4 w-4 shrink-0" />
                                            <span>Video Call</span>
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="phone">
                                        <div className="flex items-center gap-2">
                                            <Phone className="h-4 w-4 shrink-0" />
                                            <span>Phone Call</span>
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="in-person">
                                        <div className="flex items-center gap-2">
                                            <User className="h-4 w-4 shrink-0" />
                                            <span>In-Person</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                ) : (
                    <div>
                        <Label htmlFor="status-notes">Notes (Optional)</Label>
                        <Textarea
                            id="status-notes"
                            value={data.note}
                            onChange={(event) =>
                                setData('note', event.target.value)
                            }
                            placeholder="Add any notes about this status change..."
                            className="mb-4"
                        />
                    </div>
                )}

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        onClick={closeAndReset}
                        variant="outline"
                    >
                        Cancel
                    </Button>
                    <Button
                        className={`w-full rounded-[10px] ${
                            targetStatus === 'hired'
                                ? 'bg-orange-500 text-white hover:bg-orange-600'
                                : ''
                        } ${
                            targetStatus === 'declined'
                                ? 'bg-red-500 text-white hover:bg-red-600'
                                : ''
                        }`}
                        onClick={submit}
                        disabled={processing}
                    >
                        {CONFIRM_LABELS[targetStatus] ?? 'Confirm'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
