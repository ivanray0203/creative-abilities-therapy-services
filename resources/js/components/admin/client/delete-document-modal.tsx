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
import type { ClientDocument } from '@/types/client';

/** Reference: cats-frontend/src/modals/DeleteDocumentModal.tsx */
export default function DeleteDocumentModal({
    document,
    isOpen,
    onClose,
}: {
    document: ClientDocument | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!document) {
            return;
        }

        router.delete(`/admin/clients/documents/${document.id}`, {
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
                    <DialogTitle className="text-lg font-semibold">
                        Delete Document
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    Are you sure you want to delete &quot;{document?.title}
                    &quot;? This action cannot be undone.
                </p>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded"
                        onClick={onClose}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        className="rounded"
                        onClick={confirmDelete}
                        disabled={processing}
                    >
                        {processing ? 'Deleting' : 'Delete'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
