import { Head } from '@inertiajs/react';

import ClientLayout from '@/layouts/client-layout';

/**
 * Reference: cats-frontend's client Dashboard.tsx always renders its "Coming
 * Soon" branch. Not the client's post-login landing page — that's the
 * calendar (see EnsureRole::homeFor('client')) — but the route still exists.
 */
export default function ClientDashboard() {
    return (
        <>
            <Head title="Dashboard" />

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

ClientDashboard.layout = (page: React.ReactNode) => (
    <ClientLayout>{page}</ClientLayout>
);
