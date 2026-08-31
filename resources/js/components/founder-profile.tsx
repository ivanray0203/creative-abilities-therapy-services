import * as LucideIcons from 'lucide-react';

import ScrollToContactButton from '@/components/scroll-to-contact-button';

const FOUNDER_PARAGRAPHS = [
    'Mary Ann (Ann) holds a Bachelor of Science in Occupational Therapy from St. Jude College in Manila, Philippines. She has been a Registered Occupational Therapist in Alberta since 2016 and is a member of the Canadian Association of Occupational Therapists. Her professional background reflects a strong commitment to providing compassionate, family-centred support to children and families.',
    'Mary Ann brings extensive experience supporting children and families across a variety of settings, including elementary schools, nonprofit organizations, and hospitals in Canada and internationally. Her pediatric practice is supported by specialized training in sensory processing and Sequential Oral Sensory (SOS) Feeding, helping her provide individualized care that reflects each child’s strengths, needs, and everyday participation.',
    'Mary Ann’s own experience as an immigrant to Canada, together with the support and inspiration of her family and community, helped shape her vision for Creative Abilities Therapy Services. She founded CATS to create a welcoming, collaborative, and family-centred organization where children are supported through individualized care that recognizes their strengths, abilities, and unique needs.',
    'Outside of her professional work, Mary Ann enjoys travelling across North America and spending time outdoors, especially hiking in the Rocky Mountains with her family.',
];

const FOUNDER_CREDENTIALS = [
    'Bachelor of Science in Occupational Therapy — St. Jude College, Manila, Philippines',
    'Registered Occupational Therapist — Alberta College of Occupational Therapists, since 2016',
    'Member — Canadian Association of Occupational Therapists',
    'Specialized Training — Sensory Processing',
    'Specialized Training — Sequential Oral Sensory (SOS) Feeding',
];

/**
 * The founder's profile card, shared by `/team` and `/team/founder`. Each of
 * those pages supplies its own surrounding heading, since the framing differs
 * (a leadership roster versus a founder page).
 */
export default function FounderProfile() {
    return (
        <section id="founder" className="mt-10 px-4 md:px-10">
            <div className="mb-10 overflow-hidden rounded-[10px] shadow-lg hover:shadow-2xl">
                <div className="flex flex-col lg:flex-row">
                    {/* Photo */}
                    <div className="relative h-64 w-full flex-shrink-0 lg:h-auto lg:w-1/2">
                        <img
                            src="/images/Ann_Founder.png"
                            alt="Mary Ann Lerit, Founder & Director"
                            className="h-full w-full object-cover"
                        />
                    </div>

                    {/* Info */}
                    <div className="flex-1 bg-white p-4 lg:p-10">
                        <p className="text-xl font-semibold text-primary">
                            Mary Ann Lerit, B.Sc. OT, OT Reg. (AB)
                        </p>

                        <p className="pt-2 text-lg">
                            Founder &amp; Director | Registered Occupational
                            Therapist
                        </p>

                        {FOUNDER_PARAGRAPHS.map((paragraph) => (
                            <p key={paragraph} className="pt-2 lg:pt-5">
                                {paragraph}
                            </p>
                        ))}

                        {/* Credentials */}
                        <p className="flex flex-row gap-2 pt-10">
                            <LucideIcons.GraduationCap /> Credentials &amp;
                            Certifications
                        </p>

                        <div className="mt-2 border-b pb-3">
                            {FOUNDER_CREDENTIALS.map((credential) => (
                                <p
                                    key={credential}
                                    className="flex flex-row gap-2 pt-2 text-sm"
                                >
                                    <LucideIcons.Medal className="h-4 w-4 flex-shrink-0" />{' '}
                                    {credential}
                                </p>
                            ))}
                        </div>

                        <div className="flex flex-row gap-3">
                            <ScrollToContactButton className="mt-10 rounded">
                                <LucideIcons.Mail /> Contact Mary Ann
                            </ScrollToContactButton>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
