import { usePage } from '@inertiajs/react';
import { CloudUpload } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { connect } from '@/routes/admin/google-drive';

/**
 * "Later" hides the prompt until the admin next signs in: the key is cleared
 * on logout (see clearGoogleDrivePromptDismissal).
 */
const DISMISS_KEY = 'cats.google-drive-prompt-dismissed';

function isDismissed(): boolean {
    try {
        return window.sessionStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        return false;
    }
}

export function clearGoogleDrivePromptDismissal(): void {
    try {
        window.sessionStorage.removeItem(DISMISS_KEY);
    } catch {
        // Storage unavailable: the prompt simply shows again.
    }
}

/**
 * Asks admins to connect Google Drive until someone has. Until then,
 * uploads are held on the server and only move to Drive once it is
 * connected (FallbackDriveStorage / drive:sync-pending).
 */
export default function ConnectGoogleDriveDialog() {
    const { googleDrive } = usePage().props;
    const [open, setOpen] = useState(() => !isDismissed());

    if (!googleDrive?.configured || googleDrive.connected) {
        return null;
    }

    const dismiss = () => {
        try {
            window.sessionStorage.setItem(DISMISS_KEY, '1');
        } catch {
            // Storage unavailable: hidden for this page only.
        }

        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && dismiss()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <CloudUpload className="h-6 w-6 text-primary" />
                    </div>
                    <DialogTitle>Connect Google Drive</DialogTitle>
                    <DialogDescription>
                        Google Drive isn&apos;t connected yet. Uploaded
                        documents are being kept on the server until it is, then
                        they move to Drive automatically.
                    </DialogDescription>
                </DialogHeader>

                {googleDrive.pending_uploads > 0 && (
                    <p className="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        {googleDrive.pending_uploads}{' '}
                        {googleDrive.pending_uploads === 1
                            ? 'file is'
                            : 'files are'}{' '}
                        waiting to be moved to Google Drive.
                    </p>
                )}

                <DialogFooter className="gap-2 sm:gap-0">
                    <Button variant="outline" onClick={dismiss}>
                        Later
                    </Button>
                    {/* A full page visit: the connect route redirects to Google. */}
                    <Button asChild>
                        <a href={connect.url()}>Connect Google Drive</a>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
