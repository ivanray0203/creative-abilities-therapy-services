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

/**
 * The server refuses to delete a program families have registered for and
 * unpublishes it instead, so the wording here has to match what will
 * actually happen rather than promising a deletion.
 */
export default function DeleteProgramModal({
    slug,
    name,
    registrationCount,
    isOpen,
    onClose,
}: {
    slug: string;
    name: string;
    registrationCount: number;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);
    const hasRegistrations = registrationCount > 0;

    const handleDelete = () => {
        router.delete(`/admin/programs/${slug}`, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold text-primary">
                        {hasRegistrations
                            ? 'Unpublish Program'
                            : 'Delete Program'}
                    </DialogTitle>
                </DialogHeader>

                {hasRegistrations ? (
                    <p className="text-sm text-muted-foreground">
                        <strong>{name}</strong> has {registrationCount}{' '}
                        registration
                        {registrationCount === 1 ? '' : 's'}, so it will be
                        removed from the public site rather than deleted. The
                        registrations are kept.
                    </p>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Are you sure you want to delete <strong>{name}</strong>?
                        This action can&apos;t be reversed.
                    </p>
                )}

                <DialogFooter className="mt-6">
                    <Button
                        id="confirm-delete-program"
                        className="w-full rounded-[10px]"
                        variant="destructive"
                        onClick={handleDelete}
                        disabled={processing}
                    >
                        {hasRegistrations ? 'Unpublish' : 'Delete'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
