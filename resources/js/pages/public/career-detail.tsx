import { Head, Link } from '@inertiajs/react';
import { ArrowBigLeft, Book, CheckCircle, Cookie } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import { Card, CardContent, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import type { Career } from '@/types/career';

/**
 * Ported 1:1 from cats-frontend/src/pages/CareerDetail.tsx. The reference
 * looked up the career from a static src/json/careers array by numeric id;
 * this page receives the resolved `career` directly via the `public.career-detail`
 * Inertia prop (CareerController::show, route-model-bound — 404s automatically
 * if not found).
 */
export default function CareerDetail({ career }: { career: Career }) {
    return (
        <>
            <Head title={career.position} />

            {/* Header Section */}
            <section
                id="header"
                className="bg-gradient-to-br from-primary-orange/10 to-transparent py-16 sm:py-20"
            >
                <div className="container mx-auto pb-16 sm:pb-20">
                    {/* Back Button */}
                    <Link
                        href="/careers"
                        className="mt-5 mb-5 flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-normal text-primary shadow-lg sm:mt-10"
                    >
                        <ArrowBigLeft className="h-4 w-4 sm:h-5 sm:w-5" /> Back
                        to Careers
                    </Link>

                    {/* Main Grid */}
                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-2 lg:gap-12">
                        {/* Left: Career Details */}
                        <div className="flex flex-col space-y-6">
                            <p className="text-xl font-semibold text-primary sm:text-2xl">
                                {career.position}
                            </p>

                            <div className="flex flex-col gap-4 sm:gap-5">
                                <p className="flex items-center gap-2 text-base sm:text-lg">
                                    <LucideIcons.MapPin className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                                    {career.location}
                                </p>
                                <p className="flex items-center gap-2 text-base sm:text-lg">
                                    <LucideIcons.DollarSign className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                                    {career.rate}
                                </p>
                                <p className="flex items-center gap-2 text-base sm:text-lg">
                                    <LucideIcons.Calendar className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                                    {career.hours}
                                </p>
                            </div>

                            {/* Apply Button */}
                            <div>
                                <Link
                                    href={`/careers/apply/${career.id}?position=${career.position}`}
                                    className="inline-block rounded-md bg-primary px-4 py-2 font-medium text-white sm:px-6 sm:py-3"
                                >
                                    Apply for This Position
                                </Link>
                            </div>
                        </div>

                        {/* Right: Image */}
                        <div className="relative w-full">
                            <div className="aspect-[4/3] w-full overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src="/images/800x600/photo-1709880754441-f254e7a7035c_1_cropped.jpg"
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="bg-white py-20">
                <div className="px-10 lg:px-60">
                    {/* Overview */}
                    <div className="rounded-sm shadow-lg">
                        {/* Header */}
                        <div className="sticky top-0 z-10 flex items-center gap-2 border-b border-primary/20 bg-white p-4 sm:gap-3 sm:p-5">
                            <Book className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Position Overview
                            </p>
                        </div>

                        {/* Content */}
                        <div className="mt-4 rounded-b-sm bg-primary/5 p-4 text-sm leading-relaxed sm:mt-5 sm:p-8 sm:text-lg">
                            <p>{career.about_description}</p>
                        </div>
                    </div>

                    <Card className="mt-10 flex-1 shadow-lg">
                        <CardTitle className="sticky top-0 z-10 flex flex-row gap-2 rounded-t-sm bg-gradient-to-br from-secondary-orange/5 to-transparent p-4 font-light text-primary sm:gap-3 sm:p-6">
                            <Cookie className="h-4 w-4 sm:h-5 sm:w-5" />
                            Responsibilities
                        </CardTitle>

                        <CardContent className="m-0 max-h-80 overflow-y-auto p-4 sm:max-h-[400px] sm:p-6">
                            <div className="mt-4 space-y-2 sm:mt-6 sm:space-y-3">
                                {career.responsibilities.map((ap) => (
                                    <div
                                        key={ap}
                                        className="flex items-center gap-2 py-1 sm:gap-3 sm:py-2"
                                    >
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full sm:h-8 sm:w-8">
                                            <CheckCircle className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                                        </div>
                                        <span className="text-sm sm:text-base">
                                            {ap}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Qualifications */}
                    <div className="mt-20 flex flex-col gap-10 md:flex-row">
                        <Card className="mt-6 flex-1 shadow-lg sm:mt-10">
                            <CardTitle className="sticky top-0 z-10 flex flex-row gap-2 rounded-t-sm bg-gradient-to-br from-secondary-orange/5 to-transparent p-4 font-light text-primary sm:gap-3 sm:p-6">
                                <Cookie className="h-4 w-4 sm:h-5 sm:w-5" />
                                Required Qualifications
                            </CardTitle>

                            <CardContent className="m-0 max-h-80 overflow-y-auto p-4 sm:max-h-[400px] sm:p-6">
                                <div className="mt-4 space-y-2 sm:mt-6 sm:space-y-3">
                                    {career.qualifications.map((ap) => (
                                        <div
                                            key={ap}
                                            className="flex items-center gap-2 py-1 sm:gap-3 sm:py-2"
                                        >
                                            <div className="flex h-6 w-6 items-center justify-center rounded-full sm:h-8 sm:w-8">
                                                <CheckCircle className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                                            </div>
                                            <span className="text-sm sm:text-base">
                                                {ap}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>
        </>
    );
}

CareerDetail.layout = (page: React.ReactNode) => {
    const career = (page as { props: { career: Career } }).props.career;

    return (
        <PublicLayout
            footer={{
                is_career: true,
                show_ready: true,
                show_contact: false,
                title: 'Ready to Apply?',
                desc: `Complete our application form to apply for the ${career.position} position. We'll review your submission and be in touch within 1-2 business days.`,
            }}
        >
            {page}
        </PublicLayout>
    );
};
