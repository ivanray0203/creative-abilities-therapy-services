import { Head, Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import ScrollToContactButton from '@/components/scroll-to-contact-button';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { Process, ServiceComparison, WhyFscd } from '@/lib/content/fscd';

const BDS_PARAGRAPHS = [
    'Behavioural and Developmental Support (BDS) is an FSCD-funded service designed for children who need targeted support with behavioural, developmental, communication, social, or everyday living skills. Support is individualized around the child’s strengths, needs, goals, and family priorities.',
    'BDS is generally a less intensive service model and is typically provided for approximately six months. Depending on the child’s approved FSCD services, the team may include a Behavioural & Developmental Aide and up to two clinicians.',
    'During BDS, the team works collaboratively with the family to support identified goals, monitor progress, and determine whether the child’s needs can continue to be supported through BDS or whether a transition to more intensive Specialized Services (SS) may be appropriate.',
];

const SS_PARAGRAPHS = [
    'Specialized Services (SS) is an FSCD-funded service designed for children with more complex needs who require coordinated support across multiple areas of development. Services are individualized around the child’s strengths, needs, goals, and family priorities.',
    'SS is generally a more intensive, multidisciplinary service model and is typically provided over a 12-month period. Depending on the child’s approved FSCD services, the team may include a Clinical Coordinator, Behavioural & Developmental Aide, and up to four clinicians.',
    'The multidisciplinary team works collaboratively with the family to develop and implement an individualized service plan, coordinate goals across disciplines, monitor progress, and adjust strategies as the child’s needs change.',
    'Specialized Services may be appropriate when a child requires a higher level of coordinated support than can be provided through BDS. Some families may transition from Behavioural and Developmental Support (BDS) to Specialized Services (SS) when more intensive multidisciplinary support is needed.',
];

export default function Fscd() {
    return (
        <>
            <Head title="FSCD Partnership" />

            {/* Header */}
            <section
                id="fscd"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center px-4 py-20 md:px-20 md:py-40">
                    <div className="rounded-full border border-secondary-orange/20 bg-white px-6 py-3 shadow-sm">
                        <p className="flex flex-row items-center gap-3">
                            <Heart className="text-primary" />
                            <span className="text-center text-primary">
                                FSCD Partnership
                            </span>
                        </p>
                    </div>

                    <p className="my-5 text-center text-lg text-primary md:text-xl">
                        Family Support for Children with Disabilities (FSCD)
                    </p>

                    <p className="max-w-4xl text-center text-base leading-relaxed md:text-lg">
                        <span className="font-bold">
                            Creative Abilities Therapy Services
                        </span>{' '}
                        is an approved Family Support for Children with
                        Disabilities (FSCD) service provider. We support
                        families through Behavioural and Developmental Support
                        (BDS) and Specialized Services (SS), helping children
                        access individualized services based on their
                        developmental, behavioural, physical, communication, and
                        emotional needs.
                    </p>
                </div>
            </section>

            {/* Supporting your family */}
            <section id="guiding" className="py-20">
                <div className="container mx-auto">
                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        {/* Image */}
                        <div className="relative w-full">
                            <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src="/images/800x600/photo-1760267973986-5370a55550f4_1_cropped.jpg"
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>

                        {/* Text Content */}
                        <div className="space-y-6">
                            <div className="flex flex-row gap-3 text-primary">
                                <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3 text-sm md:text-base">
                                    Supporting Your Family Through Services
                                </p>
                            </div>

                            <h2 className="text-lg font-bold md:text-xl">
                                Empowering Every Child, Embracing Every Ability
                            </h2>

                            <p className="text-sm leading-relaxed text-muted-foreground md:text-base">
                                Once your family has been approved for FSCD
                                services and chooses Creative Abilities Therapy
                                Services as your provider, our team works with
                                you to understand your child&rsquo;s strengths,
                                needs, and goals. We collaborate with families,
                                FSCD caseworkers, and service providers to
                                develop and deliver individualized support based
                                on the services included in your FSCD agreement.
                            </p>

                            <p className="text-sm leading-relaxed text-muted-foreground md:text-base">
                                Our goal is to provide coordinated,
                                family-centred support that helps children build
                                meaningful skills, increase participation, and
                                grow in confidence and independence at home and
                                in their community.
                            </p>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Button
                                    asChild
                                    className="w-full rounded-[10px] sm:w-auto"
                                >
                                    <Link href="/intake/apply">
                                        Start Intake
                                    </Link>
                                </Button>
                                <ScrollToContactButton className="w-full rounded-[10px] border-2 border-primary bg-white text-primary hover:bg-white sm:w-auto">
                                    Contact Us
                                </ScrollToContactButton>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* What is FSCD */}
            <section id="what-is" className="py-32">
                <div className="flex flex-col items-center justify-center px-4">
                    {/* Badge */}
                    <div className="rounded-full border border-secondary-orange/20 bg-white px-6 py-3 shadow-sm">
                        <p className="flex flex-row gap-3 text-sm md:text-base">
                            <LucideIcons.ReceiptText className="text-primary" />
                            <span className="font-medium text-primary">
                                What is FSCD?
                            </span>
                        </p>
                    </div>

                    {/* Subtitle */}
                    <p className="m-5 text-lg font-semibold text-primary md:text-xl">
                        Understanding the FSCD Program
                    </p>

                    {/* Card */}
                    <div className="w-full max-w-5xl rounded-2xl bg-white p-5 shadow-md md:p-10">
                        <p className="px-4 text-center text-base leading-relaxed md:px-16 md:text-lg">
                            The Family Support for Children with Disabilities
                            (FSCD) program is offered by the Government of
                            Alberta to provide funding and support to eligible
                            families of children with disabilities. The program
                            is family-centred, meaning parents and caregivers
                            are involved in planning, decision-making, and
                            identifying the supports that best meet their
                            child&rsquo;s needs.
                        </p>

                        <p className="mt-5 px-4 text-center text-base leading-relaxed md:px-16 md:text-lg">
                            Depending on eligibility and the family&rsquo;s FSCD
                            agreement, funding may help support services such as
                            therapy, behavioural and developmental support,
                            respite, equipment, and other disability-related
                            needs. FSCD aims to support children&rsquo;s
                            development, independence, and participation at home
                            and in their community.
                        </p>

                        <div className="mt-8 rounded-xl bg-secondary-orange/5 p-5 md:p-8">
                            <p className="text-center font-semibold text-primary">
                                FSCD Eligibility
                            </p>
                            <p className="mt-3 text-center text-base leading-relaxed">
                                FSCD has specific eligibility requirements
                                determined by the Government of Alberta.
                                Families must apply directly through FSCD to
                                determine whether their child is eligible for
                                funding and support.
                            </p>
                            <p className="mt-4 text-center">
                                <a
                                    href="https://www.alberta.ca/fscd-eligibility"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="group inline-flex items-center gap-2 font-medium text-primary underline"
                                >
                                    Learn More About FSCD Eligibility
                                    <LucideIcons.ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* FSCD-approved services */}
            <section id="services" className="py-20">
                {/* Header */}
                <div className="mb-10 flex flex-col items-center justify-center px-4">
                    <div className="rounded-full border border-secondary-orange/20 bg-white px-6 py-3 shadow-sm">
                        <p className="flex flex-row gap-3 text-sm md:text-base">
                            <LucideIcons.CircleCheck className="text-primary" />
                            <span className="font-medium text-primary">
                                Our Services
                            </span>
                        </p>
                    </div>
                    <p className="mt-5 text-lg font-semibold text-primary md:text-xl">
                        FSCD-Approved Services
                    </p>
                </div>

                {/* Services */}
                <div className="flex flex-col gap-10 px-4 md:px-20">
                    {/* BDS */}
                    <div className="flex flex-col rounded-2xl bg-white p-6 shadow-md md:p-10">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white">
                            <LucideIcons.Target className="h-5 w-5" />
                        </div>
                        <p className="mt-5 font-semibold text-primary md:text-lg">
                            Behavioural and Developmental Support (BDS)
                        </p>

                        {BDS_PARAGRAPHS.map((paragraph) => (
                            <p
                                key={paragraph}
                                className="mt-5 text-sm leading-relaxed md:text-base"
                            >
                                {paragraph}
                            </p>
                        ))}
                    </div>

                    {/* SS */}
                    <div className="flex flex-col rounded-2xl bg-primary/90 p-6 text-white shadow-md md:p-10">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white/20">
                            <LucideIcons.Users2 className="h-6 w-6" />
                        </div>
                        <p className="mt-5 font-semibold md:text-lg">
                            Specialized Services (SS)
                        </p>

                        {SS_PARAGRAPHS.map((paragraph) => (
                            <p
                                key={paragraph}
                                className="mt-5 text-sm leading-relaxed md:text-base"
                            >
                                {paragraph}
                            </p>
                        ))}
                    </div>
                </div>

                {/* Key differences */}
                <div className="mx-4 mt-10 flex flex-col rounded-2xl border border-primary-orange/50 p-6 shadow-md md:mx-20 md:p-10">
                    <p className="text-center font-semibold text-primary">
                        Key Differences Between BDS and Specialized Services
                    </p>

                    {/*
                     * Three columns of prose do not fit a phone, so the table
                     * scrolls inside its own container rather than pushing the
                     * page sideways.
                     */}
                    <div className="mt-8 overflow-x-auto">
                        <table className="w-full min-w-[640px] border-collapse text-left text-sm md:text-base">
                            <thead>
                                <tr className="border-b border-primary-orange/30">
                                    <th className="p-3 font-semibold text-charcoal-gray" />
                                    <th className="p-3 font-semibold text-primary">
                                        Behavioural &amp; Developmental Support
                                        (BDS)
                                    </th>
                                    <th className="p-3 font-semibold text-primary">
                                        Specialized Services (SS)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {ServiceComparison.map((row) => (
                                    <tr
                                        key={row.label}
                                        className="border-b border-secondary-orange/20 align-top last:border-b-0"
                                    >
                                        <th
                                            scope="row"
                                            className="p-3 text-left font-semibold text-charcoal-gray"
                                        >
                                            {row.label}
                                        </th>
                                        <td className="p-3">{row.bds}</td>
                                        <td className="p-3">{row.ss}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {/* The process */}
            <section id="process" className="py-20">
                {/* Header */}
                <div className="mb-10 flex flex-col items-center justify-center px-4">
                    <div className="rounded-full border border-secondary-orange/20 bg-white px-6 py-3 shadow-sm">
                        <p className="flex flex-row gap-3 text-sm md:text-base">
                            <LucideIcons.ListChecks className="text-primary" />
                            <span className="font-medium text-primary">
                                The Process
                            </span>
                        </p>
                    </div>

                    <p className="mt-5 text-center text-lg font-semibold text-primary md:text-xl">
                        How FSCD Services Work with Creative Abilities Therapy
                        Services
                    </p>

                    <p className="mt-3 max-w-4xl px-4 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        Once your child has been approved for FSCD services,
                        Creative Abilities Therapy Services works
                        collaboratively with your family, FSCD caseworker, and
                        service team to coordinate the supports included in your
                        FSCD agreement.
                    </p>
                </div>

                {/* Steps with image */}
                <div className="px-4 md:px-20">
                    <div className="grid items-start gap-12 lg:grid-cols-2">
                        {/* Image */}
                        <div className="relative lg:sticky lg:top-32">
                            <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src="/images/800x600/photo-1708687045030-26702e62fc65_1_cropped.jpg"
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>

                        {/* Steps */}
                        <div className="space-y-8">
                            {Process.map((step) => (
                                <div
                                    key={step.id}
                                    className="flex flex-row gap-4"
                                >
                                    <div>
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary-orange text-lg font-semibold text-white">
                                            {step.id}
                                        </div>
                                    </div>

                                    <div className="space-y-3">
                                        <p className="text-lg font-semibold text-primary">
                                            Step {step.id} &mdash; {step.title}
                                        </p>

                                        {step.paragraphs.map((paragraph) => (
                                            <p
                                                key={paragraph}
                                                className="text-sm leading-relaxed md:text-base"
                                            >
                                                {paragraph}
                                            </p>
                                        ))}

                                        {step.lead_in && (
                                            <p className="text-sm font-medium md:text-base">
                                                {step.lead_in}
                                            </p>
                                        )}

                                        {step.options && (
                                            <ul className="space-y-2">
                                                {step.options.map((option) => (
                                                    <li
                                                        key={option}
                                                        className="flex items-start gap-2 text-sm leading-relaxed md:text-base"
                                                    >
                                                        <LucideIcons.CheckCircle2 className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                                        <span>{option}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}

                                        {step.after?.map((paragraph) => (
                                            <p
                                                key={paragraph}
                                                className="text-sm leading-relaxed md:text-base"
                                            >
                                                {paragraph}
                                            </p>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Callout */}
                <div className="mx-4 mt-10 rounded-2xl bg-primary p-6 text-center text-base leading-relaxed text-white shadow-md md:mx-[10%] md:p-10 md:text-lg">
                    <p>
                        Through ongoing collaboration, we work to create
                        consistency across home and community settings, support
                        families throughout services, and help each child make
                        meaningful progress toward their individual goals.
                    </p>
                </div>
            </section>

            {/* Why families choose CATS */}
            <section id="why-us" className="bg-peach-cream/10 py-32">
                <div className="flex flex-col items-center justify-center px-4 md:px-20">
                    <p className="m-5 text-center text-xl font-semibold text-primary md:text-2xl">
                        Why Families Choose CATS for FSCD Services
                    </p>
                    <p className="max-w-4xl px-2 text-center text-base leading-relaxed md:px-0 md:text-lg">
                        At Creative Abilities Therapy Services, we provide more
                        than individual services. We work collaboratively with
                        families and service teams to deliver coordinated,
                        individualized support based on each child&rsquo;s
                        strengths, needs, goals, and approved FSCD services.
                    </p>

                    {/* Features */}
                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {WhyFscd.map((point) => {
                            const IconComponent = LucideIcons[
                                point.icon as keyof typeof LucideIcons
                            ] as React.ElementType | undefined;

                            return (
                                <div
                                    key={point.id}
                                    className="flex flex-col items-start justify-start rounded-2xl bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md"
                                >
                                    {IconComponent ? (
                                        <div className="relative flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-primary to-primary/70 p-5 shadow-md transition-transform duration-300 hover:scale-110">
                                            <div className="absolute inset-0 bg-white/10 opacity-40 blur-xl" />
                                            <IconComponent className="relative z-10 h-6 w-6 text-white" />
                                        </div>
                                    ) : (
                                        <Heart className="h-6 w-6 text-primary" />
                                    )}

                                    <p className="pt-3 text-base font-semibold text-primary">
                                        {point.title}
                                    </p>
                                    <p className="pt-2 text-base font-light text-black">
                                        {point.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>

                    {/* Commitment */}
                    <div className="mt-10 flex flex-col items-center gap-5 rounded-2xl border-l-0 bg-secondary-orange/5 p-5 shadow-sm sm:flex-row md:w-2/3 md:border-l-4 md:border-primary">
                        <div className="relative w-full sm:w-1/3">
                            <div className="aspect-[16/9] overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src="/images/800x600/photo-1760704892974-60b5ddb59825_1_cropped.jpg"
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>

                        <div>
                            <p className="text-lg font-semibold text-primary">
                                Our Commitment to Excellence
                            </p>
                            <p className="mt-2 text-base leading-relaxed">
                                We are committed to providing coordinated,
                                evidence-based, and family-centred support that
                                reflects each child&rsquo;s strengths, needs,
                                and goals. Our team works collaboratively to
                                promote consistency across services and support
                                meaningful progress over time.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

Fscd.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: true,
            show_contact: true,
            title: 'Ready to Access FSCD Services?',
            desc: 'If your family has been approved for FSCD services and you are looking for a service provider, Creative Abilities Therapy Services is here to support you. Complete our intake form to tell us about your child, your approved services, and your family’s needs. Our team will review your information and connect with you about the next steps for beginning services with CATS.',
        }}
    >
        {page}
    </PublicLayout>
);
