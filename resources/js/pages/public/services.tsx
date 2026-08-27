import { Head, Link } from '@inertiajs/react';
import * as LucideIcons from 'lucide-react';
import { useEffect } from 'react';

import FamilyHeartHand from '@/components/icons/family-heart-hand';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { ServiceList } from '@/lib/content/service-list';
import { Funding, Journey, Regulated, Why } from '@/lib/content/services';

/** Per-service icon, keyed by the code prefix. */
const SERVICE_ICONS: Record<string, LucideIcons.LucideIcon> = {
    SLTS: LucideIcons.MessageCircle,
    BCCPS: LucideIcons.Brain,
    OTS: LucideIcons.Activity,
    P: LucideIcons.Heart,
    BTC: LucideIcons.Sparkles,
    BDAS: LucideIcons.Smile,
    CRAS: LucideIcons.Users,
};

function getServiceIcon(code: string): LucideIcons.LucideIcon {
    return SERVICE_ICONS[code.split('-')[0]] ?? LucideIcons.Heart;
}

function iconByName(name: string): LucideIcons.LucideIcon | undefined {
    return (LucideIcons as unknown as Record<string, LucideIcons.LucideIcon>)[
        name.replace(' ', '')
    ];
}

export default function Services() {
    // Scroll to the URL hash (e.g. #SLTS-202) once the cards have rendered.
    useEffect(() => {
        const hash = window.location.hash;

        if (!hash) {
            return;
        }

        const timeout = setTimeout(() => {
            const element = document.querySelector(hash);

            if (element) {
                const headerOffset = 120;
                const elementPosition =
                    element.getBoundingClientRect().top + window.scrollY;

                window.scrollTo({
                    top: elementPosition - headerOffset,
                    behavior: 'smooth',
                });
            }
        }, 150);

        return () => clearTimeout(timeout);
    }, []);

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
                            <FamilyHeartHand className="h-7 w-7 shrink-0" />
                            <span className="text-base text-primary md:text-lg">
                                Our Services
                            </span>
                        </p>
                    </div>

                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Comprehensive Support for Children &amp; Families
                    </p>

                    <p className="max-w-4xl px-2 text-center text-sm leading-relaxed md:px-0 md:text-base lg:text-lg">
                        We provide individualized, evidence-based therapy and
                        developmental support for children and families. Our
                        multidisciplinary team works collaboratively to support
                        each child&rsquo;s strengths, needs, and goals, with
                        services available in home and community settings
                        throughout Calgary and surrounding communities.
                    </p>
                </div>
            </section>

            {/* Services */}
            <section id="services" className="py-10">
                <div className="container mx-auto grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    {ServiceList.map((service) => {
                        const ServiceIcon = getServiceIcon(service.code);

                        return (
                            <Card
                                key={service.code}
                                id={service.code}
                                className="flex scroll-mt-32 flex-col border-secondary-orange/20 transition-shadow hover:shadow-xl"
                            >
                                <CardContent className="flex flex-grow flex-col gap-4 p-6">
                                    <div className="flex flex-row items-center gap-4">
                                        <div className="rounded-xl bg-primary p-3 text-white shadow-md">
                                            <ServiceIcon className="h-5 w-5" />
                                        </div>
                                        <p className="text-lg font-semibold text-primary">
                                            {service.name}
                                        </p>
                                    </div>

                                    <p className="font-medium text-charcoal-gray">
                                        {service.tagline}
                                    </p>

                                    <p className="flex-grow text-sm leading-relaxed">
                                        {service.summary}
                                    </p>

                                    <Link
                                        href={`/servicesDetails/${service.code}`}
                                        onClick={() => window.scroll(0, 0)}
                                        className="group inline-flex items-center gap-2 font-medium text-primary"
                                    >
                                        Learn More
                                        <LucideIcons.ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </Link>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </section>

            {/* Journey */}
            <section id="journey" className="bg-peach-cream/10 py-20 md:px-20">
                <div className="flex flex-col items-center justify-center px-4 md:px-0">
                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Your Journey with CATS
                    </p>
                    <p className="max-w-3xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        From your first contact with Creative Abilities Therapy
                        Services to ongoing progress, here&rsquo;s what you can
                        expect as we support your child and family.
                    </p>

                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                        {Journey.map((step, index) => {
                            const IconComponent = iconByName(step.icon);

                            return (
                                <div
                                    key={step.id}
                                    className="group relative flex flex-col items-center justify-start rounded-xl border border-secondary-orange/20 bg-white p-6 hover:border-primary/80 sm:p-8"
                                >
                                    {index < Journey.length - 1 && (
                                        <div className="absolute top-1/2 right-0 hidden -translate-y-1/2 transform lg:block">
                                            <LucideIcons.ArrowRight className="h-6 w-6 text-primary" />
                                        </div>
                                    )}

                                    {IconComponent ? (
                                        <div className="relative flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary/70 p-5 shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:shadow-xl">
                                            <IconComponent className="relative z-10 h-6 w-6 text-white" />
                                        </div>
                                    ) : (
                                        <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                    )}

                                    <h3 className="pt-4 text-lg font-bold text-primary md:text-xl">
                                        Step {step.id}
                                    </h3>
                                    <p className="pt-2 text-center text-base font-light text-primary md:text-lg">
                                        {step.title}
                                    </p>
                                    <p className="pt-2 text-center text-sm font-light text-black md:text-base">
                                        {step.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* Professional standards */}
            <section
                id="regulated"
                className="bg-peach-cream/10 py-20 md:px-20"
            >
                <div className="flex flex-col items-center justify-center px-10 md:px-0">
                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Professional Standards &amp; Regulation
                    </p>
                    <p className="max-w-3xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        Our regulated professionals practise in accordance with
                        the standards and requirements of their respective
                        Alberta regulatory colleges.
                    </p>

                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-4">
                        {Regulated.map((body) => {
                            const IconComponent = iconByName(body.icon);

                            return (
                                <div
                                    key={body.id}
                                    className="flex flex-col items-center justify-start rounded-2xl bg-white p-6 shadow-sm hover:shadow-md"
                                >
                                    {IconComponent ? (
                                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-peach-cream">
                                            <IconComponent className="h-6 w-6 text-primary" />
                                        </div>
                                    ) : (
                                        <LucideIcons.Heart className="h-6 w-6 text-primary" />
                                    )}

                                    <p className="pt-3 text-center text-base font-semibold text-primary">
                                        {body.title}
                                    </p>
                                    <p className="pt-2 text-center text-sm font-light text-black">
                                        {body.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* Funding */}
            <section id="funding" className="py-20 md:px-20">
                <div className="flex flex-col items-center justify-center px-4 md:px-0">
                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Funding &amp; Payment Options
                    </p>
                    <p className="max-w-3xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        We work with different funding and payment options to
                        help families access the services and support they need.
                    </p>

                    <div className="mt-10 grid w-full max-w-5xl grid-cols-1 gap-6 md:grid-cols-2">
                        {Funding.map((option) => {
                            const IconComponent = iconByName(option.icon);

                            return (
                                <Card
                                    key={option.id}
                                    className="border-primary/20 bg-secondary-orange/5"
                                >
                                    <CardContent className="space-y-3 p-6">
                                        <div className="flex flex-row items-center gap-3">
                                            {IconComponent ? (
                                                <IconComponent className="h-5 w-5 text-primary" />
                                            ) : (
                                                <LucideIcons.Heart className="h-5 w-5 text-primary" />
                                            )}
                                            <p className="font-semibold text-primary">
                                                {option.title}
                                            </p>
                                        </div>
                                        <p className="text-sm leading-relaxed">
                                            {option.desc}
                                        </p>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* Why families choose CATS */}
            <section id="why" className="bg-peach-cream/10 py-20 md:px-20">
                <div className="flex flex-col items-center justify-center px-4 md:px-0">
                    <p className="m-5 text-center text-xl text-primary md:text-2xl">
                        Why Families Choose Creative Abilities Therapy Services?
                    </p>
                    <p className="max-w-3xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        Our approach is built on collaboration, compassion, and
                        evidence-based practice. We work closely with children
                        and families to provide individualized support that
                        reflects each child&rsquo;s strengths, needs, and goals.
                    </p>

                    <div className="mt-10 grid w-full max-w-5xl grid-cols-1 gap-6 md:grid-cols-2">
                        {Why.map((point) => {
                            const IconComponent = iconByName(point.icon);

                            return (
                                <Card
                                    key={point.id}
                                    className="border-secondary-orange/20 bg-white"
                                >
                                    <CardContent className="space-y-3 p-6">
                                        <div className="flex flex-row items-center gap-3">
                                            {IconComponent ? (
                                                <IconComponent className="h-5 w-5 text-primary" />
                                            ) : (
                                                <LucideIcons.Heart className="h-5 w-5 text-primary" />
                                            )}
                                            <p className="font-semibold text-primary">
                                                {point.title}
                                            </p>
                                        </div>
                                        <p className="text-sm leading-relaxed">
                                            {point.desc}
                                        </p>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>

                    <div className="mt-10">
                        <Button asChild>
                            <Link
                                href="/intake/apply"
                                onClick={() => window.scroll(0, 0)}
                            >
                                Start Intake <LucideIcons.ArrowRight />
                            </Link>
                        </Button>
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
            show_contact: true,
            title: "Let's Work Together",
            desc: "Connect with our team to learn more about our services and how we can support your child's strengths, needs, goals, and everyday participation.",
            is_career: false,
        }}
    >
        {page}
    </PublicLayout>
);
