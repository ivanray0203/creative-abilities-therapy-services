import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';

import AdminIntakeForm from '@/components/admin/intake-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import type { Intake } from '@/types/intake';

export default function AdminIntakeEdit({ intake }: { intake: Intake }) {
    return (
        <>
            <Head
                title={`Edit Intake ${intake.reference_number ?? intake.id}`}
            />

            <div className="space-y-6 p-6">
                <Button variant="ghost" asChild>
                    <Link href={`/admin/intake/${intake.id}`}>
                        <ArrowLeftIcon /> Back to Intake
                    </Link>
                </Button>

                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                        Edit Intake — {intake.child_first_name}{' '}
                        {intake.child_last_name}
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        Reference{' '}
                        {intake.reference_number ?? `INT - ${intake.id}`}
                    </p>
                </div>

                <AdminIntakeForm intake={intake} />
            </div>
        </>
    );
}

AdminIntakeEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
