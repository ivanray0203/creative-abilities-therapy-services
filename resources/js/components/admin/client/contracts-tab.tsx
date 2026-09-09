import { router } from '@inertiajs/react';
import { Ban, Plus, Trash } from 'lucide-react';
import { useState } from 'react';

import { ServiceContractBadge } from '@/components/admin/client/badges';
import ServiceContractModal from '@/components/admin/client/service-contract-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/helpers';
import type { Client, ClientService, ServiceContract } from '@/types/client';

/**
 * Phase 20 — the hours admin has authorized, one block per availed service.
 *
 * A therapist can only schedule against a service that has a contract
 * covering the day, with hours still on it, so this tab is where a service
 * is switched on and off. Nothing here is a therapist's to change.
 */
export default function ContractsTab({
    client,
    fundingCodes,
}: {
    client: Client;
    fundingCodes: string[];
}) {
    const [issuingFor, setIssuingFor] = useState<ClientService | null>(null);
    const [editing, setEditing] = useState<{
        clientService: ClientService;
        contract: ServiceContract;
    } | null>(null);

    const services = client.client_services ?? [];

    const cancel = (
        clientService: ClientService,
        contract: ServiceContract,
    ) => {
        router.post(
            `/admin/clients/${client.id}/services/${clientService.id}/contracts/${contract.id}/cancel`,
            {},
            { preserveScroll: true },
        );
    };

    const destroy = (
        clientService: ClientService,
        contract: ServiceContract,
    ) => {
        router.delete(
            `/admin/clients/${client.id}/services/${clientService.id}/contracts/${contract.id}`,
            { preserveScroll: true },
        );
    };

    if (services.length === 0) {
        return (
            <div className="p-5">
                <p className="text-muted-foreground">
                    No services availed yet. Add one before issuing a contract.
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="space-y-5 p-5">
                {services.map((clientService) => {
                    const contracts = clientService.contracts ?? [];

                    return (
                        <Card key={clientService.id} className="rounded-[10px]">
                            <CardContent className="p-5">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="font-bold text-primary">
                                            {clientService.service?.name ??
                                                'Service'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {clientService.therapist
                                                ? `${clientService.therapist.first_name} ${clientService.therapist.last_name}`
                                                : 'No therapist assigned'}
                                        </p>
                                    </div>

                                    <Button
                                        variant="outline"
                                        className="rounded-[10px]"
                                        onClick={() =>
                                            setIssuingFor(clientService)
                                        }
                                    >
                                        <Plus /> Issue Contract
                                    </Button>
                                </div>

                                {contracts.length === 0 ? (
                                    <p className="mt-5 text-sm text-muted-foreground">
                                        No contract yet, so this service cannot
                                        be scheduled.
                                    </p>
                                ) : (
                                    <div className="mt-5 space-y-3">
                                        {contracts.map((contract) => (
                                            <div
                                                key={contract.id}
                                                className="rounded-[5px] border p-3"
                                            >
                                                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div className="flex flex-wrap items-center gap-3">
                                                        <span className="text-sm font-bold">
                                                            {
                                                                contract.contract_number
                                                            }
                                                        </span>
                                                        <ServiceContractBadge
                                                            status={
                                                                contract.derived_status
                                                            }
                                                        />
                                                    </div>

                                                    <div className="flex flex-wrap gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            className="rounded-[10px]"
                                                            onClick={() =>
                                                                setEditing({
                                                                    clientService,
                                                                    contract,
                                                                })
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        {contract.derived_status !==
                                                            'cancelled' && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                className="rounded-[10px]"
                                                                onClick={() =>
                                                                    cancel(
                                                                        clientService,
                                                                        contract,
                                                                    )
                                                                }
                                                            >
                                                                <Ban /> Cancel
                                                            </Button>
                                                        )}
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            className="rounded-[10px]"
                                                            onClick={() =>
                                                                destroy(
                                                                    clientService,
                                                                    contract,
                                                                )
                                                            }
                                                        >
                                                            <Trash />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div className="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">
                                                    <div>
                                                        <p className="text-xs text-muted-foreground">
                                                            Authorized
                                                        </p>
                                                        <p>
                                                            {
                                                                contract.allotted_hours
                                                            }{' '}
                                                            hours
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs text-muted-foreground">
                                                            Used
                                                        </p>
                                                        <p>
                                                            {round(
                                                                Number(
                                                                    contract.allotted_hours,
                                                                ) -
                                                                    contract.remaining_hours,
                                                            )}{' '}
                                                            hours
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs text-muted-foreground">
                                                            Remaining
                                                        </p>
                                                        {/*
                                                         * Negative when a
                                                         * session ran past
                                                         * what was booked. The
                                                         * work happened, so it
                                                         * is shown rather than
                                                         * floored at zero.
                                                         */}
                                                        <p
                                                            className={
                                                                contract.remaining_hours <
                                                                0
                                                                    ? 'text-destructive'
                                                                    : ''
                                                            }
                                                        >
                                                            {round(
                                                                contract.remaining_hours,
                                                            )}{' '}
                                                            hours
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs text-muted-foreground">
                                                            Period
                                                        </p>
                                                        <p>
                                                            {formatDate(
                                                                contract.period_start,
                                                            )}{' '}
                                                            to{' '}
                                                            {formatDate(
                                                                contract.period_end,
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>

                                                {contract.notes && (
                                                    <p className="mt-3 text-sm text-muted-foreground">
                                                        {contract.notes}
                                                    </p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {issuingFor && (
                <ServiceContractModal
                    clientId={client.id}
                    clientService={issuingFor}
                    fundingCodes={fundingCodes}
                    isOpen
                    onClose={() => setIssuingFor(null)}
                />
            )}

            {editing && (
                <ServiceContractModal
                    clientId={client.id}
                    clientService={editing.clientService}
                    contract={editing.contract}
                    fundingCodes={fundingCodes}
                    isOpen
                    onClose={() => setEditing(null)}
                />
            )}
        </>
    );
}

function round(hours: number): number {
    return Math.round(hours * 100) / 100;
}
