import { router } from '@inertiajs/react';
import { useState } from 'react';

import SignaturePad from '@/components/signature-pad';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import type { Client } from '@/types/client';

const EMPTY_FORM = { client_id: '', date_start: '', date_end: '' };

/**
 * "Generate Timesheet" on the timesheets list: rolls every hour logged for
 * one child between two dates onto one form.
 *
 * The aide signs here rather than afterwards — generating the form is them
 * standing behind the hours, so the parent receives it already signed on the
 * aide's side.
 *
 * Uses `router.post` rather than `useForm` because the signature lives in
 * the pad's own state; a useForm field would have to mirror it on every
 * stroke.
 */
export default function GenerateTimesheetModal({
    aideName,
    clients,
    isOpen,
    onClose,
}: {
    /** Printed under the pad — whose signature this is meant to be. */
    aideName: string;
    clients: Client[];
    isOpen: boolean;
    onClose: () => void;
}) {
    const [form, setForm] = useState({ ...EMPTY_FORM });
    const [signature, setSignature] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const closeAndReset = () => {
        setForm({ ...EMPTY_FORM });
        setSignature(null);
        setErrors({});
        onClose();
    };

    const submit = () => {
        router.post(
            '/therapist/timesheets/generate',
            { ...form, signature },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setErrors,
                onSuccess: () => closeAndReset(),
            },
        );
    };

    const isComplete =
        form.client_id !== '' &&
        form.date_start !== '' &&
        form.date_end !== '' &&
        signature !== null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="max-h-[90vh] w-full max-w-2xl overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Generate Timesheet
                    </DialogTitle>
                    <DialogDescription>
                        Every hour you logged for this child between these dates
                        goes onto one form, which is sent to the parent for
                        their signature.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    <div>
                        <Label htmlFor="generate-timesheet-client">
                            Client *
                        </Label>
                        <Select
                            value={form.client_id}
                            onValueChange={(value) =>
                                setForm({ ...form, client_id: value })
                            }
                        >
                            <SelectTrigger
                                id="generate-timesheet-client"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select client" />
                            </SelectTrigger>
                            <SelectContent>
                                {clients.map((client) => (
                                    <SelectItem
                                        key={client.id}
                                        id={`generate-timesheet-client-${client.id}`}
                                        value={String(client.id)}
                                    >
                                        {client.original_intake
                                            ? `${client.original_intake.child_first_name} ${client.original_intake.child_last_name}`
                                            : `Client #${client.id}`}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {clients.length === 0 && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                No child has hours waiting to go on a timesheet.
                            </p>
                        )}
                        {errors.client_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.client_id}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="generate-timesheet-date-start">
                                Date From *
                            </Label>
                            <Input
                                id="generate-timesheet-date-start"
                                type="date"
                                value={form.date_start}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        date_start: event.target.value,
                                    })
                                }
                            />
                            {errors.date_start && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.date_start}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="generate-timesheet-date-end">
                                Date To *
                            </Label>
                            <Input
                                id="generate-timesheet-date-end"
                                type="date"
                                value={form.date_end}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        date_end: event.target.value,
                                    })
                                }
                            />
                            {errors.date_end && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.date_end}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <Label>Your signature *</Label>
                        <div className="mt-2">
                            <SignaturePad
                                caption={aideName}
                                height={160}
                                onSignatureChange={setSignature}
                            />
                        </div>
                        {errors.signature && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.signature}
                            </p>
                        )}
                    </div>
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        variant="outline"
                        onClick={closeAndReset}
                    >
                        Cancel
                    </Button>
                    <Button
                        id="submit-generate-timesheet"
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={processing || !isComplete}
                    >
                        {processing ? 'Generating...' : 'Generate Timesheet'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
