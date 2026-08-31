import { Head, Link } from '@inertiajs/react';
import { HandCoinsIcon, Heart, Target, Users } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import CaringHandsHeart from '@/components/icons/caring-hands-heart';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { Approach, Approach2, Philosophy } from '@/lib/content/about';

const EVERYDAY_SKILLS = [
    { emoji: '💪', label: 'Confidence' },
    { emoji: '🌟', label: 'Independence' },
    { emoji: '🎯', label: 'Everyday Skills' },
    { emoji: '🤝', label: 'Meaningful Participation' },
];

export default function About() {
    return (
        <>
            <Head title="About Us" />

            <div className="min-h-screen bg-gradient-to-br from-secondary/30 to-background">
                {/* Header */}
                <section
                    id="header"
                    className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
                >
                    <div className="flex flex-col items-center justify-center px-4 py-20 md:px-20 md:py-40">
                        {/* Title Badge */}
                        <div className="rounded-sm bg-secondary-orange/5 p-3 shadow-lg">
                            <p className="flex flex-row items-center gap-3 text-center md:text-left">
                                <CaringHandsHeart className="h-7 w-7 shrink-0" />
                                <span className="text-base text-primary md:text-lg lg:text-xl">
                                    Welcome to Creative Abilities Therapy
                                    Services
                                </span>
                            </p>
                        </div>

                        {/* Subtitle */}
                        <p className="m-5 text-center text-sm text-charcoal-gray md:text-base lg:text-lg">
                            Empowering Every{' '}
                            <span className="text-primary">Child, </span>
                            Embracing Every{' '}
                            <span className="text-primary">Ability</span>
                        </p>

                        {/* Description */}
                        <p className="max-w-4xl text-center text-sm md:text-base lg:text-lg lg:leading-loose">
                            Creative Abilities Therapy Services provides
                            individualized, evidence-based therapy and
                            developmental support for children and families. Our
                            multidisciplinary team works together to support
                            each child&rsquo;s unique strengths, needs, and
                            goals while promoting meaningful growth,
                            participation, and independence.
                        </p>

                        {/* Additional Info */}
                        <p className="mt-6 max-w-4xl text-center text-sm text-charcoal-gray md:text-base lg:text-lg lg:leading-loose">
                            Our compassionate, multidisciplinary team works
                            collaboratively with children and families to
                            provide support in environments that are meaningful
                            to everyday life. Services may be provided at home,
                            in the community, and in other appropriate settings
                            throughout Calgary and surrounding communities.
                        </p>
                    </div>
                </section>

                {/* Mission */}
                <section
                    id="mission"
                    className="bg-gradient-to-b from-transparent to-peach-cream/20 py-20"
                >
                    <div className="container mx-auto">
                        <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                            {/* Image */}
                            <div className="relative w-full">
                                <div className="aspect-[6/4] overflow-hidden rounded-2xl shadow-2xl">
                                    <img
                                        src="/images/1920x1080/photo_family_1920x1080.jpg"
                                        alt="Therapist working with child"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>

                            {/* Text Content */}
                            <div className="space-y-6">
                                <div className="flex flex-row gap-3">
                                    <p className="flex flex-row items-center gap-3 rounded-lg bg-primary/10 p-3 text-sm text-primary sm:text-base">
                                        <Target className="h-5 w-5" /> Our
                                        Mission
                                    </p>
                                </div>

                                <h2 className="text-2xl font-bold text-charcoal-gray sm:text-3xl">
                                    Empowering Every Child, Embracing Every
                                    Ability.
                                </h2>

                                <p className="text-sm leading-relaxed text-black sm:text-base">
                                    Our mission is to empower every child by
                                    recognizing their unique strengths,
                                    abilities, and needs. We provide
                                    individualized, family-centred support that
                                    helps children build confidence, develop
                                    meaningful skills, and participate more
                                    fully in everyday life.
                                </p>

                                <p className="text-sm leading-relaxed text-black sm:text-base">
                                    We focus on meaningful progress in areas
                                    that support everyday life, including
                                    communication, sensory processing, emotional
                                    regulation, behaviour, physical development,
                                    and independence. Through a family-centred
                                    approach, we work closely with parents and
                                    caregivers to support each child at home, in
                                    school, and in their community.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Philosophy */}
                <section id="philosophy" className="bg-peach-cream/20">
                    <div className="flex w-full flex-col items-center justify-center px-4 py-20 md:px-0 md:py-40">
                        {/* Title badge */}
                        <div className="rounded-sm bg-primary/15 p-3">
                            <p className="flex flex-row items-center gap-3 text-primary">
                                <Heart className="h-5 w-5" />
                                <span className="text-base md:text-lg">
                                    Our Philosophy
                                </span>
                            </p>
                        </div>

                        {/* Subtitle */}
                        <p className="m-5 text-center text-sm text-charcoal-gray md:text-base lg:text-lg">
                            Helping Children Play, Grow, and Thrive
                        </p>

                        {/* Description */}
                        <p className="max-w-4xl px-2 text-center text-sm leading-relaxed md:px-0 md:text-base lg:text-lg">
                            We believe children learn and grow best when they
                            feel supported, understood, and encouraged. Through
                            play, connection, and individualized support, we
                            help children build functional skills, participate
                            more fully in everyday life, and grow in confidence
                            and independence.
                        </p>

                        {/* Cards */}
                        <div className="mt-10 grid w-full max-w-6xl grid-cols-1 gap-6 px-2 md:grid-cols-3 md:gap-10 md:px-0">
                            {Philosophy.map((p) => {
                                const IconComponent = LucideIcons[
                                    p.icon as keyof typeof LucideIcons
                                ] as React.ElementType | undefined;

                                return (
                                    <div
                                        key={p.id}
                                        className="flex flex-col items-center justify-center gap-5 rounded-sm bg-white p-6 shadow-lg md:p-10"
                                    >
                                        {IconComponent ? (
                                            <div className="flex items-center justify-center rounded-full bg-primary/80 p-5">
                                                <IconComponent className="h-5 w-5 text-white" />
                                            </div>
                                        ) : (
                                            <Heart className="h-5 w-5" />
                                        )}

                                        <p className="text-center text-lg font-light text-primary md:text-xl">
                                            {p.title}
                                        </p>

                                        <p className="mt-2 text-center text-sm md:text-base">
                                            {p.description}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Full-width bar */}
                        <div className="mt-10 flex w-full max-w-6xl flex-col items-center justify-center rounded-sm bg-primary/80 px-5 py-10 shadow-2xl md:px-20">
                            <p className="text-center text-lg text-white md:text-xl">
                                Building Skills for Everyday Life
                            </p>

                            <div className="mt-10 grid w-full grid-cols-2 gap-6 text-center sm:grid-cols-2 md:grid-cols-4">
                                {EVERYDAY_SKILLS.map((skill) => (
                                    <div
                                        key={skill.label}
                                        className="flex flex-col items-center gap-2 text-white"
                                    >
                                        <p className="text-4xl">
                                            {skill.emoji}
                                        </p>
                                        <p className="text-base">
                                            {skill.label}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Newcomers */}
                <section id="newcomers" className="bg-peach-cream/20 py-20">
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div className="space-y-6">
                                <div className="flex flex-row gap-3 text-primary">
                                    <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3">
                                        <LucideIcons.HandCoins /> Supporting
                                        Newcomer Families
                                    </p>
                                </div>
                                <h2 className="text-xl font-bold">
                                    Guiding Immigrant Families Through Canadian
                                    Systems
                                </h2>
                                <p className="leading-relaxed text-black">
                                    We proudly offer inclusive support to
                                    children with disabilities, with a focus on
                                    serving immigrant families who may be
                                    navigating new systems in Canada. We
                                    understand the unique challenges that
                                    newcomer families face when accessing
                                    healthcare, education, and social services
                                    in a new country.
                                </p>

                                <p className="leading-relaxed text-black">
                                    Our team provides culturally sensitive care
                                    and navigation assistance to help families
                                    understand and access the support systems
                                    available to them. We strive to ensure that
                                    every child feels seen, valued, and capable
                                    of achieving a meaningful life regardless of
                                    the challenges they face.
                                </p>

                                <Card className="border-primary/20 bg-secondary-orange/5">
                                    <CardContent className="space-y-3 p-6">
                                        <h3 className="font-semibold text-primary">
                                            How We Support Newcomer Families
                                        </h3>
                                        <ul className="space-y-2">
                                            {[
                                                'Navigation assistance for Canadian healthcare and education systems',
                                                'Culturally sensitive, inclusive therapy approaches',
                                                'Guidance on accessing funding and support programs',
                                                'Clear communication about therapy processes and expectations',
                                                "Support that respects each family's cultural background and values",
                                            ].map((item) => (
                                                <li
                                                    key={item}
                                                    className="flex items-start gap-2"
                                                >
                                                    <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                                        <span className="text-xs text-white">
                                                            ✓
                                                        </span>
                                                    </div>
                                                    <span className="text-sm">
                                                        {item}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </CardContent>
                                </Card>
                            </div>

                            <div className="relative">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                    <img
                                        src="/images/800x600/photo-1729670941973-1f5bb17eadaa_1_cropped.jpg"
                                        alt="Therapist working with child"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Team */}
                <section id="team" className="bg-peach-cream/20 py-20">
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div className="relative">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                    <img
                                        src="/images/800x600/photo-1620148222862-b95cf7405a7b_1_800x600.jpg"
                                        alt="Therapist working with child"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div className="flex flex-row gap-3 text-primary">
                                    <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3">
                                        <Users /> Our Team
                                    </p>
                                </div>
                                <h2 className="text-xl font-bold">
                                    A Collaborative Multidisciplinary Team
                                </h2>
                                <p className="leading-relaxed text-black">
                                    Our team includes Speech-Language
                                    Pathologists, Psychologists, Occupational
                                    Therapists, Physiotherapists, Behavioural
                                    Consultants, Behavioural &amp; Developmental
                                    Aides, and Community and Respite Aides who
                                    work collaboratively to support children and
                                    families. Together, we provide
                                    individualized, inclusive care that reflects
                                    each child&rsquo;s strengths, needs, and
                                    goals.
                                </p>
                                <div>
                                    <Button asChild className="rounded">
                                        <Link
                                            href="/team"
                                            onClick={() => window.scroll(0, 0)}
                                        >
                                            Meet Our Team
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Approach */}
                <section id="approach" className="bg-peach-cream/20">
                    <div className="flex flex-col items-center justify-center py-40">
                        <div className="rounded-sm bg-secondary-orange/5 p-3">
                            <p className="flex flex-row gap-3">
                                <HandCoinsIcon />{' '}
                                <span className="text-primary">
                                    Our Approach
                                </span>
                            </p>
                        </div>

                        <p className="m-5 text-primary">
                            How We Support Children and Families
                        </p>
                        <p className="text-md px-[20%] leading-loose">
                            At Creative Abilities Therapy Services, we recognize
                            that families play an important role in a
                            child&rsquo;s growth and development. We work
                            collaboratively with parents and caregivers to
                            develop individualized support plans that reflect
                            each child&rsquo;s strengths, needs, and goals.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-10 px-20 py-10 md:grid-cols-3">
                        {Approach.map((a) => {
                            const IconComponent = LucideIcons[
                                a.icon as keyof typeof LucideIcons
                            ] as React.ElementType | undefined;

                            return (
                                <div
                                    key={a.id}
                                    className="group rounded-sm bg-gradient-to-b from-primary-orange/5 to-transparent p-5 py-10 transition-all duration-300 hover:shadow-2xl"
                                >
                                    <div className="flex h-16 w-16 items-center justify-center rounded-sm bg-primary/20 shadow-lg transition-all duration-300 group-hover:scale-110">
                                        {IconComponent ? (
                                            <div className="flex items-center justify-center rounded-sm bg-gradient-to-br from-primary to-primary/70 p-4 shadow-[0_0_20px_rgba(0,0,0,0.25)] transition-all duration-300 group-hover:shadow-[0_0_35px_rgba(0,0,0,0.35)]">
                                                <IconComponent className="h-6 w-6 text-white" />
                                            </div>
                                        ) : (
                                            <Heart className="h-6 w-6 text-primary" />
                                        )}
                                    </div>

                                    <p className="my-5 text-primary">
                                        {a.title}
                                    </p>
                                    <p>{a.description}</p>
                                </div>
                            );
                        })}
                    </div>

                    <div className="grid grid-cols-1 gap-10 px-20 py-10 md:grid-cols-2">
                        {Approach2.map((a) => {
                            const IconComponent = LucideIcons[
                                a.icon as keyof typeof LucideIcons
                            ] as React.ElementType | undefined;

                            return (
                                <div
                                    key={a.id}
                                    className="group flex flex-row gap-5 rounded-sm bg-secondary/10 p-5 py-10 transition-all duration-300 hover:shadow-2xl"
                                >
                                    <div className="flex h-16 w-16 items-center justify-center rounded-sm bg-primary-orange/10 transition-all duration-300 group-hover:scale-110">
                                        {IconComponent ? (
                                            <div className="flex items-center justify-center rounded-sm bg-primary-orange/10 p-5">
                                                <IconComponent className="h-5 w-5 text-primary" />
                                            </div>
                                        ) : (
                                            <div className="flex items-center justify-center rounded-sm bg-primary-orange/10 p-5">
                                                <Heart className="h-5 w-5 text-primary" />
                                            </div>
                                        )}
                                    </div>

                                    <div>
                                        <p className="my-5 text-primary">
                                            {a.title}
                                        </p>
                                        <p>{a.description}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Child Development */}
                <section
                    id="child-development"
                    className="bg-peach-cream/15 py-20"
                >
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div className="relative">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                    <img
                                        src="/images/800x600/photo-1760267973986-5370a55550f4_1_cropped.jpg"
                                        alt="Therapist working with child"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div className="flex flex-row gap-3 text-primary">
                                    <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3">
                                        <LucideIcons.Cookie /> Behavioural &amp;
                                        Developmental Aides
                                    </p>
                                </div>
                                <h2 className="text-base">
                                    Creating Supportive Spaces for Growth
                                </h2>
                                <p className="leading-relaxed text-black">
                                    Our Behavioural &amp; Developmental Aides
                                    provide individualized support that helps
                                    children build skills through play, social
                                    interaction, everyday routines, and
                                    meaningful activities. They work
                                    collaboratively with families and the
                                    child&rsquo;s service team to support goals
                                    related to development, participation,
                                    confidence, and independence.
                                </p>
                                <p className="leading-relaxed text-black">
                                    Our Behavioural &amp; Developmental Aides
                                    work collaboratively with families and the
                                    child&rsquo;s service team to support
                                    consistency across everyday routines and
                                    environments. Through individualized
                                    strategies and ongoing communication, they
                                    help children build functional skills,
                                    positive behaviours, confidence, and greater
                                    independence.
                                </p>

                                <p className="leading-relaxed text-black">
                                    At Creative Abilities Therapy Services, we
                                    value strong, collaborative relationships
                                    with families. We work alongside parents and
                                    caregivers to support progress, celebrate
                                    meaningful milestones, and help children
                                    build skills that support their growth,
                                    participation, and independence.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

About.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            show_ready: true,
            show_contact: false,
            title: "Let's Work Together",
            desc: "Connect with our team to learn more about our services and how we can support your child's strengths, needs, goals, and everyday participation.",
            is_career: false,
        }}
    >
        {page}
    </PublicLayout>
);
