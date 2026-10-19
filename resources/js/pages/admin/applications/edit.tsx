import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';

import ApplicationForm from '@/components/admin/application-form';
import type { CareerOption } from '@/components/admin/application-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import type { Application } from '@/types/application';

interface ApplicationEditProps {
    application: Application;
    careers: CareerOption[];
}

export default function AdminApplicationEdit({
    application,
    careers,
}: ApplicationEditProps) {
    return (
        <>
            <Head
                title={`Edit Application ${application.reference_number ?? application.id}`}
            />

            <div className="space-y-6 p-6">
                <Button variant="ghost" asChild>
                    <Link href={`/admin/applications/${application.id}`}>
                        <ArrowLeftIcon /> Back to Application
                    </Link>
                </Button>

                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Edit Application — {application.first_name}{' '}
                        {application.last_name}
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Reference{' '}
                        {application.reference_number ??
                            `APP - ${application.id}`}
                    </p>
                </div>

                <ApplicationForm application={application} careers={careers} />
            </div>
        </>
    );
}

AdminApplicationEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
