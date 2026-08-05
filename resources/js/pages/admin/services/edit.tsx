import { Head } from '@inertiajs/react';

import ServiceOfferingForm from '@/components/admin/service-offering-form';
import AdminLayout from '@/layouts/admin-layout';
import type { ServiceOffering } from '@/types/client';

interface AdminServiceEditProps {
    service: ServiceOffering;
}

/** Admin "Edit Service" page, wraps the shared ServiceOfferingForm. */
export default function AdminServiceEdit({ service }: AdminServiceEditProps) {
    return (
        <>
            <Head title="Edit Service" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Edit Service
                    </h1>
                    <p className="text-muted-foreground">{service.name}</p>
                </div>
                <ServiceOfferingForm service={service} />
            </div>
        </>
    );
}

AdminServiceEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
