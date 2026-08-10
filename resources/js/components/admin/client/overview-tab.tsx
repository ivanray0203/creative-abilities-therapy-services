import {
    Calendar,
    Check,
    LucideBriefcaseMedical,
    Mail,
    Phone,
    User,
    UserCog,
    UserX,
} from 'lucide-react';
import { useState } from 'react';

import ReassignServiceModal from '@/components/admin/client/reassign-service-modal';
import ServiceModal from '@/components/admin/client/service-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { capitalize } from '@/lib/helpers';
import type { Client, ClientService } from '@/types/client';
import type { DeclinedService, TherapistOption } from '@/types/intake';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Overview.tsx */
export default function OverviewTab({
    client,
    declinedServices = [],
    therapists = [],
    isPreview,
}: {
    client: Client;
    declinedServices?: DeclinedService[];
    therapists?: TherapistOption[];
    isPreview?: boolean;
}) {
    const [selectedService, setSelectedService] =
        useState<ClientService | null>(null);
    const [reassigning, setReassigning] = useState<DeclinedService | null>(
        null,
    );
    const intake = client.original_intake;
    const currentServices = client.client_services ?? [];
    const availedNames = new Set(
        currentServices.map((service) => service.service?.name),
    );
    const requestedServices = (intake?.services_needed ?? []).filter(
        (service) => !availedNames.has(service),
    );

    return (
        <>
            <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <User className="text-primary" /> Child Information
                        </p>

                        <div className="mt-10 grid grid-cols-2 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Full Name
                                </p>
                                <p>
                                    {intake?.child_first_name}{' '}
                                    {intake?.child_last_name}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Gender
                                </p>
                                <p>{capitalize(intake?.gender ?? null)}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Date of Birth
                                </p>
                                <p>{intake?.date_of_birth}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Age
                                </p>
                                <p>{intake?.age} years old</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <UserCog className="text-primary" /> Assigned
                            Therapist
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            {client.assigned_therapist ? (
                                <div>
                                    <p>
                                        {client.assigned_therapist.first_name}{' '}
                                        {client.assigned_therapist.last_name}
                                    </p>
                                    {client.assigned_therapist.email && (
                                        <p className="text-sm text-muted-foreground">
                                            {client.assigned_therapist.email}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-muted-foreground">
                                    No therapist assigned yet
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <User className="text-primary" /> Primary
                            Parent/Guardian
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-3">
                            <p>{intake?.primary_parent_name}</p>
                            <div className="flex flex-row items-center gap-3">
                                <Mail className="h-4 w-4 text-muted-foreground" />
                                <p>{intake?.primary_parent_email || '-'}</p>
                            </div>
                            <div className="flex flex-row items-center gap-3">
                                <Phone className="h-4 w-4 text-muted-foreground" />
                                <p>{intake?.primary_parent_phone || '-'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-[10px] border border-primary bg-gradient-to-b from-primary/20 to-transparent">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <User className="text-primary" /> Emergency Contact
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-3">
                            <p>{intake?.emergency_contact_name || '-'}</p>
                            <p className="text-sm text-muted-foreground">
                                {capitalize(
                                    intake?.emergency_contact_relationship ??
                                        null,
                                )}
                            </p>
                            <div className="flex flex-row items-center gap-3">
                                <Phone className="h-4 w-4" />
                                <p>{intake?.emergency_contact_phone || '-'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <LucideBriefcaseMedical className="text-primary" />{' '}
                            Medical History
                        </p>

                        <div className="mt-10 border-b pb-5">
                            <p className="text-xs text-muted-foreground">
                                Diagnosis
                            </p>
                            <div className="mt-3 flex flex-row flex-wrap gap-3">
                                {(intake?.diagnosis ?? []).map((diagnosis) => (
                                    <p
                                        key={diagnosis}
                                        className="rounded-[5px] bg-gray-500 p-1 text-sm text-white"
                                    >
                                        {diagnosis}
                                    </p>
                                ))}
                            </div>
                        </div>

                        <div className="mt-5">
                            <p className="text-xs text-muted-foreground">
                                Medical Conditions
                            </p>
                            <p>{intake?.medical_conditions || '-'}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Check className="text-primary" /> Current Services
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-3">
                            {currentServices.length > 0 ? (
                                currentServices.map((service) => (
                                    <button
                                        key={service.id}
                                        type="button"
                                        disabled={isPreview}
                                        onClick={() =>
                                            setSelectedService(service)
                                        }
                                        className="flex flex-row items-center justify-between rounded-[5px] border p-3 text-left hover:bg-charcoal-gray/5"
                                    >
                                        <span>
                                            {service.service?.name ?? 'Service'}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {service.frequency}
                                        </span>
                                    </button>
                                ))
                            ) : (
                                <p className="text-muted-foreground">
                                    No services availed yet
                                </p>
                            )}
                        </div>

                        {requestedServices.length > 0 && (
                            <div className="mt-6 border-t pt-5">
                                <p className="text-xs text-muted-foreground">
                                    Requested (not yet availed)
                                </p>
                                <div className="mt-3 flex flex-row flex-wrap gap-3">
                                    {requestedServices.map((service) => (
                                        <p
                                            key={service}
                                            className="rounded-[5px] border p-1 text-sm"
                                        >
                                            {service}
                                        </p>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/*
                         * A declined service leaves no ClientService behind and
                         * the intake drops off the admin list once the child is
                         * promoted, so without this it would sit silently among
                         * the requested ones with nothing prompting anyone to
                         * act on it.
                         */}
                        {declinedServices.length > 0 && (
                            <div className="mt-6 border-t pt-5">
                                <p className="flex items-center gap-2 text-xs text-destructive">
                                    <UserX className="h-4 w-4" /> Declined by
                                    therapist
                                </p>
                                <div className="mt-3 grid grid-cols-1 gap-3">
                                    {declinedServices.map((declined) => (
                                        <div
                                            key={declined.service}
                                            className="rounded-[5px] border border-destructive/40 bg-destructive/5 p-3"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-2">
                                                <div>
                                                    <p className="font-medium">
                                                        {declined.service}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {declined.therapist ??
                                                            'Therapist'}
                                                        {declined.decided_at
                                                            ? ' \u00b7 ' +
                                                              declined.decided_at
                                                            : ''}
                                                    </p>
                                                </div>

                                                {!isPreview && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="rounded-[5px]"
                                                        onClick={() =>
                                                            setReassigning(
                                                                declined,
                                                            )
                                                        }
                                                    >
                                                        Reassign
                                                    </Button>
                                                )}
                                            </div>

                                            {declined.notes && (
                                                <p className="mt-2 text-sm text-muted-foreground">
                                                    &ldquo;{declined.notes}
                                                    &rdquo;
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Calendar className="text-primary" /> Availability
                        </p>

                        <div className="mt-10 grid gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Available Days
                                </p>
                                <div className="flex flex-row flex-wrap gap-3">
                                    {(intake?.available_days ?? []).map(
                                        (day) => (
                                            <p
                                                key={day}
                                                className="rounded-[5px] border p-1 text-sm"
                                            >
                                                {day}
                                            </p>
                                        ),
                                    )}
                                </div>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Preferred Times
                                </p>
                                <div className="flex flex-row flex-wrap gap-3">
                                    {(intake?.preferred_times ?? []).map(
                                        (time) => (
                                            <p
                                                key={time}
                                                className="rounded-[5px] border p-1 text-sm"
                                            >
                                                {time}
                                            </p>
                                        ),
                                    )}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {!isPreview && (
                <>
                    <ServiceModal
                        clientService={selectedService}
                        isOpen={selectedService !== null}
                        onClose={() => setSelectedService(null)}
                    />

                    {client.original_intake_id !== null && (
                        <ReassignServiceModal
                            intakeId={client.original_intake_id}
                            service={reassigning?.service ?? null}
                            declinedByTherapistId={reassigning?.therapist_id}
                            therapists={therapists}
                            isOpen={reassigning !== null}
                            onClose={() => setReassigning(null)}
                        />
                    )}
                </>
            )}
        </>
    );
}
