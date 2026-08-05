import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';

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
import type { Client, ServiceOffering } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

const DURATIONS = ['30 minutes', '45 minutes', '60 minutes', '90 minutes'];

interface SessionFormData {
    client_id: string;
    therapist_id: string;
    linked_client_service_id: string;
    service_id: string;
    location: string;
    date: string;
    start_time: string;
    duration: string;
    notes: string;
}

function initialValues(session?: ScheduleSession | null): SessionFormData {
    const start = session ? new Date(session.scheduled_start) : null;

    return {
        client_id: session ? String(session.client_id) : '',
        therapist_id: session ? String(session.therapist_id) : '',
        linked_client_service_id: session?.linked_client_service_id
            ? String(session.linked_client_service_id)
            : '',
        service_id: session?.service_id ? String(session.service_id) : '',
        location: session?.location ?? '',
        date: start ? start.toISOString().slice(0, 10) : '',
        start_time: start ? start.toISOString().slice(11, 16) : '',
        duration: session?.duration ?? DURATIONS[2],
        notes: session?.notes ?? '',
    };
}

/**
 * Shared admin/therapist "Add/Edit Session" form, reference:
 * cats-frontend/src/forms/SessionsForm.tsx. `scheduled_start`/`scheduled_end`
 * are computed server-side from date + start_time + duration.
 */
export default function SessionsForm({
    session,
    isAdmin,
    therapists,
    services,
    clients,
}: {
    session?: ScheduleSession | null;
    isAdmin: boolean;
    therapists: TherapistOption[];
    services: ServiceOffering[];
    clients: Client[];
}) {
    const isEdit = session != null;

    const { data, setData, post, put, processing, errors } =
        useForm<SessionFormData>(initialValues(session));

    const selectedClient = useMemo(
        () => clients.find((client) => String(client.id) === data.client_id),
        [clients, data.client_id],
    );

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
                                setData('linked_client_service_id', '');
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

                    <div>
                        <Label htmlFor="session-linked-service">
                            Client Service
                        </Label>
                        <Select
                            value={data.linked_client_service_id}
                            onValueChange={(value) =>
                                setData('linked_client_service_id', value)
                            }
                        >
                            <SelectTrigger
                                id="session-linked-service"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select availed service" />
                            </SelectTrigger>
                            <SelectContent>
                                {(selectedClient?.client_services ?? []).map(
                                    (clientService) => (
                                        <SelectItem
                                            key={clientService.id}
                                            value={String(clientService.id)}
                                        >
                                            {clientService.service?.name ??
                                                'Service'}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>

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
                        <Label htmlFor="session-duration">Duration *</Label>
                        <Select
                            value={data.duration}
                            onValueChange={(value) =>
                                setData('duration', value)
                            }
                        >
                            <SelectTrigger
                                id="session-duration"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {DURATIONS.map((duration) => (
                                    <SelectItem key={duration} value={duration}>
                                        {duration}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                </CardContent>
            </Card>
        </div>
    );
}
