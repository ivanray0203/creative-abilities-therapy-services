import { Head } from '@inertiajs/react';
import { Lock } from 'lucide-react';

import CareerApplicationForm from '@/components/forms/career-application-form';
import PublicLayout from '@/layouts/public-layout';
import type { Career } from '@/types/career';

interface CareerApplicationPageProps {
    careers: Career[];
    preselectedCareer: Career | null;
}

/**
 * Thin page wrapper, ported from cats-frontend/src/pages/CareerApplication.tsx.
 * The header/hero markup matches the reference; the form logic lives in
 * CareerApplicationForm.
 */
export default function CareerApplication({
    careers,
    preselectedCareer,
}: CareerApplicationPageProps) {
    return (
        <>
            <Head title="Career Application" />

            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center px-4 py-10 sm:py-16">
                    <div className="rounded-sm bg-secondary-orange/5 p-3">
                        <p className="flex flex-row items-center gap-2 text-sm sm:gap-3 sm:text-base">
                            <span className="font-medium text-primary">
                                Apply Now
                            </span>
                        </p>
                    </div>

                    <p className="mt-4 text-lg font-semibold text-primary sm:mt-5 sm:text-xl">
                        Application Form
                    </p>

                    <p className="mt-4 max-w-3xl px-4 text-center text-base leading-relaxed sm:px-20 sm:text-lg">
                        To get started, please complete the application form
                        provided. All information you share will remain
                        confidential and will be used exclusively to assess your
                        fit for the role and our team. Thank you for your
                        interest in joining us!
                    </p>

                    <div className="mt-5 flex items-center gap-2 rounded-sm bg-white p-3 shadow-xl sm:gap-3">
                        <Lock className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                        <span className="text-sm text-muted-foreground sm:text-base">
                            All information is kept strictly confidential
                        </span>
                    </div>
                </div>
            </section>

            <section id="application" className="mx-3 mb-20 lg:mx-[10%]">
                <CareerApplicationForm
                    careers={careers}
                    preselectedCareer={preselectedCareer}
                />
            </section>
        </>
    );
}

CareerApplication.layout = (page: React.ReactNode) => (
    <PublicLayout>{page}</PublicLayout>
);
