import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

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
import type { InvoiceService } from '@/types/invoice';

interface RatesForm {
    rate_fscd: string;
    rate_private: string;
}

/**
 * Edits one rate line's published rates.
 *
 * A blank field is not zero — it means the line is not billable under that
 * funding stream, which is how the rate sheet leaves travel and mileage blank
 * under private funding.
 */
export default function InvoiceServiceRatesModal({
    service,
    isOpen,
    onClose,
}: {
    service: InvoiceService | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    const { data, setData, patch, processing, errors, reset, clearErrors } =
        useForm<RatesForm>({ rate_fscd: '', rate_private: '' });

    useEffect(() => {
        if (service) {
            clearErrors();
            setData({
                rate_fscd: service.rate_fscd ?? '',
                rate_private: service.rate_private ?? '',
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [service?.id]);

    const submit = () => {
        if (!service) {
            return;
        }

        patch(`/admin/services/${service.id}/rates`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Edit Rates
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-5">
                    <div>
                        <p className="font-medium">{service?.name}</p>
                        <p className="text-sm text-muted-foreground">
                            Code: {service?.code}
                        </p>
                    </div>

                    <div>
                        <Label htmlFor="rate-fscd">FSCD Rate</Label>
                        <Input
                            id="rate-fscd"
                            type="number"
                            step="0.01"
                            min="0"
                            inputMode="decimal"
                            value={data.rate_fscd}
                            onChange={(event) =>
                                setData('rate_fscd', event.target.value)
                            }
                            className="mt-2 rounded-[10px]"
                        />
                        {errors.rate_fscd && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.rate_fscd}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="rate-private">
                            Private/Insurance Rate
                        </Label>
                        <Input
                            id="rate-private"
                            type="number"
                            step="0.01"
                            min="0"
                            inputMode="decimal"
                            value={data.rate_private}
                            onChange={(event) =>
                                setData('rate_private', event.target.value)
                            }
                            className="mt-2 rounded-[10px]"
                        />
                        {errors.rate_private && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.rate_private}
                            </p>
                        )}
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Leave a field blank if the line is not billable under
                        that funding stream.
                    </p>
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={onClose}
                    >
                        Cancel
                    </Button>
                    <Button
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={processing}
                    >
                        Save Rates
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
