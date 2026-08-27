import { Head, Link, usePage } from '@inertiajs/react';
import * as LucideIcons from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import {
    APPLICATION_TIMELINE,
    ApplicationProcess,
} from '@/lib/content/careers-config';
import type { Career, CareerSection } from '@/types/career';

function SectionHeading({ title }: { title: string }) {
    return (
        <h2 className="text-xl font-semibold text-primary md:text-2xl">
            {title}
        </h2>
    );
}

function BulletList({ items }: { items: string[] }) {
    return (
        <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {items.map((item) => (
                <li key={item} className="flex items-start gap-2">
                    <LucideIcons.CheckCircle2 className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                    <span className="text-sm leading-relaxed sm:text-base">
                        {item}
                    </span>
                </li>
            ))}
        </ul>
    );
}

/** Prose, an optional lead-in, a bullet list, and a closing line. */
function ListSection({ section }: { section: CareerSection }) {
    return (
        <section className="space-y-4">
            {section.title && <SectionHeading title={section.title} />}
            {section.intro && (
                <p className="leading-relaxed">{section.intro}</p>
            )}
            {section.paragraphs?.map((paragraph) => (
                <p key={paragraph} className="leading-relaxed">
                    {paragraph}
                </p>
            ))}
            {section.lead_in && (
                <p className="font-medium">{section.lead_in}</p>
            )}
            {section.items.length > 0 && <BulletList items={section.items} />}
            {section.closing && (
                <p className="leading-relaxed">{section.closing}</p>
            )}
        </section>
    );
}

