import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';

import AdminIntakeForm from '@/components/admin/intake-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

export default function AdminIntakeCreate() {
    return (
        <>
            <Head title="New Intake" />

            <div className="space-y-6 p-6">
                <Button variant="ghost" asChild>
                    <Link href="/admin/intake">
                        <ArrowLeftIcon /> Back to Intakes
                    </Link>
                </Button>

                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        New Intake
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Add an intake application on behalf of a family
                    </p>
                </div>

                <AdminIntakeForm />
            </div>
        </>
    );
}

AdminIntakeCreate.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
