import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import FounderProfile from '@/components/founder-profile';
import PublicLayout from '@/layouts/public-layout';
import { Values } from '@/lib/content/team';

export default function Founder() {
    return (
        <>
            <Head title="Meet Our Founder" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-br from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center px-4 py-20 md:px-0 md:py-40">
                    <div className="rounded-sm bg-secondary-orange/10 p-3">
                        <p className="flex flex-row gap-3 text-primary-orange">
                            <Star />{' '}
                            <span className="text-primary">
                                Meet Our Founder
                            </span>
                        </p>
                    </div>

                    <p className="m-5 text-center text-primary">
                        Dedicated to Supporting Children &amp; Families
                    </p>
                    <p className="max-w-4xl text-center text-base leading-loose md:px-[10%] md:text-xl">
                        Our Founder &amp; Director brings extensive experience
                        in pediatric Occupational Therapy, with specialized
                        training in sensory processing and feeding. Her
                        commitment to individualized, family-centred care helped
                        shape Creative Abilities Therapy Services into a
                        collaborative organization focused on supporting
                        children&rsquo;s strengths, development, participation,
                        and independence.
                    </p>
                </div>
            </section>

            <FounderProfile />

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
            show_contact: true,
            title: 'Work With Our Team',
            desc: "Our dedicated professionals are here to support your child's growth and development. Reach out today to learn more about our services.",
            is_career: true,
        }}
    >
        {page}
    </PublicLayout>
);
