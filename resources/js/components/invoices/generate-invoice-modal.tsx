import { useForm } from '@inertiajs/react';

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
import type { Client } from '@/types/client';

interface GenerateInvoiceForm {
    client_id: string;
    date_start: string;
    date_end: string;
}

const EMPTY_FORM: GenerateInvoiceForm = {
    client_id: '',
    date_start: '',
    date_end: '',
};

/**
 * "Create Invoice" on the invoices list: raises an invoice for everything
 * billed between two dates.
 *
 * An admin bills one family at a time, so they pick the client — only those
 * with bills still waiting to be invoiced are offered. A therapist bills the
 * clinic for all their own work in the period, whoever it was for, so they
 * give dates alone.
 */
export default function GenerateInvoiceModal({
    basePath,
    clients,
    isOpen,
    onClose,
}: {
    basePath: string;
    /** Admins only; omitted when the biller invoices for their own work. */
    clients?: Client[];
    isOpen: boolean;
    onClose: () => void;
}) {
    const picksClient = clients !== undefined;
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<GenerateInvoiceForm>({ ...EMPTY_FORM });

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        post(`${basePath}/generate-from-billing`, {
            preserveScroll: true,
            onSuccess: () => closeAndReset(),
        });
    };

    const isComplete =
        (!picksClient || data.client_id !== '') &&
        data.date_start !== '' &&
        data.date_end !== '';

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="max-h-[90vh] w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Create Invoice
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-3">
                    {picksClient && (
                        <div>
                            <Label htmlFor="generate-invoice-client">
                                Client *
                            </Label>
                            <Select
                                value={data.client_id}
                                onValueChange={(value) =>
                                    setData('client_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="generate-invoice-client"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    {(clients ?? []).map((client) => (
                                        <SelectItem
                                            key={client.id}
                                            id={`generate-invoice-client-${client.id}`}
                                            value={String(client.id)}
                                        >
                                            {client.original_intake
                                                ? `${client.original_intake.child_first_name} ${client.original_intake.child_last_name}`
                                                : `Client #${client.id}`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {clients?.length === 0 && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    No client has billing waiting to be
                                    invoiced.
                                </p>
                            )}
                            {errors.client_id && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.client_id}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="generate-invoice-date-start">
                                Date Start *
                            </Label>
                            <Input
                                id="generate-invoice-date-start"
                                type="date"
                                value={data.date_start}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('date_start', event.target.value)
                                }
                            />
                            {errors.date_start && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.date_start}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="generate-invoice-date-end">
                                Date End *
                            </Label>
                            <Input
                                id="generate-invoice-date-end"
                                type="date"
                                value={data.date_end}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('date_end', event.target.value)
                                }
                            />
                            {errors.date_end && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.date_end}
                                </p>
                            )}
                        </div>
                    </div>

                    <p className="text-xs text-muted-foreground">
                        Every bill raised for this client between these dates
                        goes onto the invoice, which is emailed to the family as
                        soon as it is generated.
                    </p>
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
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={processing || !isComplete}
                    >
                        Generate Invoice
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
