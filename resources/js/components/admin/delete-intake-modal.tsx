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

/** Reference: cats-frontend/src/modals/DeleteIntakeModal.tsx */
export default function DeleteIntakeModal({
    intakeId,
    isOpen,
    onClose,
}: {
    intakeId: number;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    const handleDelete = () => {
        router.delete(`/admin/intake/${intakeId}`, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold text-primary">
                        Delete Intake
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    Are you sure you want to delete intake {intakeId}? This
                    action can&apos;t be reversed.
                </p>

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        variant="destructive"
                        onClick={handleDelete}
                        disabled={processing}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
