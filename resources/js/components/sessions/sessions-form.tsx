import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MultiSelect } from '@/components/ui/multi-select';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Client, ClientService, ServiceOffering } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

/**
 * One availed service the visit covers, and the share of it that service
 * accounts for. `hours` is left blank to accept the even split the server
 * would apply.
 */
interface ServiceAllocation {
    client_service_id: number;
    hours: string;
}

interface SessionFormData {
    client_id: string;
    therapist_id: string;
    linked_client_services: ServiceAllocation[];
    service_id: string;
    location: string;
    date: string;
    start_time: string;
    end_time: string;
    notes: string;
}

/** Minutes between two `HH:MM` values; null while either is incomplete. */
function minutesBetween(startTime: string, endTime: string): number | null {
    if (!startTime || !endTime) {
        return null;
    }

    const [startHour, startMinute] = startTime.split(':').map(Number);
    const [endHour, endMinute] = endTime.split(':').map(Number);
    const minutes = endHour * 60 + endMinute - (startHour * 60 + startMinute);

    return Number.isFinite(minutes) ? minutes : null;
}

/**
 * The even split the server applies when no hours are typed, remainder on
 * the last service so the parts still add up to the whole.
 *
 * Mirrors ServiceContractLedger::defaultAllocations() — if the two ever
 * disagree the form shows a total the server would reject.
 */
function evenSplit(count: number, totalHours: number): number[] {
    if (count === 0) {
        return [];
    }

    const share = Math.round((totalHours / count) * 100) / 100;

    return Array.from({ length: count }, (_, index) =>
        index === count - 1
            ? Math.round((totalHours - share * (count - 1)) * 100) / 100
            : share,
    );
}

/** Trims a trailing `.00` so 40 hours does not read as "40.00 hours". */
function formatHours(hours: number): string {
    return String(Math.round(hours * 100) / 100);
}

function initialValues(
    session?: ScheduleSession | null,
    preselectedClientId?: number | null,
): SessionFormData {
    const start = session ? new Date(session.scheduled_start) : null;
    const end = session?.scheduled_end ? new Date(session.scheduled_end) : null;

    return {
        client_id: session
            ? String(session.client_id)
            : preselectedClientId
              ? String(preselectedClientId)
              : '',
        therapist_id: session ? String(session.therapist_id) : '',
        linked_client_services: (session?.client_services ?? []).map(
            (clientService) => ({
                client_service_id: clientService.id,
                // Prefilled from the ledger row this session already wrote,
                // so reopening a booking shows the split it was saved with.
                hours: clientService.pivot
                    ? formatHours(Number(clientService.pivot.hours))
                    : '',
            }),
        ),
        service_id: session?.service_id ? String(session.service_id) : '',
        location: session?.location ?? '',
        date: start ? start.toISOString().slice(0, 10) : '',
        start_time: start ? start.toISOString().slice(11, 16) : '',
        end_time: end ? end.toISOString().slice(11, 16) : '',
        notes: session?.notes ?? '',
    };
}

/**
 * Shared admin/therapist "Add/Edit Session" form, reference:
 * cats-frontend/src/forms/SessionsForm.tsx. `scheduled_start`/`scheduled_end`
 * are computed server-side from date + start_time + duration.
 *
 * Phase 20 turned the availed-service picker into an hours claim: every
 * service offered here has a contract behind it, and the visit's length is
 * divided between the ones chosen.
 */
