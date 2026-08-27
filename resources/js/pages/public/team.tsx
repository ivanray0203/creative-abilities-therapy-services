import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';

import FounderProfile from '@/components/founder-profile';
import LeaderProfile from '@/components/leader-profile';
import PublicLayout from '@/layouts/public-layout';
import { Leaders } from '@/lib/content/team';

export default function Team() {
    return (
        <>
            <Head title="Our Team" />

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
                                Meet Our Leaders
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

            {/*
             * The founder's card leads with her photo on the left, so the
             * portraits alternate from there: first leader right, next left.
             */}
            {Leaders.map((leader, index) => (
                <LeaderProfile
                    key={leader.id}
                    leader={leader}
                    imageOnRight={index % 2 === 0}
                />
            ))}
        </>
    );
}

Team.layout = (page: React.ReactNode) => (
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
