import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import PublicLayout from '@/layouts/public-layout';
import { TeamsData } from '@/lib/content/team';

export default function Team() {
    return (
        <>
            <Head title="Our Team" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-br from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center py-40">
                    <div className="rounded-sm bg-secondary-orange/10 p-3">
                        <p className="flex flex-row gap-3 text-primary-orange">
                            <Star />{' '}
                            <span className="text-primary">Meet Our Team</span>
                        </p>
                    </div>

                    <p className="m-5 text-primary">
                        Passionate Professionals Dedicated to Your Child's
                        Success
                    </p>
                    <p className="px-[20%] text-center text-xl leading-loose">
                        Our multidisciplinary team brings together expertise
                        from occupational therapy, speech-language pathology,
                        physiotherapy, psychology, behavioral consulting, and
                        child development. We work collaboratively to provide
                        comprehensive, compassionate care for every child and
                        family we serve.
                    </p>
                </div>
            </section>

            {/* Team */}
            <div className="mx-20 my-10 grid grid-cols-1 gap-10 md:grid-cols-3">
                {TeamsData.map((t) => (
                    <div
                        key={t.id}
                        className="flex flex-col overflow-hidden rounded-lg bg-white shadow-sm hover:shadow-lg"
                    >
                        {/* Photo as background with overlay text */}
                        <div
                            className="flex h-[500px] flex-col justify-end bg-cover bg-center p-5"
                            style={{
                                backgroundImage: t.photo
                                    ? `url(${t.photo})`
                                    : `url('/placeholder.svg')`,
                            }}
                        >
                            <p className="text-lg font-semibold text-white">
                                {t.title} {t.first_name} {t.last_name}
                            </p>
                            <p className="text-sm text-white">{t.position}</p>
                            <p className="text-sm text-white">{t.department}</p>
                        </div>

                        {/* Description */}
                        <div className="flex flex-grow flex-col p-5">
                            <p className="text-gray-700">{t.description}</p>

                            {/* Credentials & Certifications */}
                            <div className="mt-5">
                                <p className="mb-2 flex items-center gap-2 font-semibold text-primary">
                                    <LucideIcons.GraduationCap /> Credentials &
                                    Certifications
                                </p>

                                <div className="flex flex-col gap-1">
                                    {t.credentials.map((c, idx) => (
                                        <p
                                            key={idx}
                                            className="flex items-center gap-2 text-gray-600"
                                        >
                                            <LucideIcons.Medal className="h-4 w-4 text-primary" />{' '}
                                            {c}
                                        </p>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Contact */}
                        <div className="mx-4 mt-5 flex cursor-pointer items-center gap-2 border-t-2 p-5 pt-4 font-semibold text-primary transition hover:text-primary/80">
                            <a
                                href={`mailto:${t.email}`}
                                className="flex w-full items-center gap-2"
                            >
                                <LucideIcons.Mail className="h-5 w-5" />
                                Contact
                            </a>
                        </div>
                    </div>
                ))}
            </div>
        </>
    );
}

Team.layout = (page: React.ReactNode) => (
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