export default function CareerDetail({ career }: { career: Career }) {
    const detail = career.detail;
    const applyHref = `/careers/apply/${career.id}?position=${encodeURIComponent(career.position)}`;

    return (
        <>
            <Head title={career.position} />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-br from-primary-orange/10 to-transparent py-16 sm:py-20"
            >
                <div className="container mx-auto">
                    <Link
                        href="/careers"
                        className="mb-8 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-normal text-primary shadow-lg"
                    >
                        <LucideIcons.ArrowLeft className="h-4 w-4" /> Back to
                        Careers
                    </Link>

                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-2 lg:gap-12">
                        <div className="flex flex-col space-y-5">
                            <p className="text-2xl font-semibold text-primary sm:text-3xl">
                                {career.position}
                            </p>
                            <p className="text-lg font-medium text-charcoal-gray">
                                Contract Opportunity
                            </p>

                            <div className="flex flex-col gap-3">
                                <p className="flex items-start gap-2">
                                    <LucideIcons.MapPin className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                    <span>
                                        <span className="font-semibold">
                                            Location:
                                        </span>{' '}
                                        {career.location}
                                    </span>
                                </p>
                                <p className="flex items-start gap-2">
                                    <LucideIcons.Briefcase className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                    <span>
                                        <span className="font-semibold">
                                            Position Type:
                                        </span>{' '}
                                        {career.contract}
                                    </span>
                                </p>
                                <p className="flex items-start gap-2">
                                    <LucideIcons.DollarSign className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                    <span>
                                        <span className="font-semibold">
                                            Starting Rate:
                                        </span>{' '}
                                        {career.rate}
                                    </span>
                                </p>
                                <p className="flex items-start gap-2">
                                    <LucideIcons.Calendar className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                    <span>
                                        <span className="font-semibold">
                                            Hours:
                                        </span>{' '}
                                        {career.hours}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <Button asChild size="lg">
                                    <Link
                                        id="posting-apply-top"
                                        href={applyHref}
                                    >
                                        Apply Now
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <div className="relative w-full">
                            <div className="aspect-[4/3] w-full overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src="/images/800x600/photo-1709880754441-f254e7a7035c_1_cropped.jpg"
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div className="container mx-auto space-y-14 py-16">
                {/* The pitch */}
                <section className="space-y-4">
                    <SectionHeading title="Join Creative Abilities Therapy Services" />
                    <p className="leading-relaxed">
                        {career.about_description}
                    </p>
                    {detail?.intro?.map((paragraph) => (
                        <p key={paragraph} className="leading-relaxed">
                            {paragraph}
                        </p>
                    ))}
                </section>

                {/* About the role */}
                {detail?.role_summary && (
                    <section className="space-y-4">
                        <SectionHeading title="About the Role" />
                        <p className="leading-relaxed">{detail.role_summary}</p>
                        {career.skills.length > 0 && (
                            <>
                                <p className="font-medium">
                                    Depending on the child and service
                                    arrangement, areas of support may include:
                                </p>
                                <BulletList items={career.skills} />
                            </>
                        )}
                    </section>
                )}

                {/* Responsibilities */}
                {career.responsibilities.length > 0 && (
                    <section className="space-y-4">
                        <SectionHeading title="Key Responsibilities" />
                        {detail?.responsibilities_lead_in && (
                            <p className="font-medium">
                                {detail.responsibilities_lead_in}
                            </p>
                        )}
                        <BulletList items={career.responsibilities} />
                    </section>
                )}

                {/* Collaboration */}
                {detail?.collaboration && (
                    <ListSection section={detail.collaboration} />
                )}

                {/* Posting-specific sections */}
                {detail?.extras?.map((extra) => (
                    <ListSection key={extra.title} section={extra} />
                ))}

                {/* Qualifications */}
                {career.qualifications.length > 0 && (
                    <section className="space-y-4">
                        <SectionHeading title="Qualifications" />
                        <p className="font-medium">
                            {detail?.qualifications_lead_in ??
                                'Applicants should have:'}
                        </p>
                        <BulletList items={career.qualifications} />
                        {detail?.qualifications_note && (
                            <p className="leading-relaxed">
                                {detail.qualifications_note}
                            </p>
                        )}
                    </section>
                )}

                {/* What we offer */}
                {detail?.offers && detail.offers.length > 0 && (
                    <section className="space-y-4">
                        <SectionHeading title="What We Offer" />
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {detail.offers.map((offer) => (
                                <Card
                                    key={offer.title}
                                    className="border-secondary-orange/20 bg-white"
                                >
                                    <CardContent className="space-y-2 p-6">
                                        <p className="font-semibold text-primary">
                                            {offer.title}
                                        </p>
                                        <p className="text-sm leading-relaxed">
                                            {offer.description}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>
                )}

                {/* FSCD participation */}
                {detail?.fscd && <ListSection section={detail.fscd} />}

                {/* Contractor terms */}
                {detail?.contractor && (
                    <section className="space-y-4">
                        <SectionHeading title={detail.contractor.title} />
                        {detail.contractor.paragraphs.map((paragraph) => (
                            <p key={paragraph} className="leading-relaxed">
                                {paragraph}
                            </p>
                        ))}
                    </section>
                )}

                {/* Application process */}
                <section className="space-y-4">
                    <SectionHeading title="Application Process" />
                    <ol className="space-y-4">
                        {ApplicationProcess.map((step) => (
                            <li
                                key={step.id}
                                className="flex gap-4 rounded-xl border border-secondary-orange/20 bg-white p-5"
                            >
                                <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">
                                    {step.id}
                                </span>
                                <div className="space-y-1">
                                    <p className="font-semibold text-primary">
                                        Step {step.id} &mdash; {step.title}
                                    </p>
                                    <p className="text-sm leading-relaxed">
                                        {step.description}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ol>

                    <div className="space-y-2 rounded-sm border border-primary/40 bg-secondary-orange/5 p-6 text-center text-sm md:text-base">
                        <p className="font-semibold text-primary">
                            {APPLICATION_TIMELINE.label}
                        </p>
                        <p className="text-muted-foreground">
                            {APPLICATION_TIMELINE.note}
                        </p>
                    </div>
                </section>

                {/* Closing */}
                <section className="space-y-4">
                    <SectionHeading
                        title={
                            detail?.closing_title ?? 'Ready to Join Our Team?'
                        }
                    />
                    {detail?.closing && (
                        <p className="leading-relaxed">{detail.closing}</p>
                    )}
                    <div className="flex flex-col gap-4 sm:flex-row">
                        <Button asChild>
                            <Link id="posting-apply-bottom" href={applyHref}>
                                Apply Now
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="border-primary text-primary"
                        >
                            <Link
                                href="/careers"
                                onClick={() => window.scroll(0, 0)}
                            >
                                View All Open Positions
                            </Link>
                        </Button>
                    </div>
                </section>
            </div>
        </>
    );
}

/**
 * Reads the posting through `usePage` rather than off the page element:
 * during a client-side visit the element's `props` are not populated yet, so
 * reaching into them threw as soon as the careers list started linking here.
 */
function CareerDetailLayout({ children }: { children: React.ReactNode }) {
    const { career } = usePage<{ career: Career }>().props;

    return (
        <PublicLayout
            footer={{
                is_career: true,
                show_ready: true,
                show_contact: false,
                title: 'Ready to Apply?',
                desc: `Complete our application form to apply for the ${career.position} position. We'll review your submission and be in touch within 1-2 business days.`,
            }}
        >
            {children}
        </PublicLayout>
    );
}

CareerDetail.layout = (page: React.ReactNode) => (
    <CareerDetailLayout>{page}</CareerDetailLayout>
);
