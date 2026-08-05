import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Toaster, toast } from 'sonner';

/**
 * Fires a toast for the `flash.success`/`flash.error` session messages
 * `back()->with('success', ...)` controllers set — Inertia shares them
 * (see HandleInertiaRequests::share) but nothing rendered them before, so
 * actions like "Intake approved and client created." completed silently.
 */
export default function FlashToaster() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
    }, [flash.success]);

    useEffect(() => {
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash.error]);

    return <Toaster position="top-right" richColors closeButton />;
}
