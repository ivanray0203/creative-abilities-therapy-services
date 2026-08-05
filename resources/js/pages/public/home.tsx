import { Head, Link } from '@inertiajs/react';
import { ArrowRight, MapPin } from 'lucide-react';
import { useEffect } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';

const COMMUNITIES_SERVED = [
    'Airdrie',
    'Chestermere',
    'Strathmore',
    'Cochrane',
    'Okotoks',
    'Nearby areas',
];

/**
 * Ported 1:1 from cats-frontend/src/pages/Home.tsx. The commented-out
 * services-preview grid (fed by a live `servicesAPI.get()` call in the
 * reference) was left disabled there too, so it isn't ported here.
 */
export default function Home() {
    // Scroll to a section given by `?scroll=` query param, matching the
    // reference's useSearchParams-driven effect.
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const section = params.get('scroll');

        if (section) {
            const el = document.getElementById(section);

            if (el) {
                setTimeout(() => {
                    el.scrollIntoView({ behavior: 'smooth' });
                }, 200);
            }
        }
    }, []);

    // Scroll to a section given by the URL hash, matching the reference.
    useEffect(() => {
        const hash = window.location.hash;

        if (hash) {
            const section = document.querySelector(hash);

            if (section) {
                section.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }, []);

    return (
        <>
            <Head title="Home" />
            <div className="min-h-screen bg-gradient-to-br from-secondary/30 to-background">
                {/* Hero Section */}
                <section id="home" className="bg-primary-orange/10 py-20">
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div className="flex flex-col items-center space-y-6 md:items-start">
                                <div className="inline-block rounded-full bg-primary/20 px-3 py-1 text-sm font-medium text-primary">
                                    Welcome to Creative Abilities Therapy
                                    Services
                                </div>

                                <div>
                                    <div className="mb-0 flex flex-row flex-wrap gap-2">
                                        <h1 className="text-4xl font-bold text-black md:text-5xl">
                                            Empowering Every
                                        </h1>
                                        <h1 className="text-4xl font-bold text-primary md:text-5xl">
                                            Child,
                                        </h1>
                                    </div>
                                    <div className="mt-0 flex flex-row flex-wrap gap-2">
                                        <h1 className="text-4xl font-bold text-black md:text-5xl">
                                            Embracing Every
                                        </h1>
                                        <h1 className="text-4xl font-bold text-primary md:text-5xl">
                                            Ability.
                                        </h1>
                                    </div>
                                </div>

                                <p className="text-center text-lg text-muted-foreground md:text-left">
                                    Our mission is to empower every child and
                                    embrace every ability. We support children
                                    in building confidence and essential skills
                                    while working closely with families to help
                                    them thrive at home and in their community.
                                </p>
                                <div className="flex flex-col gap-4 md:flex-row">
                                    <Button asChild size="lg">
                                        <Link
                                            href="/intake/apply"
                                            onClick={() => window.scroll(0, 0)}
                                        >
                                            Get Started with Intake
                                        </Link>
                                    </Button>
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="outline"
                                        className="border-spacing-2 border-primary text-primary"
                                    >
                                        <Link
                                            href="/faq"
                                            onClick={() => window.scroll(0, 0)}
                                        >
                                            Learn More
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                            <div className="relative">
                                <div className="aspect-[4/3] -rotate-1 overflow-hidden rounded-2xl bg-gradient-to-br from-primary/5 to-primary/10 shadow-2xl transition-transform duration-300 ease-in-out hover:rotate-1">
                                    <img
                                        src="/images/1920x1080/photo_playing_1920x1080.jpg"
                                        alt="Children engaging in therapy activities"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Services Section */}
                <section id="services" className="bg-background py-20">
                    <div className="container mx-auto">
                        <div className="mb-12 space-y-3 text-center">
                            <div className="inline-block rounded-full bg-primary/15 px-3 py-1 text-base font-light text-primary">
                                What We Offer
                            </div>
                            <h2 className="text-2xl font-bold text-foreground">
                                Our Comprehensive Services
                            </h2>
                            <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                                At Creative Abilities Therapy Services, we
                                provide individualized support for children with
                                developmental and behavioural needs, including
                                Autism Spectrum Disorder (ASD), ADHD, and more.
                                Our team of behavioural consultants,
                                occupational therapists, speech-language
                                pathologists, physiotherapists, psychologists,
                                and behavioural aides works closely with
                                families to create personalized intervention
                                plans that help children thrive.
                            </p>
                        </div>

                        <div className="mt-10 flex items-center justify-center">
                            <Button asChild>
                                <Link
                                    href="/services"
                                    onClick={() => window.scroll(0, 0)}
                                >
                                    Explore Our Services <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* FSCD Partnership */}
                <section
                    id="about"
                    className="bg-gradient-to-b from-transparent via-secondary-orange/20 to-transparent py-[15%]"
                >
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div className="relative">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                    <img
                                        src="/images/800x600/photo-1617080090911-91409e3496ad_1_cropped.jpg"
                                        alt="Therapist working with child"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div className="inline-block rounded-full px-3 py-1 text-base font-medium text-primary">
                                    FSCD Partnership
                                </div>
                                <h2 className="text-xl font-bold text-foreground">
                                    FSCD Service Provider
                                </h2>
                                <p className="text-muted-foreground">
                                    We are an Approved Service Provider of
                                    Family Support for Children with
                                    Disabilities (FSCD) to provide specialized
                                    and behavioural developmental funded
                                    services for children with disabilities and
                                    their families.
                                </p>
                                <Card className="border-primary/20 bg-secondary/30">
                                    <CardContent className="space-y-3 p-6">
                                        <h3 className="font-semibold text-primary">
                                            What we Offer Thourgh FSCD
                                        </h3>
                                        <ul className="space-y-2">
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Specialized Services (SS)
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Behavioural and
                                                    Developmental Support (BDS)
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Direct billing at
                                                    FSCD-approved rates
                                                </span>
                                            </li>
                                        </ul>
                                    </CardContent>
                                </Card>

                                <div>
                                    <Button asChild className="rounded-[4px]">
                                        <Link
                                            href="/fscd"
                                            onClick={() => window.scroll(0, 0)}
                                        >
                                            Learn More About FSCD <ArrowRight />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* About Section */}
                <section id="about" className="py-20">
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div className="relative">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                    <img
                                        src="/images/800x600/photo-1709127347884-a106974ef58d_1_cropped.jpg"
                                        alt="Therapist working with child"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div className="inline-block rounded-full bg-primary/15 px-3 py-1 text-base font-light text-primary">
                                    Who We Are
                                </div>
                                <h2 className="text-base text-foreground sm:text-lg">
                                    Creative Abilities Therapy Services is
                                    committed to helping children reach their
                                    fullest potential through individualized,
                                    evidence-based care. We offer behavioural
                                    support, occupational therapy, speech
                                    language therapy, physiotherapy,
                                    psychological services, and more in
                                    alignment with Alberta&rsquo;s regulatory
                                    standards. Our compassionate
                                    multidisciplinary team works together to
                                    support children with behavioural and
                                    developmental challenges in a warm and
                                    supportive environment.
                                </h2>

                                <Card className="border-primary/20 bg-secondary-orange/10">
                                    <CardContent className="space-y-3 p-6">
                                        <h3 className="font-semibold text-primary">
                                            Our Approach
                                        </h3>
                                        <ul className="space-y-2">
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Evidence-based and
                                                    individualized interventions
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Family-centered and
                                                    collaborative care
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Flexible service delivery
                                                    options
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                    <span className="text-xs text-white">
                                                        ✓
                                                    </span>
                                                </div>
                                                <span className="text-sm">
                                                    Compassionate and hard
                                                    working professionals
                                                </span>
                                            </li>
                                        </ul>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Service Locations */}
                <section className="bg-background px-4 py-16 sm:px-6 md:px-8">
                    <div className="mx-auto max-w-6xl">
                        <div className="mb-12 space-y-3 text-center">
                            <div className="inline-block rounded-full bg-primary/15 px-3 py-1 text-sm font-light text-primary sm:text-base">
                                Where We Serve
                            </div>
                            <h2 className="text-xl font-semibold sm:text-2xl">
                                Service Locations
                            </h2>
                            <p className="mx-auto max-w-2xl text-sm text-muted-foreground sm:text-base">
                                We proudly serve families throughout Calgary and
                                the surrounding areas, offering flexible service
                                delivery in homes, community settings, and
                                online.
                            </p>
                        </div>

                        <div className="space-y-6 rounded-sm bg-gradient-to-b from-secondary-orange/5 to-transparent p-8 shadow-lg sm:p-12 md:p-20">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <Card>
                                    <CardContent className="space-y-4 p-6">
                                        <h3 className="text-lg font-semibold text-primary sm:text-xl">
                                            Primary Service Area
                                        </h3>
                                        <p className="text-sm text-foreground sm:text-base">
                                            Calgary and surrounding communities
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="space-y-4 p-6">
                                        <h3 className="text-lg font-semibold text-primary sm:text-xl">
                                            Communities We Serve
                                        </h3>
                                        <div className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3 sm:text-base">
                                            {COMMUNITIES_SERVED.map((area) => (
                                                <div
                                                    key={area}
                                                    className="flex items-center gap-1"
                                                >
                                                    <div className="h-1.5 w-1.5 rounded-full bg-primary" />
                                                    <span>{area}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <Card className="mx-auto max-w-4xl border-l-4 border-l-primary">
                                <CardContent className="p-6">
                                    <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                                        <div className="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                            <MapPin className="h-4 w-4 text-primary" />
                                        </div>
                                        <div>
                                            <h4 className="mb-1 font-semibold text-primary">
                                                Flexible Service Delivery:
                                            </h4>
                                            <p className="text-sm text-muted-foreground sm:text-base">
                                                We provide services in your
                                                home, in the community, or
                                                through secure online sessions
                                                to best meet your family&apos;s
                                                needs.
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

Home.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            show_ready: true,
            show_contact: true,
            title: 'Ready to Get Started?',
            desc: "Take the first step toward supporting your child's growth and development. Complete our intake form today, and we'll be in touch within 1-2 business days.",
            is_career: false,
        }}
    >
        {page}
    </PublicLayout>
);
