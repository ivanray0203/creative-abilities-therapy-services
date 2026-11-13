import { Download } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export interface ViewableDocument {
    title: string | null;
    /** An app route that streams the file (see DocumentFileController). */
    url: string;
}

/**
 * Shows an uploaded file inside the app. The file is streamed from our own
 * origin, so the viewer never leaves for Google Drive and the storage path
 * is never shown. PDFs and images render in the frame; anything the browser
 * cannot show is offered as a download instead.
 */
export default function DocumentViewerModal({
    document,
    onClose,
}: {
    document: ViewableDocument | null;
    onClose: () => void;
}) {
    return (
        <Dialog
            open={document !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="flex h-[90vh] w-full max-w-[95%] flex-col p-0 md:max-w-[80%]">
                <DialogHeader className="border-b p-4">
                    <DialogTitle>{document?.title ?? 'Document'}</DialogTitle>
                    <DialogDescription>
                        If the file does not preview here, use Download.
                    </DialogDescription>
                </DialogHeader>

                {document && (
                    <iframe
                        title={document.title ?? 'Document'}
                        src={document.url}
                        className="w-full flex-1 border-0"
                    />
                )}

                <DialogFooter className="flex gap-4 border-t bg-white p-4">
                    <Button
                        variant="outline"
                        className="flex-1 rounded"
                        onClick={onClose}
                    >
                        Close
                    </Button>
                    {document && (
                        <Button
                            asChild
                            className="flex-1 rounded bg-primary text-white"
                        >
                            <a href={`${document.url}?download=1`} download>
                                <Download /> Download
                            </a>
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
