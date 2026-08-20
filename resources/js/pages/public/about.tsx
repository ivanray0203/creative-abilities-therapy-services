import { Head } from '@inertiajs/react';
import { HandCoinsIcon, Heart, Sparkle, Target, Users } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import CaringHandsHeart from '@/components/icons/caring-hands-heart';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { Approach, Approach2, Philosophy } from '@/lib/content/about';

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
                            Celebrating Every{' '}
                            <span className="text-primary">Ability</span>
                        </p>

                        {/* Description */}
                        <p className="max-w-4xl text-center text-sm md:text-base lg:text-lg lg:leading-loose">
                            Creative Abilities Therapy Services is dedicated to
                            supporting children in reaching their fullest
                            potential through a range of individualized,
                            evidence-based therapies. We provide comprehensive
                            behavioural support, occupational therapy,
                            speech-language therapy, physiotherapy,
                            psychological services, and more — all in alignment
                            with Alberta's regulatory standards.
                        </p>

                        {/* Additional Info */}
                        <p className="mt-6 max-w-4xl text-center text-sm text-charcoal-gray md:text-base lg:text-lg lg:leading-loose">
                            Our compassionate, multidisciplinary team works
                            collaboratively to help children overcome
                            behavioural and developmental challenges in a warm
                            and supportive environment. We serve children in
                            their homes, schools, and social settings throughout
                            Calgary and surrounding areas.
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
                                    Empowering Every Child, Celebrating Every
                                    Ability
                                </h2>

                                <p className="text-sm leading-relaxed text-black sm:text-base">
                                    Our mission is to empower every child and
                                    celebrate every ability through innovative
                                    programs and activities. We believe that
                                    each child has unique strengths, and our
                                    goal is to help them build the confidence
                                    and skills needed to thrive.
                                </p>

                                <p className="text-sm leading-relaxed text-black sm:text-base">
                                    Whether it's developing communication,
                                    emotional regulation, positive behaviour, or
                                    physical abilities, we focus on meaningful,
                                    lasting progress. Through a family-centered
                                    approach and diverse therapeutic programs,
                                    we partner with parents and caregivers to
                                    ensure each child receives the support they
                                    need to succeed at home, in school, and in
                                    their community.
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
                            We Don't Just See Potential, We Nurture It
                        </p>

                        {/* Description */}
                        <p className="max-w-4xl px-2 text-center text-sm leading-relaxed md:px-0 md:text-base lg:text-lg">
                            By embracing each child's abilities and empowering
                            them to overcome obstacles, we help them thrive in
                            their environment and unlock new possibilities. Our
                            goal is to help your child participate meaningfully
                            in daily life, develop functional skills, and
                            enhance their overall well-being.
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
                                Building Essential Skills for Lifelong Success
                            </p>

                            <div className="mt-10 grid w-full grid-cols-2 gap-6 text-center sm:grid-cols-2 md:grid-cols-4">
                                <div className="flex flex-col items-center gap-2 text-white">
                                    <p className="text-4xl">💪</p>
                                    <p className="text-base">Confidence</p>
                                </div>

                                <div className="flex flex-col items-center gap-2 text-white">
                                    <p className="text-4xl">🎯</p>
                                    <p className="text-base">Skills</p>
                                </div>

                                <div className="flex flex-col items-center gap-2 text-white">
                                    <p className="text-4xl">🌟</p>
                                    <p className="text-base">Independence</p>
                                </div>

                                <div className="flex flex-col items-center gap-2 text-white">
                                    <p className="text-4xl">🤝</p>
                                    <p className="text-base">
                                        Meaningful Participation
                                    </p>
                                </div>
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
                                    Passionate Professionals
                                </h2>
                                <p className="leading-relaxed text-black">
                                    Our team is made up of experienced
                                    professionals who are passionate about
                                    helping children grow. We include
                                    Occupational Therapists, Speech-Language
                                    Pathologists, Physiotherapists,
                                    Psychologists, Behavioural Consultants, and
                                    Child Development Facilitators. Together, we
                                    offer culturally sensitive, inclusive care
                                    that respects each family's background and
                                    goals. By working together, we help children
                                    build essential life skills, enhance their
                                    well-being, and fully participate in daily
                                    life.
                                </p>
                                <p className="leading-relaxed text-black">
                                    Together, we offer culturally sensitive,
                                    inclusive care that respects each family's
                                    background and goals. By working together,
                                    we help children build essential life
                                    skills, enhance their well-being, and fully
                                    participate in daily life.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Meet Our Founder */}
                <section
                    id="founder"
                    className="bg-gradient-to-br from-secondary-orange/10 to-transparent py-20"
                >
                    <div className="container mx-auto">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            {/* Text Content */}
                            <div className="space-y-6">
                                <div className="inline-block rounded-sm bg-secondary-orange/20 p-3 text-primary">
                                    <p className="flex items-center gap-3">
                                        <Sparkle />{' '}
                                        <span>Meet Our Founder</span>
                                    </p>
                                </div>

                                <p className="text-xl font-semibold text-primary md:text-2xl">
                                    Passionate Professionals Dedicated to Your
                                    Child's Success
                                </p>

                                <p className="text-lg leading-relaxed text-foreground md:text-xl">
                                    Our founder brings expertise in occupational
                                    therapy with specialized training in sensory
                                    processing and feeding therapy. With a deep
                                    commitment to supporting families,
                                    especially newcomers to Canada, we provide
                                    comprehensive, compassionate care for every
                                    child and family we serve.
                                </p>
                            </div>

                            {/* Image */}
                            <div className="relative w-full">
                                <div className="aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-primary/5 to-primary/10 shadow-2xl">
                                    <img
                                        src="/images/FamilyPicture.png"
                                        alt="Founder"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="mt-10 px-4 md:px-10">
                    <div className="mb-10 overflow-hidden rounded-[10px] shadow-lg hover:shadow-2xl">
                        <div className="flex flex-col lg:flex-row">
                            {/* Image */}
                            <div className="relative h-64 w-full flex-shrink-0 lg:h-auto lg:w-1/2">
                                <img
                                    src="/images/Ann_Founder.png"
                                    alt="Founder"
                                    className="h-full w-full object-cover"
                                />
                            </div>

                            {/* Info */}
                            <div className="flex-1 bg-white p-4 lg:p-10">
                                {/* Title */}
                                <p className="text-lg">Mary Ann Lerit B.Sc</p>

                                <p className="pt-2 text-xl">
                                    Registered Occupational Therapist
                                </p>
                                <p className="pt-3">
                                    Sensory Processing Therapy & Sequential Oral
                                    Sensory Feeding
                                </p>

                                {/* Description */}
                                <p className="pt-2 lg:pt-5">
                                    Mary Ann (aka Ann) holds a Bachelor of
                                    Science in Occupational Therapy from St.
                                    Jude College in Manila, Philippines. Since
                                    2016, she has been a Registered Occupational
                                    Therapist in Alberta through the Alberta
                                    College of Occupational Therapists, and she
                                    is a member of the Canadian Association of
                                    Occupational Therapists.
                                </p>

                                <p className="pt-2 lg:pt-5">
                                    Ann brings extensive professional experience
                                    in providing pediatric support to families
                                    of children with disabilities. Her work
                                    spans elementary schools, nonprofit
                                    agencies, and hospitals both in Canada and
                                    internationally. She has specialized
                                    training in Sensory Processing Therapy and
                                    Sequential Oral Sensory Feeding.
                                </p>

                                <p className="pt-2 lg:pt-5">
                                    As an immigrant to Canada, Ann is passionate
                                    about supporting immigrant families with
                                    children with disabilities. She was inspired
                                    by her community and her family to open an
                                    agency that focuses on holistic support for
                                    all. In her free time, she enjoys travelling
                                    across North America and hiking the Rocky
                                    Mountains with her son and husband.
                                </p>

                                {/* Credentials */}
                                <p className="flex flex-row gap-2 pt-10">
                                    <LucideIcons.GraduationCap /> Credentials &
                                    Certifications
                                </p>

                                <div className="mt-2 border-b pb-3">
                                    {[
                                        'Bachelor of Science in Occupational Therapy, St. Jude College, Manila, Philippines',
                                        'Registered Occupational Therapist with Alberta College of Occupational Therapists (since 2016)',
                                        'Member of the Canadian Association of Occupational Therapists',
                                        'Specialized Training in Sensory Processing Therapy',
                                        'Specialized Training in Sequential Oral Sensory Feeding',
                                    ].map((c) => (
                                        <p
                                            key={c}
                                            className="flex flex-row gap-2 pt-2 text-sm"
                                        >
                                            <LucideIcons.Medal className="h-4 w-4" />{' '}
                                            {c}
                                        </p>
                                    ))}
                                </div>

                                <div className="flex flex-row gap-3">
                                    <Button
                                        asChild
                                        className="mt-10 rounded bg-primary"
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
                                            <LucideIcons.Mail /> Contact Mary
                                            Ann
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                            At Creative Abilities Therapy Services, we
                            understand that parents play a crucial role in their
                            child's success. That's why we emphasize
                            collaboration and partnership with families to
                            develop customized intervention plans tailored to
                            each child's unique strengths and challenges.
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
                                        <LucideIcons.Cookie /> Child Development
                                        Facilitators
                                    </p>
                                </div>
                                <h2 className="text-base">
                                    Nurturing Safe Spaces for Growth
                                </h2>
                                <p className="leading-relaxed text-black">
                                    As Child Development Facilitators, we
                                    understand the importance of nurturing
                                    environments where children feel safe to
                                    explore, learn, and grow. We support
                                    development through play, social
                                    interaction, and connection—ensuring that
                                    each child feels supported and celebrated.
                                </p>
                                <p className="leading-relaxed text-black">
                                    We believe in a collaborative
                                    approach—parents are at the heart of a
                                    child's support system. Together, we empower
                                    your child to build essential life skills,
                                    foster positive behaviours, and lay the
                                    foundation for lifelong success. We focus on
                                    helping children participate meaningfully in
                                    daily life, develop functional skills, and
                                    enhance their overall well-being.
                                </p>

                                <p className="leading-relaxed text-black">
                                    At Creative Abilities Therapy Services, we
                                    don't just provide services—we build
                                    lifelong partnerships with families. We
                                    guide them through challenges, celebrate
                                    milestones, and work hand-in-hand to ensure
                                    that children grow, develop, and reach their
                                    fullest potential.
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
            desc: "Connect with our compassionate team today to learn how we can support your child's unique journey and help them reach their fullest potential.",
            is_career: false,
        }}
    >
        {page}
    </PublicLayout>
);
