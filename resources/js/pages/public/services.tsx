import { Head } from '@inertiajs/react';
import * as LucideIcons from 'lucide-react';
import { Sparkles } from 'lucide-react';
import { useEffect } from 'react';

import PublicLayout from '@/layouts/public-layout';
import { Funding, Journey, Regulated, Why } from '@/lib/content/services';
import type { Service } from '@/types/service';

/** Per-service icon, keyed by the code prefix, matching the reference's fixed icon-per-card mapping. */
const SERVICE_ICONS: Record<string, LucideIcons.LucideIcon> = {
    OTS: LucideIcons.Activity,
    SLTS: LucideIcons.MessageCircle,
    P: LucideIcons.Heart,
    BCCPS: LucideIcons.Brain,
    BDAS: LucideIcons.Smile,
};

function getServiceIcon(code: string): LucideIcons.LucideIcon {
    const prefix = code.split('-')[0];

    return SERVICE_ICONS[prefix] ?? LucideIcons.Heart;
}

/**
 * Ported 1:1 from cats-frontend/src/pages/Services.tsx. The reference
 * fetches services client-side via servicesAPI.get() and filters to
 * is_active; here the controller already passes only active services as
 * an Inertia prop, so no client-side fetch/filter is needed. The reference
 * also inlines each service's marketing "tile" markup directly in this page
 * rather than via a separate component (its ServiceCard.tsx is a different,
 * client-dashboard-only component), so the same inline structure is kept
 * here instead of extracting a service-card.tsx.
 */