export default function SessionsForm({
    session,
    isAdmin,
    therapists,
    services,
    clients,
    preselectedClientId = null,
}: {
    session?: ScheduleSession | null;
    isAdmin: boolean;
    therapists: TherapistOption[];
    services: ServiceOffering[];
    clients: Client[];
    /** Set when arriving from a client's "Create Session" action. */
    preselectedClientId?: number | null;
}) {
    const isEdit = session != null;

    const { data, setData, post, put, processing, errors, transform } =
        useForm<SessionFormData>(initialValues(session, preselectedClientId));

    // A therapist's service always follows the client service they picked,
    // so the field they can't see is never posted stale.
    transform((values) => (isAdmin ? values : { ...values, service_id: '' }));

    const selectedClient = useMemo(
        () => clients.find((client) => String(client.id) === data.client_id),
        [clients, data.client_id],
    );

    // Replaces the information the removed Duration dropdown used to state
    // outright, so the booked length stays visible while picking times.
    const durationMinutes = useMemo(
        () => minutesBetween(data.start_time, data.end_time),
        [data.start_time, data.end_time],
    );

    const sessionHours = (durationMinutes ?? 0) / 60;

    // Memoised for its own sake: the `?? []` would otherwise hand back a new
    // array each render and re-run every hook that reads it.
    const availedServices = useMemo(
        () => selectedClient?.client_services ?? [],
        [selectedClient],
    );

    const clientServiceOptions = useMemo(
        () =>
            availedServices.map((clientService) => ({
                value: clientService.id,
                label: contractLabel(clientService),
            })),
        [availedServices],
    );

    const selectedIds = data.linked_client_services.map(
        (allocation) => allocation.client_service_id,
    );

    const defaults = evenSplit(selectedIds.length, sessionHours);

    /**
     * The hours actually claimed, reading a blank box as the even share the
     * server would fill in. Only meaningful once the times are set.
     */
    const claimedHours = data.linked_client_services.reduce(
        (total, allocation, index) =>
            total +
            (allocation.hours === ''
                ? (defaults[index] ?? 0)
                : Number(allocation.hours) || 0),
        0,
    );

    const splitMatches =
        durationMinutes === null ||
        Math.abs(claimedHours - sessionHours) <= 0.011;

    const changeSelection = (ids: number[]) => {
        setData(
            'linked_client_services',
            ids.map((id) => ({
                client_service_id: id,
                hours:
                    data.linked_client_services.find(
                        (allocation) => allocation.client_service_id === id,
                    )?.hours ?? '',
            })),
        );
    };

    const changeHours = (id: number, hours: string) => {
        setData(
            'linked_client_services',
            data.linked_client_services.map((allocation) =>
                allocation.client_service_id === id
                    ? { ...allocation, hours }
                    : allocation,
            ),
        );
    };

    const submit = () => {
        const prefix = isAdmin ? '/admin' : '/therapist';

        if (isEdit && session) {
            put(`${prefix}/sessions/${session.id}`);

            return;
        }

        post(`${prefix}/sessions`);
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6 lg:grid-cols-3">
            <Card className="rounded-[10px] lg:col-span-2">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <div>
                        <Label htmlFor="session-client">Client *</Label>
                        <Select
                            value={data.client_id}
                            onValueChange={(value) => {
                                setData('client_id', value);
                                setData('linked_client_services', []);
                            }}
                        >
                            <SelectTrigger
                                id="session-client"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select client" />
                            </SelectTrigger>
                            <SelectContent>
                                {clients.map((client) => (
                                    <SelectItem
                                        key={client.id}
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

                    {isAdmin && (
                        <div>
                            <Label htmlFor="session-therapist">
                                Therapist *
                            </Label>
                            <Select
                                value={data.therapist_id}
                                onValueChange={(value) =>
                                    setData('therapist_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="session-therapist"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Select therapist" />
                                </SelectTrigger>
                                <SelectContent>
                                    {therapists.map((therapist) => (
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
                            {errors.therapist_id && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.therapist_id}
                                </p>
                            )}
                        </div>
                    )}

                    {/*
                     * A single visit can cover more than one availed service,
                     * so this dropdown takes several answers. A service only
                     * appears while a contract covers today and has hours
                     * left on it.
                     */}
                    <div>
                        <Label htmlFor="session-client-services">
                            Client Services
                        </Label>
                        <MultiSelect
                            id="session-client-services"
                            className="mt-2 rounded-[10px]"
                            options={clientServiceOptions}
                            selected={selectedIds}
                            onChange={changeSelection}
                            placeholder="Select availed services"
                            emptyLabel={
                                selectedClient
                                    ? 'No contracted services with hours left'
                                    : 'Select a client first'
                            }
                        />
                        {errors.linked_client_services && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.linked_client_services}
                            </p>
                        )}
                    </div>

                    {/*
                     * Therapists don't pick a service: the client service
                     * they chose above already names it, and the server
                     * derives `service_id` from it.
                     */}
                    {isAdmin && (
                        <div>
                            <Label htmlFor="session-service">Service</Label>
                            <Select
                                value={data.service_id}
                                onValueChange={(value) =>
                                    setData('service_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="session-service"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue placeholder="Select service" />
                                </SelectTrigger>
                                <SelectContent>
                                    {services.map((service) => (
                                        <SelectItem
                                            key={service.id}
                                            value={String(service.id)}
                                        >
                                            {service.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    <div>
                        <Label htmlFor="session-location">Location</Label>
                        <Input
                            id="session-location"
                            value={data.location}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('location', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="session-date">Date *</Label>
                        <Input
                            id="session-date"
                            type="date"
                            value={data.date}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('date', event.target.value)
                            }
                        />
                        {errors.date && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.date}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="session-start-time">Start Time *</Label>
                        <Input
                            id="session-start-time"
                            type="time"
                            value={data.start_time}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('start_time', event.target.value)
                            }
                        />
                        {errors.start_time && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.start_time}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="session-end-time">End Time *</Label>
                        <Input
                            id="session-end-time"
                            type="time"
                            value={data.end_time}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('end_time', event.target.value)
                            }
                        />
                        {errors.end_time ? (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.end_time}
                            </p>
                        ) : (
                            durationMinutes !== null &&
                            durationMinutes > 0 && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {durationMinutes} minutes
                                </p>
                            )
                        )}
                    </div>

                    {/*
                     * The split only needs saying once more than one service
                     * is on the visit. A single service takes the whole of it
                     * and there is nothing to divide.
                     */}
                    {data.linked_client_services.length > 1 && (
                        <div className="md:col-span-2">
                            <Label>Hours per service</Label>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Leave a box empty to split the visit evenly.
                                Each service draws from its own contract.
                            </p>

                            <div className="mt-3 space-y-2">
                                {data.linked_client_services.map(
                                    (allocation, index) => {
                                        const clientService =
                                            availedServices.find(
                                                (candidate) =>
                                                    candidate.id ===
                                                    allocation.client_service_id,
                                            );

                                        return (
                                            <div
                                                key={
                                                    allocation.client_service_id
                                                }
                                                className="flex flex-row items-center justify-between gap-3 rounded-[5px] border p-3"
                                            >
                                                <div>
                                                    <p className="text-sm">
                                                        {clientService?.service
                                                            ?.name ?? 'Service'}
                                                    </p>
                                                    {clientService?.contract && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {formatHours(
                                                                clientService
                                                                    .contract
                                                                    .remaining_hours,
                                                            )}{' '}
                                                            hours left
                                                        </p>
                                                    )}
                                                </div>
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    step="0.25"
                                                    className="w-28 rounded-[10px]"
                                                    aria-label={`Hours for ${clientService?.service?.name ?? 'service'}`}
                                                    placeholder={formatHours(
                                                        defaults[index] ?? 0,
                                                    )}
                                                    value={allocation.hours}
                                                    onChange={(event) =>
                                                        changeHours(
                                                            allocation.client_service_id,
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                        );
                                    },
                                )}
                            </div>

                            <p
                                className={
                                    splitMatches
                                        ? 'mt-2 text-sm text-muted-foreground'
                                        : 'mt-2 text-sm text-destructive'
                                }
                            >
                                {formatHours(claimedHours)} of{' '}
                                {formatHours(sessionHours)} hours allocated
                            </p>
                        </div>
                    )}

                    {/*
                     * A therapist has no Therapist field to hang this on, so
                     * a clash with their own diary would otherwise reject the
                     * form with nothing on screen to explain it.
                     */}
                    {!isAdmin && errors.therapist_id && (
                        <p
                            id="session-therapist-conflict"
                            className="text-sm text-destructive md:col-span-2"
                        >
                            {errors.therapist_id}
                        </p>
                    )}

                    <div className="md:col-span-2">
                        <Label htmlFor="session-notes">Notes</Label>
                        <Textarea
                            id="session-notes"
                            value={data.notes}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('notes', event.target.value)
                            }
                        />
                    </div>

                    <div className="md:col-span-2">
                        <Button
                            className="rounded-[10px]"
                            onClick={submit}
                            disabled={processing}
                        >
                            {isEdit ? 'Save Changes' : 'Schedule Session'}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="font-bold text-primary">
                        Client Availability
                    </p>
                    <p className="text-sm text-muted-foreground">
                        From the client&apos;s intake application
                    </p>

                    <div className="mt-5">
                        <p className="text-xs text-muted-foreground">
                            Available Days
                        </p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {(
                                selectedClient?.original_intake
                                    ?.available_days ?? []
                            ).map((day) => (
                                <span
                                    key={day}
                                    className="rounded-[5px] border p-1 text-xs"
                                >
                                    {day}
                                </span>
                            ))}
                            {!selectedClient && (
                                <p className="text-sm text-muted-foreground">
                                    Select a client to see availability
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-5">
                        <p className="text-xs text-muted-foreground">
                            Preferred Times
                        </p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {(
                                selectedClient?.original_intake
                                    ?.preferred_times ?? []
                            ).map((time) => (
                                <span
                                    key={time}
                                    className="rounded-[5px] border p-1 text-xs"
                                >
                                    {time}
                                </span>
                            ))}
                        </div>
                    </div>

                    {/*
                     * The contract is what decides whether a service can be
                     * booked at all, so what is left on it belongs beside the
                     * picker rather than a click away.
                     */}
                    {availedServices.length > 0 && (
                        <div className="mt-5 border-t pt-5">
                            <p className="text-xs text-muted-foreground">
                                Contracted Hours
                            </p>
                            <div className="mt-2 space-y-2">
                                {availedServices.map((clientService) => (
                                    <div
                                        key={clientService.id}
                                        className="text-sm"
                                    >
                                        <p>
                                            {clientService.service?.name ??
                                                'Service'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {clientService.contract
                                                ? `${formatHours(clientService.contract.remaining_hours)} of ${formatHours(clientService.contract.allotted_hours)} hours left, to ${clientService.contract.period_end ?? '?'}`
                                                : 'No contract for today'}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

/**
 * A picker option carrying the two numbers that decide whether it can be
 * chosen: what is left, and how long that lasts.
 */
function contractLabel(clientService: ClientService): string {
    const name = clientService.service?.name ?? 'Service';
    const contract = clientService.contract;

    if (!contract) {
        return name;
    }

    return `${name} — ${formatHours(contract.remaining_hours)}h left${
        contract.period_end ? `, to ${contract.period_end}` : ''
    }`;
}
