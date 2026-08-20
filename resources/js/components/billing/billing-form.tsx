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
import type { TherapistOption } from '@/types/intake';
import type { InvoiceServiceOption } from '@/types/invoice';
import type { ScheduleSession } from '@/types/session';

const OTHER_SERVICE = 'other';

/** Intake funding sources billed at the FSCD rate (App\Models\InvoiceService). */
const FSCD_FUNDING_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

interface LineItemForm {
    service_id: string;
    name: string;
    quantity: string;
    rate: string;
}

interface BillingFormData {
    therapist_id: string;
    client_id: string;
    session_id: string;
    notes: string;
    services: LineItemForm[];
}

const EMPTY_LINE: LineItemForm = {
    service_id: '',
    name: '',
    quantity: '1',
    rate: '',
};

/**
 * The "Create Bill" form. Unlike the invoice form there are no dates and
 * nothing is sent — every service line here is saved as its own billing
 * item, and the month-end invoice is what turns them into a document.
 */
export default function BillingForm({
    basePath,
    sessionsPath,
    clients,
    services,
    therapists = [],
}: {
    basePath: string;
    /** Where the client's sessions are fetched from, for the session picker. */
    sessionsPath: string;
    clients: Client[];
    services: InvoiceServiceOption[];
    /** Admins only — a therapist always bills under their own name. */
    therapists?: TherapistOption[];
}) {
    const { data, setData, post, processing, errors, transform } =
        useForm<BillingFormData>({
            therapist_id: '',
            client_id: '',
            session_id: '',
            notes: '',
            services: [{ ...EMPTY_LINE }],
        });

    const [clientSessions, setClientSessions] = useState<ScheduleSession[]>([]);

    useEffect(() => {
        if (!data.client_id) {
            return;
        }

        const params = new URLSearchParams({
            user_id: data.client_id,
            role: 'client',
        });

        fetch(`${sessionsPath}/by-user?${params}`)
            .then((response) => response.json())
            .then(setClientSessions)
            .catch(() => setClientSessions([]));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.client_id]);

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
        (parseFloat(line.rate) || 0) * (parseFloat(line.quantity) || 0);

    const total = useMemo(
        () => data.services.reduce((sum, line) => sum + lineAmount(line), 0),
        [data.services],
    );

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

    /** The rate this bill charges a line at, or null when it is not billable. */
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

                return { ...line, rate: rateFor(service) ?? line.rate };
            }),
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fundingStream]);

    const updateLine = (
        index: number,
        field: keyof LineItemForm,
        value: string,
    ) => {
        setData(
            'services',
            data.services.map((line, lineIndex) =>
                lineIndex === index ? { ...line, [field]: value } : line,
            ),
        );
    };

    const selectService = (index: number, serviceId: string) => {
        setData(
            'services',
            data.services.map((line, lineIndex) => {
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
                    rate: rateFor(service) ?? line.rate,
                };
            }),
        );
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

    const submit = () => {
        transform((form) => ({
            ...form,
            // A therapist bills under their own name, so the field is only
            // filled in on the admin form.
            therapist_id: form.therapist_id || null,
            session_id: form.session_id || null,
            services: form.services.map((line) => ({
                // Carried so the monthly invoice can re-price this line off
                // the rate card rather than matching it by name.
                invoice_service_id:
                    line.service_id === OTHER_SERVICE ? null : line.service_id,
                name: line.name,
                quantity: line.quantity,
                rate: line.rate,
            })),
        }));

        post(basePath);
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6 lg:grid-cols-3">
            <Card className="rounded-[10px] lg:col-span-2">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {therapists.length > 0 && (
                            <div>
                                <Label htmlFor="billing-therapist">
                                    Therapist *
                                </Label>
                                <Select
                                    value={data.therapist_id}
                                    onValueChange={(value) =>
                                        setData('therapist_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="billing-therapist"
                                        className="mt-2 rounded-[10px]"
                                    >
                                        <SelectValue placeholder="Select therapist" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {therapists.map((therapist) => (
                                            <SelectItem
                                                key={therapist.id}
                                                id={`billing-therapist-${therapist.id}`}
                                                value={String(therapist.id)}
                                            >
                                                {therapist.first_name}{' '}
                                                {therapist.last_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Who delivered the work this bill is for.
                                </p>
                                {errors.therapist_id && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.therapist_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div>
                            <Label htmlFor="billing-client">Client *</Label>
                            <Select
                                value={data.client_id}
                                onValueChange={(value) => {
                                    setData('client_id', value);
                                    setData('session_id', '');
                                }}
                            >
                                <SelectTrigger
                                    id="billing-client"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((client) => (
                                        <SelectItem
                                            key={client.id}
                                            id={`billing-client-${client.id}`}
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
                            <Label htmlFor="billing-session">
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
                                    id="billing-session"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Link a session" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clientSessions.map((session) => (
                                        <SelectItem
                                            key={session.id}
                                            id={`billing-session-${session.id}`}
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
                                The visit this work was delivered in. Services
                                are chosen below.
                            </p>
                            {errors.session_id && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.session_id}
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
                        <p className="mb-3 text-xs text-muted-foreground">
                            Each line is saved as its own billing item.
                        </p>

                        <div className="grid grid-cols-1 gap-3">
                            {data.services.map((line, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-1 gap-3 rounded-[10px] border p-3 md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                                >
                                    <div>
                                        <Label
                                            htmlFor={`billing-service-name-${index}`}
                                        >
                                            Service Name
                                        </Label>
                                        <Combobox
                                            id={`billing-service-name-${index}`}
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
                                            htmlFor={`billing-rate-${index}`}
                                        >
                                            Rate ($)
                                        </Label>
                                        <Input
                                            id={`billing-rate-${index}`}
                                            type="number"
                                            min={0}
                                            step="0.01"
                                            value={line.rate}
                                            className="mt-1 rounded-[10px]"
                                            onChange={(event) =>
                                                updateLine(
                                                    index,
                                                    'rate',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label
                                            htmlFor={`billing-quantity-${index}`}
                                        >
                                            Quantity
                                        </Label>
                                        <Input
                                            id={`billing-quantity-${index}`}
                                            type="number"
                                            // Billable hours, so fractional:
                                            // 0.75 and 1.5 are real quantities.
                                            min={0.01}
                                            step="0.25"
                                            inputMode="decimal"
                                            value={line.quantity}
                                            className="mt-1 rounded-[10px]"
                                            onChange={(event) =>
                                                updateLine(
                                                    index,
                                                    'quantity',
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
                        <Label htmlFor="billing-notes">Notes (Optional)</Label>
                        <Textarea
                            id="billing-notes"
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
                            className="rounded-[10px]"
                            onClick={submit}
                            disabled={processing}
                        >
                            Save
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="font-bold text-primary">Summary</p>

                    <div className="mt-5 space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Items</span>
                            <span>{data.services.length}</span>
                        </div>
                        <div className="flex justify-between border-t pt-2 font-bold">
                            <span>Total</span>
                            <span>${total.toFixed(2)}</span>
                        </div>
                    </div>

                    <p className="mt-4 text-xs text-muted-foreground">
                        These items stay unbilled until the month closes and an
                        invoice is generated from them.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