export default function Services({ services }: { services: Service[] }) {
    // Scroll to the URL hash (e.g. #OTS-101) once services have rendered,
    // matching the reference's useLocation/hash effect.
    useEffect(() => {
        const hash = window.location.hash;

        if (!hash || services.length === 0) {
            return;
        }

        const timeout = setTimeout(() => {
            const element = document.querySelector(hash);

            if (element) {
                const headerOffset = 120;
                const elementPosition =
                    element.getBoundingClientRect().top + window.scrollY;
                const offsetPosition = elementPosition - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth',
                });
            }
        }, 150);

        return () => clearTimeout(timeout);
    }, [services]);

    return (
        <>
            <Head title="Services" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center px-4 py-20 md:px-0 md:py-40">
                    <div className="rounded-full border border-secondary-orange/20 bg-white px-6 py-3 shadow-sm">
                        <p className="flex flex-row items-center gap-3 text-center">
                            <Sparkles className="h-5 w-5 text-primary" />
                            <span className="text-base text-primary md:text-lg">
                                Our Therapy Services
                            </span>
                        </p>
                    </div>

                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Comprehensive Therapy Services
                    </p>

                    <p className="max-w-4xl px-2 text-center text-sm leading-relaxed md:px-0 md:text-base lg:text-xl">
                        We offer a comprehensive range of evidence-based
                        approaches, all in alignment with Alberta&apos;s
                        regulatory standards, to support children in reaching
                        their fullest potential.
                    </p>

                    <p className="mt-5 max-w-4xl px-2 text-center text-sm leading-relaxed text-charcoal-gray md:px-0 md:text-base lg:text-lg">
                        Our multidisciplinary team provides individualized
                        support in homes, and community settings throughout
                        Calgary and surrounding areas.
                    </p>
                </div>
            </section>

            {/* Journey */}
            <section id="journey" className="bg-peach-cream/10 py-20 md:px-20">
                <div className="flex flex-col items-center justify-center px-4 md:px-0">
                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Your Journey With Us
                    </p>
                    <p className="max-w-3xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        From first contact to celebrating progress, here&apos;s
                        what to expect when you work with Creative Abilities.
                    </p>

                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                        {Journey.map((j, index) => {
                            const IconComponent = (
                                LucideIcons as unknown as Record<
                                    string,
                                    LucideIcons.LucideIcon
                                >
                            )[j.icon.replace(' ', '')];

                            return (
                                <div
                                    key={j.id}
                                    className="group relative flex flex-col items-center justify-center rounded-xl border border-secondary-orange/20 bg-white p-6 hover:border-primary/80 sm:p-8"
                                >
                                    {index < Journey.length - 1 && (
                                        <div className="absolute top-1/2 right-0 hidden -translate-y-1/2 transform lg:block">
                                            <LucideIcons.ArrowRight className="h-6 w-6 text-primary" />
                                        </div>
                                    )}

                                    {IconComponent ? (
                                        <div className="relative flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary/70 p-5 shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:shadow-xl">
                                            <div className="absolute inset-0 bg-white/10 opacity-40 blur-xl"></div>
                                            <IconComponent className="relative z-10 h-6 w-6 text-white" />
                                        </div>
                                    ) : (
                                        <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                    )}

                                    <h1 className="pt-4 text-lg font-bold text-primary md:text-xl">
                                        Step {j.id}
                                    </h1>
                                    <p className="pt-2 text-base font-light text-primary md:text-lg">
                                        {j.title}
                                    </p>
                                    <p className="pt-2 text-center text-sm font-light text-black md:text-base">
                                        {j.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* Regulated */}
            <section
                id="regulated"
                className="bg-peach-cream/10 py-20 md:px-20"
            >
                <div className="flex flex-col items-center justify-center px-10 md:px-0">
                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Regulated and Accredited
                    </p>
                    <p className="max-w-3xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        Our services meet Alberta&apos;s highest professional
                        standards
                    </p>

                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        {Regulated.map((j) => {
                            const IconComponent = (
                                LucideIcons as unknown as Record<
                                    string,
                                    LucideIcons.LucideIcon
                                >
                            )[j.icon.replace(' ', '')];

                            return (
                                <div
                                    key={j.id}
                                    className="group relative flex flex-col items-center justify-center rounded-2xl bg-white p-6 shadow-sm hover:shadow-md"
                                >
                                    {IconComponent ? (
                                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-peach-cream">
                                            <IconComponent className="h-6 w-6 text-primary" />
                                        </div>
                                    ) : (
                                        <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                    )}

                                    <p className="pt-3 text-center text-base text-primary">
                                        {j.title}
                                    </p>
                                    <p className="pt-2 text-center text-sm font-light text-black">
                                        {j.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* Services */}
            <section id="services">
                <div className="px-4 lg:px-20">
                    {services?.map((service, index) => {
                        const ServiceIcon = getServiceIcon(service.code);

                        return (
                            <div
                                key={service.code}
                                id={`${service.code}`}
                                className="mb-10 overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl"
                            >
                                <div
                                    className={`flex flex-col lg:flex-row ${index % 2 === 0 ? 'lg:flex-row-reverse' : ''}`}
                                >
                                    {/* Image */}
                                    <div className="relative h-64 w-full flex-shrink-0 lg:h-auto lg:w-1/3">
                                        <img
                                            src={service.photo}
                                            alt="Therapist working with child"
                                            className="h-full w-full object-cover object-top"
                                        />
                                        <span className="absolute top-3 right-3 rounded-full bg-white/90 px-3 py-2 text-xs font-semibold text-primary shadow-lg">
                                            {service.main_tag}
                                        </span>
                                    </div>

                                    {/* Info */}
                                    <div className="flex-1 p-4 lg:p-10">
                                        {/* Title */}
                                        <div className="mb-4 flex flex-row items-center gap-5">
                                            <div className="rounded-xl bg-primary p-3 text-white shadow-md">
                                                <ServiceIcon />
                                            </div>
                                            <p className="text-xl font-semibold text-primary">
                                                {service.name}
                                            </p>
                                        </div>

                                        {/* Description */}
                                        <p className="pt-2 lg:pt-10">
                                            {service.description}
                                        </p>

                                        {/* Key Areas */}
                                        <div className="mt-5 rounded-xl bg-secondary-orange/5 p-4 lg:p-5">
                                            <p className="flex flex-row gap-2 text-primary">
                                                <LucideIcons.SparklesIcon /> Key
                                                areas of focus include:
                                            </p>
                                            {service.area_of_focus.map((a) => (
                                                <div
                                                    key={a.title}
                                                    className="mt-2 flex flex-row gap-2"
                                                >
                                                    <LucideIcons.CheckCircle2Icon className="text-primary" />
                                                    <p>
                                                        <span className="font-bold">
                                                            {a.title}
                                                        </span>
                                                        : {a.desc}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>

                                        {/* Who Is This Service For */}
                                        <div className="mt-5 rounded-xl bg-secondary-orange/5 p-4 lg:p-5">
                                            <p className="flex flex-row gap-2 text-primary">
                                                <LucideIcons.Users /> Who is
                                                This Service For
                                            </p>
                                            <div className="mt-2 flex flex-row gap-2">
                                                <LucideIcons.CheckCircle2Icon className="text-primary" />
                                                <span className="font-bold">
                                                    Ages:
                                                </span>{' '}
                                                {service.ages}
                                            </div>
                                            <div className="mt-2 flex flex-row gap-2">
                                                <LucideIcons.CheckCircle2Icon className="text-primary" />
                                                <span className="font-bold">
                                                    Diagnosis:
                                                </span>{' '}
                                                {service.conditions}
                                            </div>
                                        </div>

                                        {/* Duration, Frequency, Location */}
                                        <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:mt-10 lg:grid-cols-3 lg:gap-5">
                                            <div className="flex flex-row items-center gap-2 rounded-xl border p-3">
                                                <LucideIcons.TimerIcon className="text-primary-orange" />
                                                <div>
                                                    <p className="text-xs text-charcoal-gray">
                                                        Duration
                                                    </p>
                                                    <p className="text-sm">
                                                        {service.duration}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex flex-row items-center gap-2 rounded-xl border p-3">
                                                <LucideIcons.Calendar className="text-primary-orange" />
                                                <div>
                                                    <p className="text-xs text-charcoal-gray">
                                                        Frequency
                                                    </p>
                                                    <p className="text-sm">
                                                        {service.frequency}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex flex-row items-center gap-2 rounded-xl border p-3">
                                                <LucideIcons.MapPin className="text-primary-orange" />
                                                <div>
                                                    <p className="text-xs text-charcoal-gray">
                                                        Location
                                                    </p>
                                                    <p className="text-sm">
                                                        {service.location}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Tags */}
                                        <div className="mt-4 mb-5 flex flex-wrap gap-2 lg:mt-5 lg:mb-10">
                                            {service.tags.map((tag, idx) => (
                                                <span
                                                    key={idx}
                                                    className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"
                                                >
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </section>

            {/* Funding */}
            <section id="funding" className="bg-peach-cream/10">
                <div className="flex flex-col items-center justify-center px-4 py-20 md:px-20 md:py-40">
                    <p className="mb-5 text-center text-xl text-primary md:text-2xl">
                        Funding & Payment Options
                    </p>
                    <p className="text-center text-base leading-loose md:text-lg">
                        We work with various funding sources to make our
                        services accessible to families across Alberta.
                    </p>

                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2">
                        {Funding.map((j) => (
                            <div
                                key={j.id}
                                className="flex flex-col items-start justify-start rounded-2xl border border-secondary-orange/20 bg-white p-4 shadow-sm transition-all duration-300 hover:border-secondary-orange/80 md:p-5"
                            >
                                <div className="flex items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/70 p-4 shadow-lg md:p-5">
                                    <LucideIcons.CircleCheck className="h-6 w-6 text-white md:h-8 md:w-8" />
                                </div>

                                <p className="pt-3 text-lg font-semibold text-primary md:text-xl">
                                    {j.title}
                                </p>
                                <p className="pt-2 text-base font-light text-black md:text-lg">
                                    {j.desc}
                                </p>
                                {j.sub_desc && (
                                    <p className="pt-2 text-sm font-light text-charcoal-gray md:text-base">
                                        {j.sub_desc}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Why Choose */}
            <section id="why-choose" className="bg-peach-cream/10">
                <div className="flex flex-col items-center justify-center px-4 py-20 md:px-20">
                    <p className="mb-5 text-center text-xl text-primary md:text-2xl">
                        Why Choose Creative Abilities?
                    </p>
                    <p className="px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        Our approach is rooted in collaboration, compassion, and
                        evidence-based practice. We work alongside families to
                        create meaningful, positive experiences.
                    </p>

                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {Why.map((j) => {
                            const IconComponent = (
                                LucideIcons as unknown as Record<
                                    string,
                                    LucideIcons.LucideIcon
                                >
                            )[j.icon.replace(' ', '')];

                            return (
                                <div
                                    key={j.id}
                                    className="flex flex-col items-center rounded-2xl bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md"
                                >
                                    {IconComponent ? (
                                        <div className="group relative flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary/70 p-5 shadow-lg transition-all duration-300 group-hover:scale-110">
                                            <div className="absolute inset-0 bg-white/10 opacity-40 blur-xl"></div>
                                            <IconComponent className="relative z-10 h-6 w-6 text-white" />
                                        </div>
                                    ) : (
                                        <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                    )}

                                    <p className="pt-3 text-center text-base font-light text-primary md:text-lg">
                                        {j.title}
                                    </p>
                                    <p className="pt-2 text-center text-sm font-light text-black md:text-base">
                                        {j.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>
        </>
    );
}

Services.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            show_ready: true,
            show_contact: false,
            title: "Let's Work Together",
            is_career: false,
            desc: "Connect with our compassionate team today to learn how we can support your child's unique journey and help them reach their fullest potential.",
        }}
    >
        {page}
    </PublicLayout>
);
