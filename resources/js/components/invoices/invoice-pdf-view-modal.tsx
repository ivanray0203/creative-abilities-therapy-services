import { useRef } from 'react';
import { useReactToPrint } from 'react-to-print';

import InvoicePrintable from '@/components/invoices/invoice-printable';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Invoice } from '@/types/invoice';

/**
 * Browser-print PDF export, mirrors `intake-pdf-view-modal.tsx`'s
 * `useReactToPrint` pattern.
 */
export default function InvoicePdfViewModal({
    invoice,
    isOpen,
    onClose,
}: {
    invoice: Invoice;
    isOpen: boolean;
    onClose: () => void;
}) {
    const printRef = useRef<HTMLDivElement>(null);

    const handlePrint = useReactToPrint({
        contentRef: printRef,
        documentTitle: `Invoice_${invoice.invoice_id ?? invoice.id}`,
    });

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="flex h-[90vh] w-full max-w-[80%] flex-col p-0">
                <div className="flex-1 overflow-y-auto p-6">
                    <div ref={printRef} className="m-3">
                        <DialogHeader className="mb-4">
                            <DialogTitle>
                                Invoice {invoice.invoice_id ?? `#${invoice.id}`}
                            </DialogTitle>
                        </DialogHeader>

                        <InvoicePrintable invoice={invoice} />
                    </div>
                </div>

                <DialogFooter className="flex gap-4 border-t bg-white p-4">
                    <Button
                        variant="outline"
                        className="flex-1 rounded"
                        onClick={onClose}
                    >
                        Close
                    </Button>
                    <Button
                        className="flex-1 rounded bg-primary text-white"
                        onClick={() => handlePrint()}
                    >
                        Export / Print PDF
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
