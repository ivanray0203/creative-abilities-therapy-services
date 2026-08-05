import { Head, Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { Process, WhyFscd } from '@/lib/content/fscd';

const BDS_GOALS = [
    'Practice communication and social skills',
    'Learn routines and follow structured activities',
    'Develop positive behaviour strategies',
    'Gain confidence and independence in daily tasks',
];

const SS_GOALS = [
    'Behaviour management and emotional regulation',
    'Communication and social interaction',
    'Physical development, including gross and fine motor skills',
    'Adaptive skills and independence in daily living',
    'Mental health support, coping strategies, and family guidance',
];

const KEY_DIFFERENCES = [
    {
        title: 'Intensity and Complexity',
        desc: 'BDS is generally less intensive, focusing on direct support from a behavioural aide. SS is multidisciplinary, structured, and more intensive for children with complex needs.',
    },
    {
        title: 'Type of Support',
        desc: 'BDS involves hands-on support under supervision, while SS integrates therapy and interventions from a team of professionals according to an individualized plan.',
    },
    {
        title: 'Eligibility',
        desc: 'BDS is designed for children requiring behavioural or developmental support. SS is intended for children with severe disabilities affecting multiple areas of functioning.',
    },
    {
        title: 'Duration',
        desc: 'BDS is usually planned in 6-month blocks with reviews, while SS is planned in 12-month blocks as part of an FSCD Agreement that can last up to three years.',
    },
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

                    <p className="text-center text-base leading-relaxed md:text-lg">
                        At{' '}
                        <span className="font-bold">
                            Creative Abilities Therapy Services
                        </span>
                        , we are proud to be an FSCD-approved service provider,
                        offering families access to both Behavioural
                        Developmental Services (BDS) and Specialized Services
                        (SS). The{' '}
                        <span className="font-bold">
                            Family Support for Children with Disabilities (FSCD)
                        </span>{' '}
                        program is designed by the Alberta government to help
                        children with disabilities reach their full potential.
                        FSCD provides funding and support for families to access
                        services that meet their child's unique developmental,
                        behavioural, physical, and emotional needs.
                    </p>
                </div>
            </section>

            {/* Guiding */}
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
                                    Guiding You Through Every Step
                                </p>
                            </div>

                            <h2 className="text-lg font-bold md:text-xl">
                                Empowering Every Child, Celebrating Every
                                Ability
                            </h2>

                            <p className="text-sm leading-relaxed text-muted-foreground md:text-base">
                                We understand that navigating the FSCD program
                                can be complex, and we are here to guide you
                                through every step of the process. From initial
                                intake and assessment to the creation of an
                                individualized services plan, our goal is to
                                ensure that your child's unique needs are met,
                                and that they receive the support they deserve.
                            </p>

                            <p className="text-sm leading-relaxed text-muted-foreground md:text-base">
                                By working closely with families and other
                                service providers, we ensure a holistic approach
                                to care that helps children reach their full
                                potential. Our commitment is to foster a better
                                quality of life for children and provide
                                families with the tools and resources they need
                                for continued success.
                            </p>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Button
                                    asChild
                                    className="w-full rounded-[10px] sm:w-auto"
                                >
                                    <Link href="/intake/apply">
                                        Start Intake Form
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="w-full rounded-[10px] border-2 border-primary text-primary sm:w-auto"
                                >
                                    <a
                                        href="#contact_us"
                                        onClick={(e) => {
                                            const el =
                                                document.getElementById(
                                                    'contact_us',
                                                );

                                            if (el) {
                                                e.preventDefault();
                                                el.scrollIntoView({
                                                    behavior: 'smooth',
                                                });
                                            }
                                        }}
                                    >
                                        Contact Us
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* What is */}
            <section id="what-is" className="py-32">
                <div className="flex flex-col items-center justify-center">
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
                            Alberta to provide support and funding to families
                            of children with disabilities. FSCD is designed to
                            help families access the services, resources, and
                            support their child needs to thrive at home, in
                            school, and in the community. The program is
                            family-centred, meaning that families are actively
                            involved in planning, decision-making, and goal
                            setting, ensuring that supports are tailored to the
                            child's unique needs and the family's circumstances.
                            FSCD can help cover costs associated with a child's
                            disability, such as respite care, medical-related
                            travel, equipment, therapy services, and behavioural
                            or developmental supports.
                        </p>

                        <p className="mt-5 px-4 text-center text-base leading-relaxed md:px-16 md:text-lg">
                            Services are flexible and coordinated to meet both
                            the child's and family's long-term needs, and the
                            program focuses on building on the family's
                            strengths while providing additional assistance
                            where required. Families must meet eligibility
                            requirements, including having a child under 18 who
                            resides in Alberta and has a disability that
                            significantly impacts daily functioning. Through
                            FSCD, families can access a range of supports
                            designed to promote their child's development,
                            independence, and participation in everyday life,
                            helping both children and families achieve
                            meaningful progress.
                        </p>

                        <p className="mt-5 px-4 text-center text-base leading-relaxed md:px-16 md:text-lg">
                            For more information, visit:{' '}
                            <a
                                href="https://www.alberta.ca/fscd"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-primary underline"
                            >
                                https://www.alberta.ca/fscd
                            </a>
                        </p>
                    </div>
                </div>
            </section>

            {/* Services */}
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

                {/* Services Grid */}
                <div className="flex flex-col gap-10 px-4 md:px-20">
                    {/* BDS Service */}
                    <div className="flex flex-col rounded-2xl bg-white p-6 shadow-md md:p-10">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white">
                            <LucideIcons.Target className="h-5 w-5" />
                        </div>
                        <p className="mt-5 font-semibold text-primary md:text-lg">
                            Behavioural and Developmental Support (BDS)
                        </p>
                        <p className="mt-5 text-sm leading-relaxed md:text-base">
                            Behavioural Developmental Services (BDS) focus on
                            providing structured, goal-oriented support to
                            children who need assistance with behavioural or
                            developmental challenges. BDS is typically delivered
                            by trained behavioural developmental aides who work
                            directly with your child under the guidance of
                            qualified professionals, such as psychologists or
                            behavioural consultants.
                        </p>

                        <p className="mt-10 font-semibold text-primary">
                            The primary purpose of BDS is to help children:
                        </p>
                        <div className="mt-4 space-y-2">
                            {BDS_GOALS.map((item) => (
                                <p
                                    key={item}
                                    className="flex flex-row items-start gap-2"
                                >
                                    <LucideIcons.CheckCircle2 className="mt-1 text-primary" />
                                    {item}
                                </p>
                            ))}
                        </div>

                        <div className="mt-10 rounded-xl bg-peach-cream/20 p-5 text-sm leading-relaxed md:text-base">
                            BDS is designed to be flexible and individualized.
                            FSCD funding usually supports BDS for blocks of
                            approximately six months, during which the child's
                            goals are actively monitored and progress is
                            documented. After each block, the service plan is
                            reviewed collaboratively by the family, FSCD
                            caseworker, and service provider to determine
                            whether additional support is needed, or if goals
                            can be adjusted.
                        </div>
                    </div>

                    {/* SS Service */}
                    <div className="flex flex-col rounded-2xl bg-primary/90 p-6 text-white shadow-md md:p-10">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white/20">
                            <LucideIcons.Users2 className="h-6 w-6" />
                        </div>
                        <p className="mt-5 font-semibold md:text-lg">
                            Specialized Services (SS)
                        </p>

                        <p className="mt-5 text-sm leading-relaxed md:text-base">
                            Specialized Services (SS) are designed for children
                            with more severe or complex disabilities whose needs
                            significantly impact multiple areas of daily living.
                            These services are more intensive than BDS and are
                            delivered by a multidisciplinary team.
                        </p>

                        <p className="mt-10 font-semibold">
                            SS focuses on providing coordinated, individualized
                            support for children who require interventions in
                            multiple domains, such as:
                        </p>
                        <div className="mt-4 space-y-2">
                            {SS_GOALS.map((item) => (
                                <p
                                    key={item}
                                    className="flex flex-row items-start gap-2"
                                >
                                    <LucideIcons.CheckCircle2 />
                                    {item}
                                </p>
                            ))}
                        </div>

                        <p className="mt-5 text-sm leading-relaxed md:text-base">
                            Each SS plan is formalized in an Individualized
                            Service Plan (ISP), which clearly outlines the
                            child's specific goals, the supports required, and
                            how progress will be measured. The ISP is reviewed
                            regularly and updated as the child grows and their
                            needs change. SS contracts are generally planned in
                            12-month blocks but are part of the broader FSCD
                            Agreement, which can last up to three years.
                        </p>
                    </div>
                </div>

                {/* Key Differences */}
                <div className="mx-4 mt-10 flex flex-col rounded-2xl border border-primary-orange/50 p-6 shadow-md md:mx-20 md:p-10">
                    <p className="mt-5 text-center font-semibold text-primary">
                        Key Differences Between BDS and SS
                    </p>

                    <div className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
                        {KEY_DIFFERENCES.map((item) => (
                            <div
                                key={item.title}
                                className="rounded-xl bg-secondary-orange/5 p-5 text-sm shadow-sm md:text-base"
                            >
                                <p className="font-semibold text-primary">
                                    {item.title}:
                                </p>
                                <p className="mt-2">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Process */}
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
                        How FSCD Works with Creative Abilities Therapy Services
                    </p>

                    <p className="mt-3 px-4 text-center text-base leading-relaxed md:px-[20%] md:text-xl">
                        We partner with Alberta's FSCD program to make accessing
                        services as smooth and supportive as possible.
                    </p>
                </div>

                {/* Process Steps with Image */}
                <div className="px-4 md:px-20">
                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        {/* Image */}
                        <div className="relative">
                            <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src="/images/800x600/photo-1708687045030-26702e62fc65_1_cropped.jpg"
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>

                        {/* Steps */}
                        <div className="space-y-6">
                            {Process.map((p) => (
                                <div key={p.id} className="flex flex-row gap-4">
                                    <div>
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary-orange text-lg font-semibold text-white">
                                            {p.id}
                                        </div>
                                    </div>

                                    <div>
                                        <p className="text-lg font-semibold text-primary">
                                            {p.title}
                                        </p>
                                        <p className="mt-1 text-sm leading-relaxed md:text-base">
                                            {p.desc}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Callout */}
                <div className="mx-4 mt-10 rounded-2xl bg-primary p-6 text-center text-base leading-relaxed text-white shadow-md md:mx-[10%] md:p-10 md:text-lg">
                    <p>
                        Through this collaboration, we aim to reduce stress for
                        families, ensure consistency across home and community
                        settings, and help every child make meaningful progress
                        in their development.
                    </p>
                </div>
            </section>

            {/* Why Choose Us */}
            <section id="why-us" className="bg-peach-cream/10 py-32">
                <div className="flex flex-col items-center justify-center px-4 md:px-20">
                    <p className="m-5 text-center text-xl font-semibold text-primary md:text-2xl">
                        Why Choose Us for FSCD Services?
                    </p>
                    <p className="px-2 text-center text-base leading-relaxed md:px-[20%] md:text-lg">
                        At Creative Abilities Therapy Services, we go beyond
                        delivering therapy — we build strong, supportive
                        partnerships with families.
                    </p>

                    {/* Features Grid */}
                    <div className="mt-10 grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {WhyFscd.map((j) => {
                            const IconComponent = LucideIcons[
                                j.icon as keyof typeof LucideIcons
                            ] as React.ElementType | undefined;

                            return (
                                <div
                                    key={j.id}
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
                                        {j.title}
                                    </p>
                                    <p className="pt-2 text-base font-light text-black">
                                        {j.desc}
                                    </p>
                                </div>
                            );
                        })}
                    </div>

                    {/* Commitment Section */}
                    <div className="mt-10 flex flex-row items-center gap-5 rounded-2xl border-l-0 bg-secondary-orange/5 p-5 shadow-sm md:w-1/2 md:border-l-4 md:border-primary">
                        <div className="relative">
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
                                At Creative Abilities Therapy Services, we
                                understand that every family's journey is
                                unique. We are committed to providing
                                exceptional, evidence-based care that respects
                                your family's values, culture, and goals.
                            </p>
                            <p className="mt-2 text-base leading-relaxed">
                                Our experienced professionals work together
                                seamlessly to ensure your child receives
                                coordinated, comprehensive support that leads to
                                meaningful, lasting outcomes.
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
            show_contact: false,
            title: 'Ready to Access FSCD Services?',
            desc: 'If your family is eligible for FSCD support and you are seeking specialized services for your child, contact Creative Abilities Therapy Services today. Our team will help you navigate the FSCD process, from application to service delivery, ensuring your child receives the best possible care.',
        }}
    >
        {page}
    </PublicLayout>
);
