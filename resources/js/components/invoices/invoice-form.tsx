import { useForm } from '@inertiajs/react';
import { Plus, Trash } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { formatScheduledDate } from '@/lib/helpers';
import { sessionServiceLabel, sessionServiceRefs } from '@/lib/sessions';
import type { Client, ServiceOffering } from '@/types/client';
import type { Invoice } from '@/types/invoice';
import type { ScheduleSession } from '@/types/session';

const OTHER_SERVICE = 'other';

interface LineItemForm {
    service_id: string;
    name: string;
    description: string;
    numberOfSessions: string;
    rate_numeric: string;
}

interface InvoiceFormData {
    client_id: string;
    session_id: string;
    linked_therapist_invoice_id: string;
    invoice_date: string;
    due_date: string;
    notes: string;
    services: LineItemForm[];
}

const EMPTY_LINE: LineItemForm = {
    service_id: '',
    name: '',
    description: '',
    numberOfSessions: '1',
    rate_numeric: '',
};

function initialValues(
    invoice: Invoice | null | undefined,
    services: ServiceOffering[],
): InvoiceFormData {
    return {
        client_id: invoice ? String(invoice.client_id) : '',
        session_id: invoice?.session_id ? String(invoice.session_id) : '',
        linked_therapist_invoice_id: invoice?.linked_therapist_invoice_id
            ? String(invoice.linked_therapist_invoice_id)
            : '',
        invoice_date:
            invoice?.invoice_date ?? new Date().toISOString().slice(0, 10),
        due_date:
            invoice?.due_date ??
            new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                .toISOString()
                .slice(0, 10),
        notes: invoice?.notes ?? '',
        services: invoice
            ? invoice.services.map((line) => {
                  const matched = services.find(
                      (service) => service.name === line.name,
                  );

                  return {
                      service_id: matched ? String(matched.id) : OTHER_SERVICE,
                      name: line.name,
                      description: line.description ?? '',
                      numberOfSessions: String(line.numberOfSessions),
                      rate_numeric: String(line.rate_numeric),
                  };
              })
            : [{ ...EMPTY_LINE }],
    };
}

/**
 * One invoice line per service the visit covered, priced from the matching
 * service offering. A service the clinic no longer offers still gets a line,
 * as "Other" with its name filled in, rather than being dropped.
 */
function linesFromSession(
    session: ScheduleSession,
    services: ServiceOffering[],
): LineItemForm[] {
    return sessionServiceRefs(session).map((ref) => {
        const option = services.find(
            (service) =>
                (ref.id !== null && service.id === ref.id) ||
                service.name === ref.name,
        );

        return {
            service_id: option ? String(option.id) : OTHER_SERVICE,
            name: option?.name ?? ref.name,
            description: '',
            // One visit, so one of each service it delivered.
            numberOfSessions: '1',
            rate_numeric: option?.base_price ?? '',
        };
    });
}

/**
 * Shared admin/therapist "Add/Edit Invoice" form, reference:
 * cats-frontend/src/forms/InvoiceForm.tsx. Totals shown here are a live
 * client-side preview of `Invoice::calculateTotals()`'s formula — the
 * server recomputes authoritatively on submit.
 */
