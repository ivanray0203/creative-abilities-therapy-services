import { Head } from '@inertiajs/react';
import { Lock, Pen } from 'lucide-react';

import IntakeApplicationForm from '@/components/forms/intake-application-form';
import PublicLayout from '@/layouts/public-layout';
import type { ConsentDocument } from '@/types/consent';

interface IntakeApplicationPageProps {
    requiredConsents: ConsentDocument[];
}

/**
 * Thin page wrapper, ported from cats-frontend/src/pages/IntakeApplication.tsx.
 * The header/hero markup matches the reference; the form logic lives in
 * IntakeApplicationForm. `requiredConsents` replaces the reference's
 * react-query fetch of required intake consent documents.
 */
export default function IntakeApplication({
    requiredConsents,
}: IntakeApplicationPageProps) {
    return (
        <>
            <Head title="Intake Form" />

            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center px-4 py-16 sm:px-6 sm:py-24 md:px-8">
                    <div className="rounded-sm bg-secondary-orange/5 p-2 sm:p-3">
                        <p className="flex flex-row items-center gap-2 text-sm font-medium text-primary sm:gap-3 sm:text-base">
                            <Pen className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                            <span>Getting Started</span>
                        </p>
                    </div>

                    <p className="mt-5 text-center text-lg font-semibold text-primary sm:text-xl">
                        Intake Form
                    </p>

                    <p className="mt-3 max-w-3xl px-4 text-center text-base leading-relaxed text-foreground sm:mt-5 sm:px-20 sm:text-lg">
                        Please complete this form to help us better understand
                        your child's needs and match you with the therapist and
                        services.
                    </p>

                    <div className="mt-5 flex items-center gap-2 rounded-sm bg-white p-2 shadow-md sm:gap-3 sm:p-3 sm:shadow-xl">
                        <Lock className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                        <span className="text-xs text-muted-foreground sm:text-sm">
                            All information is kept strictly confidential
                        </span>
                    </div>
                </div>
            </section>

            <section id="application" className="mx-3 mb-20 lg:mx-[10%]">
                <IntakeApplicationForm requiredConsents={requiredConsents} />
            </section>
        </>
    );
}

IntakeApplication.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: false,
            title: 'Ready to Access FSCD Services?',
            desc: 'If your family is eligible for FSCD support and you are seeking specialized services for your child, contact Creative Abilities Therapy Services today. Our team will help you navigate the FSCD process, from application to service delivery, ensuring your child receives the best possible care.',
        }}
    >
        {page}
    </PublicLayout>
);
