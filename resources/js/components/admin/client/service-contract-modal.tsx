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
import type { ClientService, ServiceContract } from '@/types/client';

interface ServiceContractForm {
    allotted_hours: string;
    funding_code: string;
    period_start: string;
    period_end: string;
    notes: string;
}

/**
 * The blank the sheet leaves when the funding is not settled yet. A word
 * rather than an empty string, which Radix will not take as a value.
 *
 * The codes themselves arrive as a prop from `ServiceContract::FUNDING_CODES`.
 * A copy kept here would eventually fall behind the validator, and an admin
 * opening this form on a code missing from the list would blank it on save.
 */
const NO_FUNDING_CODE = 'none';

/** First of the month to last, the shape most contracts take. */
function currentMonth(): { start: string; end: string } {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    return {
        start: toDateInput(start),
        end: toDateInput(end),
    };
}

function toDateInput(date: Date): string {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

/**
 * An edit opens on what the contract holds; a new contract opens on the funder
 * already recorded for the service or the child, which
 * `Admin\ClientController` sends down as `default_funding_code`.
 *
 * A suggestion, not a default: it is pre-selected where the admin can see it,
 * so clearing it back to "Not set" stays a choice they can make.
 */
function initialValues(
    clientService: ClientService,
    contract?: ServiceContract | null,
): ServiceContractForm {
    if (contract) {
        return {
            allotted_hours: contract.allotted_hours,
            funding_code: contract.funding_code ?? '',
            period_start: contract.period_start?.slice(0, 10) ?? '',
            period_end: contract.period_end?.slice(0, 10) ?? '',
            notes: contract.notes ?? '',
        };
    }

    const month = currentMonth();

    return {
        allotted_hours: '40',
        funding_code: clientService.default_funding_code ?? '',
        period_start: month.start,
        period_end: month.end,
        notes: '',
    };
}

/**
 * Phase 20 — issue or amend the hours a therapist is authorized to deliver
 * against one availed service.
 *
 * A contract is what makes a service schedulable at all, so the two numbers
 * that matter are the pool and the window. Everything else about the service
 * is already set on the service itself.
 */
export default function ServiceContractModal({
    clientId,
    clientService,
    contract,
    fundingCodes,
    isOpen,
    onClose,
}: {
    clientId: number;
    clientService: ClientService;
    contract?: ServiceContract | null;
    fundingCodes: string[];
    isOpen: boolean;
    onClose: () => void;
}) {
    const isEdit = contract != null;

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm<ServiceContractForm>(initialValues(clientService, contract));

    const base = `/admin/clients/${clientId}/services/${clientService.id}/contracts`;

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        const options = { preserveScroll: true, onSuccess: closeAndReset };

        if (isEdit && contract) {
            put(`${base}/${contract.id}`, options);

            return;
        }

        post(base, options);
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="max-h-[90vh] w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        {isEdit ? 'Edit Contract' : 'Issue Contract'}
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    {clientService.service?.name ?? 'Service'}
                    {clientService.therapist
                        ? ` — ${clientService.therapist.first_name} ${clientService.therapist.last_name}`
                        : ''}
                </p>

                <div className="mt-3 space-y-3">
                    <div>
                        <Label htmlFor="contract-hours">
                            Authorized Hours *
                        </Label>
                        <Input
                            id="contract-hours"
                            type="number"
                            min={0.25}
                            step="0.25"
                            value={data.allotted_hours}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('allotted_hours', event.target.value)
                            }
                        />
                        {errors.allotted_hours && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.allotted_hours}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="contract-funding">Funding</Label>
                        <Select
                            value={data.funding_code || NO_FUNDING_CODE}
                            onValueChange={(value) =>
                                setData(
                                    'funding_code',
                                    value === NO_FUNDING_CODE ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger
                                id="contract-funding"
                                className="mt-2 w-full rounded-[10px]"
                            >
                                <SelectValue placeholder="Not set" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NO_FUNDING_CODE}>
                                    Not set
                                </SelectItem>
                                {fundingCodes.map((code) => (
                                    <SelectItem key={code} value={code}>
                                        {code}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.funding_code && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.funding_code}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="contract-start">
                                Period Start *
                            </Label>
                            <Input
                                id="contract-start"
                                type="date"
                                value={data.period_start}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('period_start', event.target.value)
                                }
                            />
                            {errors.period_start && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.period_start}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="contract-end">Period End *</Label>
                            <Input
                                id="contract-end"
                                type="date"
                                value={data.period_end}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('period_end', event.target.value)
                                }
                            />
                            {errors.period_end && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.period_end}
                                </p>
                            )}
                        </div>
                    </div>

                    <p className="text-xs text-muted-foreground">
                        Unused hours do not carry over. Issue a new contract for
                        the next period.
                    </p>

                    <div>
                        <Label htmlFor="contract-notes">Notes</Label>
                        <Textarea
                            id="contract-notes"
                            value={data.notes}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('notes', event.target.value)
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
                        disabled={processing || data.allotted_hours === ''}
                    >
                        {isEdit ? 'Save Changes' : 'Issue Contract'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
