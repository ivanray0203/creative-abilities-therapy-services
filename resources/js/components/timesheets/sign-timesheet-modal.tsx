import { router } from '@inertiajs/react';
import { useState } from 'react';

import SignaturePad from '@/components/signature-pad';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * The parent confirming the hours their aide logged.
 *
 * The pad itself lives in components/signature-pad.tsx, shared with the
 * invoice a parent signs and the offer letter a candidate signs before they
 * are hired.
 */
export default function SignTimesheetModal({
    timesheetId,
    parentName,
    isOpen,
    onClose,
}: {
    timesheetId: number;
    parentName: string;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [signature, setSignature] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        if (!signature) {
            return;
        }

        router.post(
            `/client/timesheets/${timesheetId}/sign`,
            { signature },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => onClose(),
            },
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-2xl rounded-xl">
                <DialogHeader>
                    <DialogTitle>Sign this timesheet</DialogTitle>
                    <DialogDescription>
                        Draw your signature below. It is added to the
                        Parent&apos;s Signature box on the time sheet and sent
                        back to the clinic.
                    </DialogDescription>
                </DialogHeader>

                <SignaturePad
                    caption={parentName}
                    onSignatureChange={setSignature}
                />

                <DialogFooter>
                    <Button
                        id="submit-timesheet-signature"
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={!signature || processing}
                    >
                        {processing ? 'Sending...' : 'Sign and send back'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
