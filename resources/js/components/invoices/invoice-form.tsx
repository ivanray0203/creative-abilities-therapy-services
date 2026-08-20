import { useForm } from '@inertiajs/react';
import { Plus, Trash } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import type { ComboboxOption } from '@/components/ui/combobox';
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
import { sessionServiceLabel } from '@/lib/sessions';
import type { Client } from '@/types/client';
import type { Invoice, InvoiceServiceOption } from '@/types/invoice';
import type { ScheduleSession } from '@/types/session';

const OTHER_SERVICE = 'other';

/** Intake funding sources billed at the FSCD rate (App\Models\InvoiceService). */
const FSCD_FUNDING_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

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
    services: InvoiceServiceOption[],
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
                      (service) =>
                          (line.invoice_service_id != null &&
                              service.id === line.invoice_service_id) ||
                          service.name === line.name,
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
    services: InvoiceServiceOption[];
    /** Admins only — the therapist bills this invoice can recover. */
    therapistInvoices?: Invoice[];
}) {
    const isEdit = invoice != null;

    const { data, setData, post, put, processing, errors, transform } =
        useForm<InvoiceFormData>(initialValues(invoice, services));

    const [clientSessions, setClientSessions] = useState<ScheduleSession[]>(
        invoice?.session ? [invoice.session] : [],
    );

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
     * The rate card as combobox options. Searchable on the code as well as
     * the name, so "ot-home" finds the line as readily as "Occupational".
     */
    const serviceOptions = useMemo<ComboboxOption[]>(
        () => [
            ...services.map((service) => ({
                value: String(service.id),
                label: service.name,
                keywords: service.code,
            })),
            { value: OTHER_SERVICE, label: 'Other' },
        ],
        [services],
    );

    /**
     * Quantity times the rate this line bills at. Quantity is billable hours,
     * so it is parsed as a float — parseInt would bill 1.5 hours as 1.
     */
    const lineAmount = (line: LineItemForm): number =>
        (parseFloat(line.rate_numeric) || 0) *
        (parseFloat(line.numberOfSessions) || 0);

    /*
     * New invoices carry no GST. An invoice raised before that change keeps
     * the rate it was billed at, so editing one still shows and charges it —
     * the server leaves `tax_percentage` untouched on update.
     */
    const taxPercentage = parseFloat(invoice?.tax_percentage ?? '0') || 0;

    const totals = useMemo(() => {
        const subTotal = data.services.reduce(
            (sum, line) => sum + lineAmount(line),
            0,
        );
        const gst = Math.round(subTotal * (taxPercentage / 100) * 100) / 100;

        return { subTotal, gst, total: subTotal + gst };
    }, [data.services, taxPercentage]);

    /**
     * Which rate column this client bills against. FSCD-funded care bills the
     * FSCD rate; Insurance and private care bill the private/insurance rate.
     */
    const fundingStream = useMemo(() => {
        const client = clients.find(
            (option) => String(option.id) === data.client_id,
        );

        return FSCD_FUNDING_SOURCES.includes(
            client?.original_intake?.funding_source ?? '',
        )
            ? 'rate_fscd'
            : 'rate_private';
    }, [clients, data.client_id]);

    /** The rate this invoice bills a line at, or null when it is not billable. */
    const rateFor = (
        service: InvoiceServiceOption | undefined,
    ): string | null => service?.[fundingStream] ?? null;

    /*
     * The funding source sets the price, so switching client re-prices every
     * line that came from the rate card. A hand-typed "Other" line is left as
     * the user wrote it.
     */
    useEffect(() => {
        if (!data.client_id) {
            return;
        }

        setData(
            'services',
            data.services.map((line) => {
                if (!line.service_id || line.service_id === OTHER_SERVICE) {
                    return line;
                }

                const service = services.find(
                    (option) => String(option.id) === line.service_id,
                );

                return {
                    ...line,
                    rate_numeric: rateFor(service) ?? line.rate_numeric,
                };
            }),
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fundingStream]);

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
                rate_numeric: rateFor(service) ?? line.rate_numeric,
            };
        });

        setData('services', updatedLines);
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
                // Carried so the clinic's monthly invoice can re-price this
                // line off the rate card rather than matching it by name.
                invoice_service_id:
                    line.service_id === OTHER_SERVICE ? null : line.service_id,
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
                                onValueChange={(value) =>
                                    setData('session_id', value)
                                }
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
                                Marks the session completed once this invoice is
                                paid. Services are chosen below.
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
                                    className="grid grid-cols-1 gap-3 rounded-[10px] border p-3 md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                                >
                                    <div>
                                        <Label
                                            htmlFor={`service-name-${index}`}
                                        >
                                            Service Name
                                        </Label>
                                        <Combobox
                                            id={`service-name-${index}`}
                                            className="mt-1 rounded-[10px]"
                                            value={line.service_id}
                                            onChange={(value) =>
                                                selectService(index, value)
                                            }
                                            options={serviceOptions}
                                            placeholder="Select service"
                                            searchPlaceholder="Search services..."
                                            emptyLabel="No service matches that search."
                                        />
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
                                    <div>
                                        <Label
                                            htmlFor={`service-sessions-${index}`}
                                        >
                                            Quantity
                                        </Label>
                                        <Input
                                            id={`service-sessions-${index}`}
                                            type="number"
                                            // Billable hours, so fractional:
                                            // 0.75 and 1.5 are real quantities.
                                            min={0.01}
                                            step="0.25"
                                            inputMode="decimal"
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
                                        <Label>Amount</Label>
                                        <p className="mt-1 flex h-9 items-center font-medium">
                                            ${lineAmount(line).toFixed(2)}
                                        </p>
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
