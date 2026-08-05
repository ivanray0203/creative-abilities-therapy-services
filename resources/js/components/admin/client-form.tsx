import { useForm } from '@inertiajs/react';
import { Printer, Save, Trash } from 'lucide-react';
import { useRef, useState } from 'react';
import { useReactToPrint } from 'react-to-print';

import DeleteClientModal from '@/components/admin/delete-client-modal';
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
import type { Client, ClientStatus } from '@/types/client';

const STATUSES: ClientStatus[] = [
    'active',
    'paused',
    'completed',
    'inactive',
    'archive',
];

interface ClientFormData {
    status: ClientStatus;
    contract_start_date: string;
    contract_end_date: string;
    signed_date: string;
    allergies: string;
}

function initialValues(client: Client): ClientFormData {
    return {
        status: client.status,
        contract_start_date: client.contract_start_date ?? '',
        contract_end_date: client.contract_end_date ?? '',
        signed_date: client.signed_date ?? '',
        allergies: (client.allergies ?? []).join(', '),
    };
}

/**
 * Admin "Edit Client" form, reference: cats-frontend/src/components/ClientForm.tsx.
 *
 * Only client-specific fields are editable here — child/parent/funding info
 * is read straight from `original_intake` and shown read-only below.
 */
export default function ClientForm({ client }: { client: Client }) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const printRef = useRef<HTMLDivElement>(null);
    const intake = client.original_intake;

    const { data, setData, put, processing, errors, transform } =
        useForm<ClientFormData>(initialValues(client));

    const handlePrint = useReactToPrint({
        contentRef: printRef,
        documentTitle: `Client_${intake?.child_first_name ?? client.id}_${intake?.child_last_name ?? ''}`,
    });

    const submit = () => {
        transform((form) => ({
            ...form,
            allergies: form.allergies
                .split(',')
                .map((entry) => entry.trim())
                .filter(Boolean),
        }));

        put(`/admin/clients/${client.id}`);
    };

    return (
        <div ref={printRef} className="grid grid-cols-1 gap-5 p-6">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">
                        Child & Family Information
                    </p>
                    <div className="grid grid-cols-2 gap-5 text-sm">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Child Name
                            </p>
                            <p>
                                {intake?.child_first_name}{' '}
                                {intake?.child_last_name}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Primary Parent
                            </p>
                            <p>{intake?.primary_parent_name}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Funding Source
                            </p>
                            <p>{intake?.funding_source}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Reference Number
                            </p>
                            <p>{intake?.reference_number}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">
                        Client Details
                    </p>

                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <Label htmlFor="client-status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(value) =>
                                    setData('status', value as ClientStatus)
                                }
                            >
                                <SelectTrigger
                                    id="client-status"
                                    className="mt-2 rounded-[10px]"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {STATUSES.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {status}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.status && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.status}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="client-allergies">
                                Allergies (comma-separated)
                            </Label>
                            <Input
                                id="client-allergies"
                                value={data.allergies}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('allergies', event.target.value)
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="client-contract-start">
                                Contract Start Date
                            </Label>
                            <Input
                                id="client-contract-start"
                                type="date"
                                value={data.contract_start_date}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData(
                                        'contract_start_date',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="client-contract-end">
                                Contract End Date
                            </Label>
                            <Input
                                id="client-contract-end"
                                type="date"
                                value={data.contract_end_date}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData(
                                        'contract_end_date',
                                        event.target.value,
                                    )
                                }
                            />
                            {errors.contract_end_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.contract_end_date}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="client-signed-date">
                                Signed Date
                            </Label>
                            <Input
                                id="client-signed-date"
                                type="date"
                                value={data.signed_date}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('signed_date', event.target.value)
                                }
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-between print:hidden">
                <Button
                    variant="destructive"
                    className="rounded-[10px]"
                    onClick={() => setDeleteOpen(true)}
                >
                    <Trash /> Delete Client
                </Button>

                <div className="flex gap-3">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={() => handlePrint()}
                    >
                        <Printer /> Print
                    </Button>
                    <Button
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={processing}
                    >
                        <Save /> Save Changes
                    </Button>
                </div>
            </div>

            <DeleteClientModal
                clientId={client.id}
                isOpen={deleteOpen}
                onClose={() => setDeleteOpen(false)}
            />
        </div>
    );
}