export default function InvoiceForm({
    invoice,
    basePath,
    clients,
    services,
    therapistInvoices = [],
}: {
    invoice?: Invoice | null;
    basePath: string;
    clients: Client[];
    services: ServiceOffering[];
    /** Admins only — the therapist bills this invoice can recover. */
    therapistInvoices?: Invoice[];
}) {
    const isEdit = invoice != null;

    const { data, setData, post, put, processing, errors, transform } =
        useForm<InvoiceFormData>(initialValues(invoice, services));

    const [clientSessions, setClientSessions] = useState<ScheduleSession[]>(
        invoice?.session ? [invoice.session] : [],
    );

    /*
     * The lines this form last wrote from a session. Kept so picking a
     * different session can replace them, while anything the user typed —
     * or an existing invoice's own lines — is left alone.
     */
    const [autoFilledLines, setAutoFilledLines] = useState<
        LineItemForm[] | null
    >(null);

    useEffect(() => {
        if (!data.client_id) {
            return;
        }

        const params = new URLSearchParams({
            user_id: data.client_id,
            role: 'client',
        });

        fetch(`${basePath.replace('/invoices', '/sessions')}/by-user?${params}`)
            .then((response) => response.json())
            .then(setClientSessions)
            .catch(() => setClientSessions([]));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.client_id]);

    /*
     * The clinic bills the family for work a therapist already billed the
     * clinic for. Pointing one at the other is what lets the admin read what
     * they are owed and what they owe as a pair.
     */
    const recoverableInvoices = useMemo(
        () =>
            therapistInvoices.filter(
                (therapistInvoice) =>
                    String(therapistInvoice.client_id) === data.client_id,
            ),
        [therapistInvoices, data.client_id],
    );

    /*
     * New invoices carry no GST. An invoice raised before that change keeps
     * the rate it was billed at, so editing one still shows and charges it —
     * the server leaves `tax_percentage` untouched on update.
     */
    const taxPercentage = parseFloat(invoice?.tax_percentage ?? '0') || 0;

    const totals = useMemo(() => {
        const subTotal = data.services.reduce((sum, line) => {
            const rate = parseFloat(line.rate_numeric) || 0;
            const sessions = parseInt(line.numberOfSessions, 10) || 0;

            return sum + rate * sessions;
        }, 0);
        const gst = Math.round(subTotal * (taxPercentage / 100) * 100) / 100;

        return { subTotal, gst, total: subTotal + gst };
    }, [data.services, taxPercentage]);

    const updateLine = (
        index: number,
        field: keyof LineItemForm,
        value: string,
    ) => {
        const updatedLines = data.services.map((line, lineIndex) =>
            lineIndex === index ? { ...line, [field]: value } : line,
        );
        setData('services', updatedLines);
    };

    const selectService = (index: number, serviceId: string) => {
        const updatedLines = data.services.map((line, lineIndex) => {
            if (lineIndex !== index) {
                return line;
            }

            if (serviceId === OTHER_SERVICE) {
                return { ...line, service_id: serviceId, name: '' };
            }

            const service = services.find(
                (option) => String(option.id) === serviceId,
            );

            return {
                ...line,
                service_id: serviceId,
                name: service?.name ?? '',
                rate_numeric: service?.base_price ?? line.rate_numeric,
            };
        });

        setData('services', updatedLines);
    };

    /**
     * Safe to overwrite when the lines are still the blank starting state, or
     * are exactly what a previous session selection put there.
     */
    const linesAreUnedited = () =>
        autoFilledLines !== null
            ? JSON.stringify(data.services) === JSON.stringify(autoFilledLines)
            : data.services.every(
                  (line) =>
                      !line.name && !line.description && !line.rate_numeric,
              );

    const selectSession = (sessionId: string) => {
        setData('session_id', sessionId);

        const session = clientSessions.find(
            (option) => String(option.id) === sessionId,
        );

        if (!session) {
            return;
        }

        const lines = linesFromSession(session, services);

        if (lines.length === 0 || !linesAreUnedited()) {
            return;
        }

        setData('services', lines);
        setAutoFilledLines(lines);
    };

    const addLine = () => {
        setData('services', [...data.services, { ...EMPTY_LINE }]);
    };

    const removeLine = (index: number) => {
        setData(
            'services',
            data.services.filter((_, lineIndex) => lineIndex !== index),
        );
    };

    const submit = (action: 'draft' | 'send') => {
        transform((form) => ({
            ...form,
            action,
            session_id: form.session_id || null,
            services: form.services.map((line) => ({
                name: line.name,
                description: line.description,
                numberOfSessions: line.numberOfSessions,
                rate_numeric: line.rate_numeric,
            })),
        }));

        if (isEdit && invoice) {
            put(`${basePath}/${invoice.id}`);

            return;
        }

        post(basePath);
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6 lg:grid-cols-3">
            <Card className="rounded-[10px] lg:col-span-2">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <Label htmlFor="invoice-client">Client *</Label>
                            <Select
                                value={data.client_id}
                                onValueChange={(value) => {
                                    setData('client_id', value);
                                    setData('session_id', '');
                                    setAutoFilledLines(null);
                                }}
                            >
                                <SelectTrigger
                                    id="invoice-client"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((client) => (
                                        <SelectItem
                                            key={client.id}
                                            id={`invoice-client-${client.id}`}
                                            value={String(client.id)}
                                        >
                                            {client.original_intake
                                                ? `${client.original_intake.child_first_name} ${client.original_intake.child_last_name}`
                                                : `Client #${client.id}`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.client_id && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.client_id}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="invoice-session">
                                Session (Optional)
                            </Label>
                            <Select
                                value={data.session_id}
                                onValueChange={selectSession}
                                disabled={
                                    !data.client_id ||
                                    clientSessions.length === 0
                                }
                            >
                                <SelectTrigger
                                    id="invoice-session"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Link a session" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clientSessions.map((session) => (
                                        <SelectItem
                                            key={session.id}
                                            id={`invoice-session-${session.id}`}
                                            value={String(session.id)}
                                        >
                                            {formatScheduledDate(
                                                session.scheduled_start,
                                            )}{' '}
                                            —{' '}
                                            {sessionServiceLabel(
                                                session,
                                                'Session',
                                            )}{' '}
                                            ({session.status})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Fills the services below, and marks the session
                                completed once this invoice is paid.
                            </p>
                            {errors.session_id && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.session_id}
                                </p>
                            )}
                        </div>

                        {therapistInvoices.length > 0 && (
                            <div>
                                <Label htmlFor="invoice-recovers">
                                    Recovers Therapist Invoice (Optional)
                                </Label>
                                <Select
                                    value={data.linked_therapist_invoice_id}
                                    onValueChange={(value) =>
                                        setData(
                                            'linked_therapist_invoice_id',
                                            value,
                                        )
                                    }
                                    disabled={
                                        !data.client_id ||
                                        recoverableInvoices.length === 0
                                    }
                                >
                                    <SelectTrigger
                                        id="invoice-recovers"
                                        className="mt-2 rounded-[10px]"
                                    >
                                        <SelectValue placeholder="Link the therapist's bill" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {recoverableInvoices.map(
                                            (therapistInvoice) => (
                                                <SelectItem
                                                    key={therapistInvoice.id}
                                                    value={String(
                                                        therapistInvoice.id,
                                                    )}
                                                >
                                                    {therapistInvoice.invoice_id ??
                                                        `#${therapistInvoice.id}`}{' '}
                                                    —{' '}
                                                    {therapistInvoice.therapist
                                                        ? `${therapistInvoice.therapist.first_name} ${therapistInvoice.therapist.last_name}`
                                                        : 'Therapist'}{' '}
                                                    — $
                                                    {Number(
                                                        therapistInvoice.total,
                                                    ).toFixed(2)}{' '}
                                                    ({therapistInvoice.status})
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    What the clinic owes the therapist for this
                                    work, shown alongside this invoice.
                                </p>
                                {errors.linked_therapist_invoice_id && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.linked_therapist_invoice_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div>
                            <Label htmlFor="invoice-date">Invoice Date *</Label>
                            <Input
                                id="invoice-date"
                                type="date"
                                value={data.invoice_date}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('invoice_date', event.target.value)
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="invoice-due-date">Due Date *</Label>
                            <Input
                                id="invoice-due-date"
                                type="date"
                                value={data.due_date}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('due_date', event.target.value)
                                }
                            />
                            {errors.due_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.due_date}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <div className="mb-2 flex items-center justify-between">
                            <Label>Services *</Label>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="rounded-[10px]"
                                onClick={addLine}
                            >
                                <Plus className="h-4 w-4" /> Add Line
                            </Button>
                        </div>

                        <div className="grid grid-cols-1 gap-3">
                            {data.services.map((line, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-1 gap-3 rounded-[10px] border p-3 md:grid-cols-[2fr_1fr_1fr_auto]"
                                >
                                    <div>
                                        <Label
                                            htmlFor={`service-name-${index}`}
                                        >
                                            Service Name
                                        </Label>
                                        <Select
                                            value={line.service_id}
                                            onValueChange={(value) =>
                                                selectService(index, value)
                                            }
                                        >
                                            <SelectTrigger
                                                id={`service-name-${index}`}
                                                className="mt-1 rounded-[10px]"
                                            >
                                                <SelectValue placeholder="Select service" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {services.map((service) => (
                                                    <SelectItem
                                                        key={service.id}
                                                        value={String(
                                                            service.id,
                                                        )}
                                                    >
                                                        {service.name}
                                                    </SelectItem>
                                                ))}
                                                <SelectItem
                                                    value={OTHER_SERVICE}
                                                >
                                                    Other
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {line.service_id === OTHER_SERVICE && (
                                            <Input
                                                value={line.name}
                                                placeholder="Enter service name"
                                                className="mt-2 rounded-[10px]"
                                                onChange={(event) =>
                                                    updateLine(
                                                        index,
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        )}
                                    </div>
                                    <div>
                                        <Label
                                            htmlFor={`service-sessions-${index}`}
                                        >
                                            Sessions
                                        </Label>
                                        <Input
                                            id={`service-sessions-${index}`}
                                            type="number"
                                            min={1}
                                            value={line.numberOfSessions}
                                            className="mt-1 rounded-[10px]"
                                            onChange={(event) =>
                                                updateLine(
                                                    index,
                                                    'numberOfSessions',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label
                                            htmlFor={`service-rate-${index}`}
                                        >
                                            Rate ($)
                                        </Label>
                                        <Input
                                            id={`service-rate-${index}`}
                                            type="number"
                                            min={0}
                                            step="0.01"
                                            value={line.rate_numeric}
                                            className="mt-1 rounded-[10px]"
                                            onChange={(event) =>
                                                updateLine(
                                                    index,
                                                    'rate_numeric',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="rounded-[10px] text-red-700 hover:bg-red-600 hover:text-white"
                                            onClick={() => removeLine(index)}
                                            disabled={
                                                data.services.length === 1
                                            }
                                        >
                                            <Trash className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {errors.services && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.services}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="invoice-notes">Notes</Label>
                        <Textarea
                            id="invoice-notes"
                            value={data.notes}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('notes', event.target.value)
                            }
                        />
                    </div>

                    <div className="flex gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => submit('draft')}
                            disabled={processing}
                        >
                            Save as Draft
                        </Button>
                        <Button
                            type="button"
                            className="rounded-[10px]"
                            onClick={() => submit('send')}
                            disabled={processing}
                        >
                            Save & Send
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="font-bold text-primary">Summary</p>

                    <div className="mt-5 space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Sub Total
                            </span>
                            <span>${totals.subTotal.toFixed(2)}</span>
                        </div>
                        {taxPercentage > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    GST ({taxPercentage}%)
                                </span>
                                <span>${totals.gst.toFixed(2)}</span>
                            </div>
                        )}
                        <div className="flex justify-between border-t pt-2 font-bold">
                            <span>Total</span>
                            <span>${totals.total.toFixed(2)}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
