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
import { Textarea } from '@/components/ui/textarea';
import type { Client, ClientService, ServiceOffering } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

interface ClientServiceForm {
    service_id: string;
    therapist_id: string;
    frequency: string;
    duration: string;
    start_date: string;
    funding_source: string;
    no_sessions: string;
    goals: string;
}

function initialValues(
    clientService?: ClientService | null,
): ClientServiceForm {
    return {
        service_id: clientService?.service_id
            ? String(clientService.service_id)
            : '',
        therapist_id: clientService?.therapist_id
            ? String(clientService.therapist_id)
            : '',
        frequency: clientService?.frequency ?? '',
        duration: clientService?.duration ?? '',
        start_date: clientService?.start_date ?? '',
        funding_source: clientService?.funding_source ?? '',
        no_sessions: clientService?.no_sessions
            ? String(clientService.no_sessions)
            : '',
        goals: clientService?.goals ?? '',
    };
}

/** Reference: cats-frontend/src/modals/AddClientService.tsx */
export default function AddClientServiceModal({
    client,
    services,
    therapists,
    clientService,
    isOpen,
    onClose,
}: {
    client: Client;
    services: ServiceOffering[];
    therapists: TherapistOption[];
    clientService?: ClientService | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    const isEdit = clientService != null;

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm<ClientServiceForm>(initialValues(clientService));

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        const onSuccess = () => closeAndReset();

        if (isEdit && clientService) {
            put(`/admin/clients/${client.id}/services/${clientService.id}`, {
                preserveScroll: true,
                onSuccess,
            });

            return;
        }

        post(`/admin/clients/${client.id}/services`, {
            preserveScroll: true,
            onSuccess,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="max-h-[90vh] w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        {isEdit ? 'Edit Service' : 'Add Service'}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-3">
                    <div>
                        <Label htmlFor="service-id">Service *</Label>
                        <Select
                            value={data.service_id}
                            onValueChange={(value) =>
                                setData('service_id', value)
                            }
                        >
                            <SelectTrigger
                                id="service-id"
                                className="rounded-[10px]"
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
                        {errors.service_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.service_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="service-therapist">Therapist</Label>
                        <Select
                            value={data.therapist_id}
                            onValueChange={(value) =>
                                setData('therapist_id', value)
                            }
                        >
                            <SelectTrigger
                                id="service-therapist"
                                className="rounded-[10px]"
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
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="service-frequency">Frequency</Label>
                            <Input
                                id="service-frequency"
                                value={data.frequency}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('frequency', event.target.value)
                                }
                            />
                        </div>
                        <div>
                            <Label htmlFor="service-duration">Duration</Label>
                            <Input
                                id="service-duration"
                                value={data.duration}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('duration', event.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="service-start-date">
                                Start Date
                            </Label>
                            <Input
                                id="service-start-date"
                                type="date"
                                value={data.start_date}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('start_date', event.target.value)
                                }
                            />
                        </div>
                        <div>
                            <Label htmlFor="service-no-sessions">
                                No. of Sessions
                            </Label>
                            <Input
                                id="service-no-sessions"
                                type="number"
                                min={0}
                                value={data.no_sessions}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('no_sessions', event.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="service-funding-source">
                            Funding Source
                        </Label>
                        <Input
                            id="service-funding-source"
                            value={data.funding_source}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('funding_source', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="service-goals">Goals</Label>
                        <Textarea
                            id="service-goals"
                            value={data.goals}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('goals', event.target.value)
                            }
                        />
                    </div>
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
                        disabled={processing || data.service_id === ''}
                    >
                        {isEdit ? 'Save Changes' : 'Add Service'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
