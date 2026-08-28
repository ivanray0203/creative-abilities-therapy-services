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
import type { Timesheet } from '@/types/timesheet';

/**
 * The stored time sheet document: the copy the parent signed when there is
 * one, otherwise the PDF filed when the aide generated it.
 *
 * The controller resolves which to serve and streams it inline, so the
 * browser's own viewer renders it in the frame.
 */
export default function TimesheetDocumentModal({
    timesheet,
    basePath,
    isOpen,
    onClose,
}: {
    timesheet: Timesheet;
    basePath: string;
    isOpen: boolean;
    onClose: () => void;
}) {
    const source = `${basePath}/${timesheet.id}/pdf`;
    const isSigned = Boolean(timesheet.parent_signature);

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="flex h-[90vh] w-full max-w-[80%] flex-col p-0">
                <DialogHeader className="border-b p-4">
                    <DialogTitle>
                        Timesheet {timesheet.timesheet_number}
                    </DialogTitle>
                    <DialogDescription>
                        {isSigned
                            ? 'The copy signed and returned by the parent.'
                            : 'Awaiting the parent’s signature.'}
                    </DialogDescription>
                </DialogHeader>

                <iframe
                    id="timesheet-document-frame"
                    title={`Timesheet ${timesheet.timesheet_number}`}
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
