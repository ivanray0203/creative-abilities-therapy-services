import { Head } from '@inertiajs/react';

import ClientLayout from '@/layouts/client-layout';

/** Reference: cats-frontend's ReportsPage.tsx always renders its "Coming Soon" branch (isReady is never set true) — this stub matches that behavior. */
export default function ClientReports() {
    return (
        <>
            <Head title="Reports" />

            <div className="flex h-[calc(100vh-4rem)] items-center justify-center">
                <div className="px-6 text-center">
                    <h1 className="mb-6 animate-pulse text-6xl font-extrabold text-primary sm:text-7xl">
                        Coming Soon
                    </h1>
                    <p className="mb-8 text-xl text-primary/90 sm:text-2xl">
                        We&apos;re working hard to bring you something amazing!
                    </p>
                </div>
            </div>
        </>
    );
}

ClientReports.layout = (page: React.ReactNode) => (
    <ClientLayout>{page}</ClientLayout>
);
