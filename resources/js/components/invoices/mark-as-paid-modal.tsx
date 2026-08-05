import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Invoice } from '@/types/invoice';

/** Reference: cats-frontend/src/modals/MarkAsPaidModal.tsx */
export default function MarkAsPaidModal({
    invoice,
    isOpen,
    onClose,
}: {
    invoice: Invoice;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        router.post(`/admin/invoices/${invoice.id}/mark-paid`, undefined, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold text-primary">
                        Mark Invoice as Paid
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    This will mark invoice{' '}
                    {invoice.invoice_id ?? `#${invoice.id}`} as paid
                    {invoice.session_id
                        ? ' and complete its linked session'
                        : ''}
                    . This action can&apos;t be reversed.
                </p>

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
                        onClick={confirm}
                        disabled={processing}
                    >
                        Confirm Payment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
