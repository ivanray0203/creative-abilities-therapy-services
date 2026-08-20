import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Toaster, toast } from 'sonner';

/**
 * Fires a toast for the `flash.success`/`flash.error` session messages
 * `back()->with('success', ...)` controllers set — Inertia shares them
 * (see HandleInertiaRequests::share) but nothing rendered them before, so
 * actions like "Intake approved and client created." completed silently.
 */

/**
 * Only strings are worth showing. The intake controllers flash
 * `success => true` as a plain signal rather than a message, and toasting a
 * boolean renders an empty popup with no text in it.
 */
function toastableMessage(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value : null;
}

export default function FlashToaster() {
    const { flash } = usePage().props;

    useEffect(() => {
        const message = toastableMessage(flash.success);

        if (message) {
            toast.success(message);
        }
    }, [flash.success]);

    useEffect(() => {
        const message = toastableMessage(flash.error);

        if (message) {
            toast.error(message);
        }
    }, [flash.error]);

    return <Toaster position="top-right" richColors closeButton />;
}
