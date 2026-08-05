import { Head } from '@inertiajs/react';

import ServiceOfferingForm from '@/components/admin/service-offering-form';
import AdminLayout from '@/layouts/admin-layout';

/** Admin "Add Service" page, wraps the shared ServiceOfferingForm. */
export default function AdminServiceCreate() {
    return (
        <>
            <Head title="Add Service" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Add Service
                    </h1>
                    <p className="text-muted-foreground">
                        Create a new service offering
                    </p>
                </div>
                <ServiceOfferingForm />
            </div>
        </>
    );
}

AdminServiceCreate.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
