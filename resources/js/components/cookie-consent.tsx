import { Cookie, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';

/**
 * Ported 1:1 from cats-frontend/src/components/CookieConsent.tsx.
 * LocalStorage-only banner, mounted globally in PublicLayout.
 */
export default function CookieConsent() {
    const [showBanner, setShowBanner] = useState(false);

    useEffect(() => {
        const consent = localStorage.getItem('cookieConsent');

        if (!consent) {
            // localStorage only exists client-side, so this can't be computed during render (SSR) —
            // an effect is the correct place to read it.
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setShowBanner(true);
        }
    }, []);

    const handleConsent = (accepted: boolean) => {
        localStorage.setItem(
            'cookieConsent',
            accepted ? 'accepted' : 'declined',
        );

        if (!accepted) {
            localStorage.clear();
            sessionStorage.clear();

            if ('indexedDB' in window) {
                indexedDB.databases().then((dbs) => {
                    dbs.forEach((db) => {
                        if (db.name) {
                            indexedDB.deleteDatabase(db.name);
                        }
                    });
                });
            }

            if ('caches' in window) {
                caches
                    .keys()
                    .then((keys) => keys.forEach((key) => caches.delete(key)));
            }
        }

        setShowBanner(false);
    };

    if (!showBanner) {
        return null;
    }

    return (
        <div className="fixed bottom-10 left-1/2 z-50 flex w-[90%] -translate-x-1/2 transform flex-col gap-4 rounded-lg border border-secondary-orange/50 bg-white p-5 shadow-xl sm:w-[500px]">
            <button
                onClick={() => setShowBanner(false)}
                className="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
                aria-label="Dismiss"
            >
                <X className="h-5 w-5" />
            </button>

            <h1 className="flex items-center gap-2 text-primary-orange">
                <Cookie /> We Use Cookies
            </h1>

            <p className="text-sm text-gray-800 sm:text-base">
                We use cookies to ensure our website works properly and to
                improve your experience. By clicking "Accept", you agree to our
                use of cookies.{' '}
                <a
                    href="/cookiepolicy"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary underline"
                >
                    Learn more
                </a>
            </p>

            <div className="flex flex-wrap justify-end gap-3">
                <Button
                    variant="outline"
                    className="rounded-[5px] border-primary text-primary"
                    onClick={() => handleConsent(false)}
                >
                    Decline
                </Button>
                <Button
                    className="rounded-[5px] bg-primary text-white"
                    onClick={() => handleConsent(true)}
                >
                    Accept
                </Button>
            </div>
        </div>
    );
}
