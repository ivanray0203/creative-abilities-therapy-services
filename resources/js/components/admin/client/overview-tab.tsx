import { Check, UserCog, UserX } from 'lucide-react';
import { useState } from 'react';

import ReassignServiceModal from '@/components/admin/client/reassign-service-modal';
import ServiceModal from '@/components/admin/client/service-modal';
import {
    AvailabilityCard,
    ChildInformationCard,
    EducationCard,
    FundingInformationCard,
    GoalsCard,
    MedicalHistoryCard,
    OtherProgramsCard,
} from '@/components/admin/intake/overview-tab';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/helpers';
import type { Client, ClientService } from '@/types/client';
import type { DeclinedService, TherapistOption } from '@/types/intake';

interface AssignedTherapist {
    therapist: TherapistOption;
    isPrimary: boolean;
    services: string[];
}

/**
 * Everyone working with the child, once each: the primary therapist first,
 * then whoever holds one of the child's services, then the rest of the care
 * team. Each carries the services they provide.
 */
function listAssignedTherapists(client: Client): AssignedTherapist[] {
    const byId = new Map<number, AssignedTherapist>();
    const add = (therapist: TherapistOption | null | undefined) => {
        if (!therapist) {
            return null;
        }

        if (!byId.has(therapist.id)) {
            byId.set(therapist.id, {
                therapist,
                isPrimary: false,
                services: [],
            });
        }

        return byId.get(therapist.id)!;
    };

    const primary = add(client.primary_therapist ?? client.assigned_therapist);

    if (primary) {
        primary.isPrimary = true;
    }

    add(client.assigned_therapist);

    for (const clientService of client.client_services ?? []) {
        const assigned = add(clientService.therapist);
        const name = clientService.service?.name;

        if (assigned && name && !assigned.services.includes(name)) {
            assigned.services.push(name);
        }
    }

    for (const member of client.care_team ?? []) {
        add(member);
    }

    return [...byId.values()];
}

/**
 * Reference: cats-frontend/src/pages/admin/clientTabs/Overview.tsx
 *
 * The child's details come from the intake the client was approved from,
 * through the intake page's own section cards, so both pages show the same
 * data. The client-only parts — assigned therapists and availed services —
 * sit alongside them.
 */
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
    const assignedTherapists = listAssignedTherapists(client);

    return (
        <>
            <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                {intake && <ChildInformationCard intake={intake} />}

                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <UserCog className="text-primary" /> Assigned
                            Therapists
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-4">
                            {assignedTherapists.length > 0 ? (
                                assignedTherapists.map((assigned) => (
                                    <div
                                        key={assigned.therapist.id}
                                        className="border-b pb-4 last:border-b-0 last:pb-0"
                                    >
                                        <div className="flex flex-row flex-wrap items-center gap-2">
                                            <p>
                                                {assigned.therapist.first_name}{' '}
                                                {assigned.therapist.last_name}
                                            </p>
                                            {assigned.isPrimary && (
                                                <span className="rounded-[5px] bg-primary/10 px-2 py-0.5 text-xs text-primary">
                                                    Primary
                                                </span>
                                            )}
                                        </div>
                                        {assigned.therapist.email && (
                                            <p className="text-sm text-muted-foreground">
                                                {assigned.therapist.email}
                                            </p>
                                        )}
                                        {assigned.services.length > 0 && (
                                            <div className="mt-2 flex flex-row flex-wrap gap-2">
                                                {assigned.services.map(
                                                    (service) => (
                                                        <span
                                                            key={service}
                                                            className="rounded-[5px] border px-2 py-0.5 text-xs"
                                                        >
                                                            {service}
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))
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
                {intake && <EducationCard intake={intake} />}
                {intake && <AvailabilityCard intake={intake} />}
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                {intake && <MedicalHistoryCard intake={intake} />}
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
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
                                                              formatDate(
                                                                  declined.decided_at,
                                                              )
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
            </div>

            {intake && (
                <>
                    <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                        <FundingInformationCard intake={intake} />
                    </div>
                    <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                        <OtherProgramsCard intake={intake} />
                    </div>
                    <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                        <GoalsCard intake={intake} />
                    </div>
                </>
            )}

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
