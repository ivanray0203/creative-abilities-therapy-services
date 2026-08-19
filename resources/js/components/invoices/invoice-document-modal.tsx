import { Download, ExternalLink } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Invoice } from '@/types/invoice';

/**
 * The stored invoice document: the copy the parent signed when there is one,
 * otherwise the PDF filed when the invoice was raised.
 *
 * The controller resolves which to serve and streams it inline, so the
 * browser's own viewer renders it in the frame. It serves a therapist's bill
 * to the clinic the same way, in that document's own format.
 */
export default function InvoiceDocumentModal({
    invoice,
    basePath,
    isOpen,
    onClose,
}: {
    invoice: Invoice;
    basePath: string;
    isOpen: boolean;
    onClose: () => void;
}) {
    const source = `${basePath}/${invoice.id}/pdf`;
    const isSigned = Boolean(invoice.signed_invoice);
    // Only the clinic's invoice to a family is ever signed; a bill to the
    // clinic is not waiting on anyone.
    const isParentFacing = invoice.billed_by === 'admin';

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="flex h-[90vh] w-full max-w-[80%] flex-col p-0">
                <DialogHeader className="border-b p-4">
                    <DialogTitle>
                        Invoice {invoice.invoice_id ?? `#${invoice.id}`}
                    </DialogTitle>
                    <DialogDescription>
                        {!isParentFacing
                            ? 'The invoice as it was filed.'
                            : isSigned
                              ? 'The copy signed and returned by the parent.'
                              : 'Awaiting the parent’s signature.'}
                    </DialogDescription>
                </DialogHeader>

                <iframe
                    id="invoice-document-frame"
                    title={`Invoice ${invoice.invoice_id ?? invoice.id}`}
                    src={source}
                    className="w-full flex-1 border-0"
                />

                <DialogFooter className="flex gap-4 border-t bg-white p-4">
                    <Button
                        variant="outline"
                        className="flex-1 rounded"
                        onClick={onClose}
                    >
                        Close
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="flex-1 rounded"
                    >
                        <a href={source} target="_blank" rel="noreferrer">
                            <ExternalLink /> Open in New Tab
                        </a>
                    </Button>
                    <Button
                        asChild
                        className="flex-1 rounded bg-primary text-white"
                    >
                        <a href={source} download>
                            <Download /> Download PDF
                        </a>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
