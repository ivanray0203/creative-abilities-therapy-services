import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { Values } from '@/lib/content/team';

export default function Founder() {
    return (
        <>
            <Head title="Meet Our Founder" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-br from-secondary-orange/10 to-transparent py-20"
            >
                <div className="container mx-auto">
                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        {/* Text Content */}
                        <div className="space-y-6">
                            <div className="inline-block rounded-sm bg-secondary-orange/20 p-3 text-primary">
                                <p className="flex items-center gap-3">
                                    <Star /> <span>Meet Our Founder</span>
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
                                commitment to supporting families, especially
                                newcomers to Canada, we provide comprehensive,
                                compassionate care for every child and family we
                                serve.
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

            {/* Founder */}
            <section id="founder" className="mt-10 px-10">
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
                        <div className="flex-1 p-4 lg:p-10">
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
                                Mary Ann (aka Ann) holds a Bachelor of Science
                                in Occupational Therapy from St. Jude College in
                                Manila, Philippines. Since 2016, she has been a
                                Registered Occupational Therapist in Alberta
                                through the Alberta College of Occupational
                                Therapists, and she is a member of the Canadian
                                Association of Occupational Therapists.
                            </p>

                            <p className="pt-2 lg:pt-5">
                                Ann brings extensive professional experience in
                                providing pediatric support to families of
                                children with disabilities. Her work spans
                                elementary schools, nonprofit agencies, and
                                hospitals both in Canada and internationally.
                                She has specialized training in Sensory
                                Processing Therapy and Sequential Oral Sensory
                                Feeding.
                            </p>

                            <p className="pt-2 lg:pt-5">
                                As an immigrant to Canada, Ann is passionate
                                about supporting immigrant families with
                                children with disabilities. She was inspired by
                                her community and her family to open an agency
                                that focuses on holistic support for all. In her
                                free time, she enjoys travelling across North
                                America and hiking the Rocky Mountains with her
                                son and husband.
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
                                        <LucideIcons.Mail /> Contact Mary Ann
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="values"
                className="bg-gradient-to-tl from-secondary-orange/5 to-transparent"
            >
                <div className="flex flex-col items-center justify-center py-40">
                    <div className="rounded-sm bg-secondary-orange/10 p-3 text-primary">
                        <p className="flex flex-row gap-3">
                            <LucideIcons.Heart /> <span>Our Values</span>
                        </p>
                    </div>

                    <p className="m-5">What Drives Our Team</p>
                    <p className="px-[20%] text-center text-xl leading-loose">
                        Our founder brings expertise in occupational therapy
                        with specialized training in sensory processing and
                        feeding therapy. With a deep commitment to supporting
                        families, especially newcomers to Canada, we provide
                        comprehensive, compassionate care for every child and
                        family we serve.
                    </p>

                    <div className="mt-10 grid w-full max-w-6xl grid-cols-1 gap-6 px-2 md:grid-cols-3 md:gap-10 md:px-0">
                        {Values.map((p) => {
                            const IconComponent = LucideIcons[
                                p.icon as keyof typeof LucideIcons
                            ] as React.ElementType | undefined;

                            return (
                                <div
                                    key={p.id}
                                    className="flex flex-col items-center justify-center gap-5 rounded-sm bg-white p-6 shadow-lg md:p-10"
                                >
                                    {IconComponent ? (
                                        <div className="flex items-center justify-center rounded-full bg-primary p-5">
                                            <IconComponent className="h-5 w-5 text-white" />
                                        </div>
                                    ) : (
                                        <LucideIcons.Heart className="h-5 w-5" />
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
                </div>
            </section>
        </>
    );
}

Founder.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            show_ready: true,
            show_contact: false,
            title: 'Work With Our Team',
            desc: "Our dedicated professionals are here to support your child's growth and development. Reach out today to learn more about our services.",
            is_career: true,
        }}
    >
        {page}
    </PublicLayout>
);
