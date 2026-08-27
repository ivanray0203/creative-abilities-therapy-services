import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Luggage, MapPin } from 'lucide-react';
import * as LucideIcons from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import {
    APPLICATION_TIMELINE,
    ApplicationProcess,
    WhyWorkWithCats,
} from '@/lib/content/careers-config';
import type { Career } from '@/types/career';

/**
 * Ported 1:1 from cats-frontend/src/pages/Careers.tsx. The reference loaded a
 * static `Careers` array from src/json/careers; this page sources the live
 * listing from the database via the `careers` Inertia prop (CareerController).
 */
export default function Careers({ careers }: { careers: Career[] }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedLocation, setSelectedLocation] = useState('');
    const [selectedExperience, setSelectedExperience] = useState('');

    const filteredCareers = useMemo(() => {
        if (!careers) {
            return [];
        }

        return careers.filter((c) => {
            const matchesSearch = c.position
                .toLowerCase()
                .includes(searchTerm.toLowerCase());
            const matchesLocation = selectedLocation
                ? c.location === selectedLocation
                : true;
            const matchesExperience = selectedExperience
                ? c.level.toLowerCase() === selectedExperience.toLowerCase()
                : true;

            return matchesSearch && matchesLocation && matchesExperience;
        });
    }, [searchTerm, selectedLocation, selectedExperience, careers]);

    return (
        <>
            <Head title="Careers" />

            <div className="min-h-screen bg-gradient-to-br from-secondary/30 to-background">
                {/* Hero Section */}
                <section id="home" className="bg-primary-orange/10 py-20">
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            {/* Text Content */}
                            <div className="space-y-6">
                                <div className="flex gap-4">
                                    <Button size="lg">
                                        <Luggage /> Join Our Team
                                    </Button>
                                </div>

                                <div>
                                    <p className="text-xl font-semibold text-primary md:text-2xl">
                                        Build Your Career with Creative
                                        Abilities Therapy Services
                                    </p>
                                </div>

                                <p className="text-lg leading-relaxed text-foreground md:text-xl">
                                    We&rsquo;re looking for dedicated
                                    professionals who are passionate about
                                    supporting children and families through
                                    individualized, family-centred care. At
                                    Creative Abilities Therapy Services,
                                    you&rsquo;ll have the opportunity to
                                    contribute your skills while working as part
                                    of a collaborative and growing team.
                                </p>

                                <p className="text-lg leading-relaxed md:text-xl">
                                    We offer flexible, contract-based
                                    opportunities for Speech-Language
                                    Pathologists, Psychologists, Occupational
                                    Therapists, Physiotherapists, Behavioural
                                    Consultants, Behavioural &amp; Developmental
                                    Aides, and Community &amp; Respite Aides. We
                                    welcome professionals who value
                                    collaboration, individualized support, and
                                    family-centred care.
                                </p>
                            </div>

                            {/* Image */}
                            <div className="relative w-full">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-primary/5 to-primary/10 shadow-2xl">
                                    <img
                                        src="/images/800x600/photo-1592392821486-71f028a00581_1_cropped.jpg"
                                        alt="Children engaging in therapy activities"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Process Section */}
                <section id="process" className="bg-background py-20">
                    <div className="container mx-auto">
                        {/* Header */}
                        <div className="mb-12 space-y-3 text-center">
                            <div className="inline-block rounded-full bg-primary/15 px-3 py-1 text-base font-light text-primary">
                                Application Process
                            </div>
                            <h2 className="text-xl font-semibold md:text-2xl">
                                Your Journey to Joining CATS
                            </h2>
                            <p className="mx-auto max-w-2xl leading-relaxed text-muted-foreground">
                                Our application process is designed to be clear
                                and straightforward, giving both you and our
                                team the opportunity to determine whether the
                                role is a good fit.
                            </p>
                        </div>

                        {/* Steps */}
                        <div className="relative grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                            {ApplicationProcess.map((app, index) => {
                                const IconComponent = LucideIcons[
                                    app.icon.replace(
                                        ' ',
                                        '',
                                    ) as keyof typeof LucideIcons
                                ] as React.ElementType | undefined;

                                return (
                                    <div
                                        key={app.id}
                                        className="relative flex flex-col items-center justify-center"
                                    >
                                        {/* Connecting lines for larger screens */}
                                        {index > 0 &&
                                            index <
                                                ApplicationProcess.length && (
                                                <div className="absolute top-[46px] left-0 hidden h-[3px] w-1/2 bg-primary/50 lg:block" />
                                            )}
                                        {index <
                                            ApplicationProcess.length - 1 && (
                                            <div className="absolute top-[46px] right-0 hidden h-[3px] w-1/2 bg-primary/50 lg:block" />
                                        )}

                                        {/* Icon */}
                                        <div className="relative z-10">
                                            {IconComponent ? (
                                                <div className="relative flex items-center justify-center overflow-hidden rounded-sm bg-primary p-5 shadow-md transition-transform duration-300 hover:scale-105">
                                                    <div className="absolute inset-0 bg-white/10 opacity-40 blur-xl" />
                                                    <IconComponent className="relative z-10 h-10 w-10 text-white" />
                                                </div>
                                            ) : (
                                                <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                            )}
                                        </div>

                                        {/* Step card */}
                                        <div className="mt-6 flex flex-col items-center rounded-lg border-2 border-primary/20 p-6 shadow-md transition-all hover:border-primary/70 hover:shadow-xl">
                                            <p className="rounded-lg bg-primary px-3 py-1 text-xs text-white">
                                                Step {app.id}
                                            </p>
                                            <p className="mt-2 text-center text-sm font-semibold text-primary">
                                                {app.title}
                                            </p>
                                            <p className="mt-2 text-center text-sm text-muted-foreground">
                                                {app.description}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Total Timeline */}
                        <div className="mt-10 space-y-2 rounded-sm border border-primary/40 bg-secondary-orange/5 p-6 text-center text-sm md:text-base">
                            <p className="font-semibold text-primary">
                                {APPLICATION_TIMELINE.label}
                            </p>
                            <p className="text-muted-foreground">
                                {APPLICATION_TIMELINE.note}
                            </p>
                        </div>
                    </div>
                </section>

                {/* Benefits */}
                <section id="benefits" className="bg-background py-40">
                    <div className="flex flex-col items-center justify-center">
                        {/* Header */}
                        <p className="m-5 text-xl font-semibold text-primary">
                            Why Work With CATS
                        </p>
                        <p className="px-4 text-center text-base leading-relaxed text-muted-foreground md:px-[20%]">
                            We offer flexible contractor opportunities designed
                            to support professional growth, collaboration, and
                            work-life balance.
                        </p>

                        {/* Benefits grid */}
                        <div className="mt-10 grid w-full grid-cols-1 gap-10 px-4 sm:grid-cols-2 md:px-20 lg:grid-cols-3">
                            {WhyWorkWithCats.map((benefit) => {
                                const IconComponent = LucideIcons[
                                    benefit.icon.replace(
                                        ' ',
                                        '',
                                    ) as keyof typeof LucideIcons
                                ] as React.ElementType | undefined;

                                return (
                                    <div
                                        key={benefit.id}
                                        className="flex flex-col items-start rounded-lg bg-white p-8 shadow-md transition-all duration-300 hover:shadow-xl"
                                    >
                                        {/* Icon */}
                                        {IconComponent ? (
                                            <div className="relative flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary/70 p-5 shadow-md transition-transform duration-300 hover:scale-110">
                                                <div className="absolute inset-0 bg-white/10 opacity-40 blur-xl" />
                                                <IconComponent className="relative z-10 h-6 w-6 text-white" />
                                            </div>
                                        ) : (
                                            <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                        )}

                                        {/* Title */}
                                        <p className="pt-4 text-base font-semibold text-primary">
                                            {benefit.title}
                                        </p>

                                        {/* Description */}
                                        <p className="pt-2 text-sm font-light text-black">
                                            {benefit.desc}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Call to action */}
                        <div className="mt-10 rounded-lg border border-primary/40 bg-secondary-orange/5 p-8 text-center text-sm text-muted-foreground md:text-base">
                            Whether you&rsquo;re an experienced professional or
                            just beginning your career, we&rsquo;d be happy to
                            hear from you.
                        </div>
                    </div>
                </section>

                {/* Open Positions */}
                <section id="positions" className="bg-background py-20">
                    <div className="container mx-auto">
                        {/* Header */}
                        <div className="mb-12 space-y-4 px-4 text-center sm:px-0">
                            <div
                                className="inline-block rounded-full bg-primary/15 px-4 py-1 text-sm font-medium text-primary sm:text-base"
                                aria-label="Section label: Open Positions"
                            >
                                Open Positions
                            </div>

                            <h2 className="text-2xl font-bold text-primary sm:text-3xl">
                                Current Openings
                            </h2>

                            <p className="mx-auto max-w-2xl text-sm leading-relaxed text-muted-foreground sm:text-base">
                                Explore our current contract opportunities and
                                find a role that aligns with your experience,
                                interests, and availability.
                            </p>
                        </div>

                        {/* Search + Filters */}
                        <div className="mb-12 rounded-xl border border-primary/10 bg-white p-6 shadow-md">
                            <div className="flex flex-col gap-4 md:flex-row">
                                {/* Position Search */}
                                <input
                                    type="text"
                                    placeholder="Search position..."
                                    className="rounded-[5px] border border-primary/20 p-3 transition focus:border-primary focus:ring focus:ring-primary/20"
                                    value={searchTerm}
                                    onChange={(e) =>
                                        setSearchTerm(e.target.value)
                                    }
                                    aria-label="Search by position"
                                />

                                {/* Search Button */}
                                <button
                                    className="w-20 rounded-[5px] bg-primary p-3 text-white transition-colors duration-300 hover:bg-primary/90 sm:p-2 sm:text-sm"
                                    aria-label="Search positions"
                                >
                                    Search
                                </button>
                            </div>

                            {/* Active Filters */}
                            {(searchTerm ||
                                selectedLocation ||
                                selectedExperience) && (
                                <div className="mt-4 flex flex-wrap items-center gap-2">
                                    <span className="font-medium text-muted-foreground">
                                        Active Filters:
                                    </span>

                                    {searchTerm && (
                                        <div className="flex items-center gap-1 rounded border border-secondary-orange/20 bg-secondary-orange/5 px-2 py-1 text-sm text-primary">
                                            Search: {searchTerm}
                                            <button
                                                className="ml-1"
                                                onClick={() =>
                                                    setSearchTerm('')
                                                }
                                                aria-label="Clear search filter"
                                            >
                                                <LucideIcons.X className="h-3 w-3" />
                                            </button>
                                        </div>
                                    )}

                                    {selectedLocation && (
                                        <div className="flex items-center gap-1 rounded border border-secondary-orange/20 bg-secondary-orange/5 px-2 py-1 text-sm text-primary">
                                            Location: {selectedLocation}
                                            <button
                                                className="ml-1"
                                                onClick={() =>
                                                    setSelectedLocation('')
                                                }
                                                aria-label="Clear location filter"
                                            >
                                                <LucideIcons.X className="h-3 w-3" />
                                            </button>
                                        </div>
                                    )}

                                    {selectedExperience && (
                                        <div className="flex items-center gap-1 rounded border border-secondary-orange/20 bg-secondary-orange/5 px-2 py-1 text-sm text-primary">
                                            Experience: {selectedExperience}
                                            <button
                                                className="ml-1"
                                                onClick={() =>
                                                    setSelectedExperience('')
                                                }
                                                aria-label="Clear experience filter"
                                            >
                                                <LucideIcons.X className="h-3 w-3" />
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Summary */}
                            <p className="mt-4 text-sm text-muted-foreground">
                                Showing{' '}
                                <span className="font-semibold text-primary">
                                    {filteredCareers.length}
                                </span>{' '}
                                of{' '}
                                <span className="font-semibold">
                                    {careers.length}
                                </span>{' '}
                                positions
                            </p>
                        </div>

                        {/* Positions List */}
                        <div id="positions-list">
                            {filteredCareers?.length > 0 ? (
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                                    {filteredCareers.map((c) => (
                                        <div
                                            key={c.id}
                                            className="group overflow-hidden rounded-lg shadow-lg transition-transform duration-300 ease-in-out hover:border hover:border-primary hover:shadow-2xl"
                                        >
                                            {/* Header */}
                                            <div className="flex flex-col items-center justify-center rounded-t-sm bg-primary py-8 transition-transform duration-300 ease-in-out group-hover:scale-105">
                                                <div className="rounded-sm bg-white/10 p-5">
                                                    <LucideIcons.Brain className="h-10 w-10 text-white" />
                                                </div>
                                                <p className="mt-2 text-center text-sm text-white sm:text-base">
                                                    {c.position}
                                                </p>
                                            </div>

                                            {/* Info */}
                                            <div className="rounded-b-sm bg-white p-4 sm:p-5">
                                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <div className="flex flex-col gap-3 text-sm sm:gap-5 sm:text-base">
                                                        <p className="flex items-center gap-2">
                                                            <MapPin className="h-4 w-4 text-primary" />{' '}
                                                            {c.location}
                                                        </p>
                                                        <p className="flex items-center gap-2">
                                                            <LucideIcons.DollarSign className="h-4 w-4 text-primary" />{' '}
                                                            {c.rate}
                                                        </p>
                                                    </div>

                                                    <div className="flex flex-col gap-3 text-sm sm:gap-5 sm:text-base">
                                                        <p className="flex items-center gap-2">
                                                            <LucideIcons.Briefcase className="h-4 w-4 text-primary" />{' '}
                                                            {c.level}
                                                        </p>
                                                        <p className="flex items-center gap-2">
                                                            <LucideIcons.CircleChevronLeft className="h-4 w-4 text-primary" />{' '}
                                                            {c.hours}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="mt-4 flex flex-col justify-between gap-3 sm:mt-6 sm:flex-row">
                                                    <Button
                                                        asChild
                                                        className="flex-1 rounded-[5px] border border-primary text-primary"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            id={`career-details-${c.id}`}
                                                            href={`/careers/${c.id}`}
                                                            onClick={() =>
                                                                window.scroll(
                                                                    0,
                                                                    0,
                                                                )
                                                            }
                                                        >
                                                            View Details{' '}
                                                            <ArrowRight />
                                                        </Link>
                                                    </Button>

                                                    <Button
                                                        asChild
                                                        className="flex-1 rounded-[5px]"
                                                    >
                                                        <Link
                                                            id={`career-apply-${c.id}`}
                                                            href={`/careers/apply/${c.id}?position=${c.position}`}
                                                        >
                                                            Apply Now{' '}
                                                            <Luggage />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center gap-4 rounded-lg bg-gray-50 p-8 shadow-md">
                                    <div className="rounded-full bg-gray-200 p-4">
                                        <LucideIcons.Search className="h-6 w-6 text-gray-500" />
                                    </div>

                                    <h3 className="text-center text-lg font-semibold text-gray-700">
                                        No Positions Found
                                    </h3>

                                    <p className="max-w-sm text-center text-gray-500">
                                        Try adjusting your filters or search
                                        terms to find more opportunities.
                                    </p>

                                    <Button
                                        className="mt-2 rounded-[10px] bg-primary px-6 py-2 text-white transition-colors duration-300 hover:bg-primary/90"
                                        onClick={() => {
                                            setSearchTerm('');
                                            setSelectedExperience('');
                                            setSelectedLocation('');
                                        }}
                                    >
                                        Clear All Filters
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

Careers.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: false,
            title: 'Ready to Get Started?',
            desc: "Take the first step toward supporting your child's growth and development. Complete our intake form today, and we'll be in touch within 1-2 business days.",
        }}
    >
        {page}
    </PublicLayout>
);
