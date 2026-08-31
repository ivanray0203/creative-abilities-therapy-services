import { Head, Link } from '@inertiajs/react';
import * as LucideIcons from 'lucide-react';

import ScrollToContactButton from '@/components/scroll-to-contact-button';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { findService } from '@/lib/content/service-list';
import type { TitledPoint } from '@/lib/content/service-list';

/** Section heading shared by every block on the page. */
function SectionHeading({
    title,
    intro,
}: {
    title: string;
    intro?: string | null;
}) {
    return (
        <div className="space-y-3">
            <h2 className="text-xl font-semibold text-primary md:text-2xl">
                {title}
            </h2>
            {intro && <p className="leading-relaxed">{intro}</p>}
        </div>
    );
}

/** Grid of titled points — the shape most detail sections take. */
function PointGrid({ points }: { points: TitledPoint[] }) {
    return (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {points.map((point) => (
                <Card
                    key={point.title}
                    className="border-secondary-orange/20 bg-white"
                >
                    <CardContent className="space-y-2 p-6">
                        <p className="font-semibold text-primary">
                            {point.title}
                        </p>
                        <p className="text-sm leading-relaxed">
                            {point.description}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function BulletList({ items }: { items: string[] }) {
    return (
        <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {items.map((item) => (
                <li key={item} className="flex items-start gap-2">
                    <LucideIcons.CheckCircle2 className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                    <span className="text-sm">{item}</span>
                </li>
            ))}
        </ul>
    );
}

export default function ServiceDetail({ code }: { code: string }) {
    const service = findService(code);

    if (!service) {
        return (
            <>
                <Head title="Service Not Found" />
                <div className="container mx-auto space-y-6 py-32 text-center">
                    <h1 className="text-2xl font-bold text-primary">
                        We couldn&rsquo;t find that service
                    </h1>
                    <p>
                        The service you were looking for isn&rsquo;t one we
                        list. Browse everything we offer instead.
                    </p>
                    <Button asChild>
                        <Link
                            href="/services"
                            onClick={() => window.scroll(0, 0)}
                        >
                            View All Services
                        </Link>
                    </Button>
                </div>
            </>
        );
    }

    const detail = service.detail;

    return (
        <>
            <Head title={service.name} />

            {/* Hero */}
            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent py-16"
            >
                <div className="container mx-auto">
                    <Button
                        asChild
                        variant="ghost"
                        className="mb-8 rounded-full bg-white px-4 text-sm font-normal text-primary shadow-sm"
                    >
                        <Link
                            href="/services"
                            onClick={() => window.scroll(0, 0)}
                        >
                            <LucideIcons.ArrowLeft /> All Services
                        </Link>
                    </Button>

                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        <div className="space-y-5">
                            <h1 className="text-2xl font-bold text-primary md:text-3xl">
                                {service.name}
                            </h1>
                            <p className="text-lg font-medium text-charcoal-gray md:text-xl">
                                {service.tagline}
                            </p>
                            <p className="leading-relaxed">
                                {service.description}
                            </p>
                        </div>

                        <div className="relative w-full">
                            <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src={service.photo}
                                    alt={service.name}
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div className="container mx-auto space-y-16 py-16">
                {/* How this service helps */}
                {detail.how_we_help?.items && (
                    <section id="how-we-help" className="space-y-6">
                        <SectionHeading
                            title={
                                detail.how_we_help.title ??
                                `How ${service.name} Can Help`
                            }
                            intro={detail.how_we_help.intro}
                        />
                        <PointGrid points={detail.how_we_help.items} />
                    </section>
                )}

                {/* What to expect */}
                {detail.what_to_expect?.steps && (
                    <section id="what-to-expect" className="space-y-6">
                        <SectionHeading
                            title="What to Expect"
                            intro={detail.what_to_expect.intro}
                        />
                        <ol className="space-y-4">
                            {detail.what_to_expect.steps.map((step, index) => (
                                <li
                                    key={step.title}
                                    className="flex gap-4 rounded-xl border border-secondary-orange/20 bg-white p-5"
                                >
                                    <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">
                                        {index + 1}
                                    </span>
                                    <div className="space-y-1">
                                        <p className="font-semibold text-primary">
                                            {step.title}
                                        </p>
                                        <p className="text-sm leading-relaxed">
                                            {step.description}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </section>
                )}

                {/* Family-centred care */}
                {detail.family_centred?.items && (
                    <section id="family-centred" className="space-y-6">
                        <SectionHeading
                            title={
                                detail.family_centred.title ??
                                'Family-Centred Care'
                            }
                            intro={detail.family_centred.intro}
                        />
                        <PointGrid points={detail.family_centred.items} />
                    </section>
                )}

                {/* Who may benefit */}
                {detail.who_may_benefit && (
                    <section id="who-may-benefit" className="space-y-6">
                        <SectionHeading
                            title="Who May Benefit?"
                            intro={detail.who_may_benefit.intro}
                        />
                        {detail.who_may_benefit.lead_in && (
                            <p className="font-medium">
                                {detail.who_may_benefit.lead_in}
                            </p>
                        )}
                        <BulletList items={detail.who_may_benefit.items} />
                        {detail.who_may_benefit.closing && (
                            <p className="leading-relaxed">
                                {detail.who_may_benefit.closing}
                            </p>
                        )}
                    </section>
                )}

                {/* Our approach */}
                {detail.approach && (
                    <section id="approach" className="space-y-6">
                        <SectionHeading title="Our Approach" />
                        <PointGrid points={detail.approach} />
                    </section>
                )}

                {/* Where services are provided */}
                {detail.settings?.items && (
                    <section id="settings" className="space-y-6">
                        <SectionHeading
                            title={
                                detail.settings.title ??
                                'Where Services May Be Provided'
                            }
                            intro={detail.settings.intro}
                        />
                        <PointGrid points={detail.settings.items} />
                    </section>
                )}

                {/* Collaboration with the support team */}
                {detail.collaboration && (
                    <section id="collaboration" className="space-y-6">
                        <SectionHeading
                            title={
                                detail.collaboration.title ??
                                'Collaboration with Your Child’s Support Team'
                            }
                            intro={detail.collaboration.intro}
                        />
                        {detail.collaboration.lead_in && (
                            <p className="font-medium">
                                {detail.collaboration.lead_in}
                            </p>
                        )}
                        <BulletList items={detail.collaboration.items} />
                        {detail.collaboration.closing && (
                            <p className="leading-relaxed">
                                {detail.collaboration.closing}
                            </p>
                        )}
                    </section>
                )}

                {/* Service-specific sections (AAC, respite planning, ...) */}
                {detail.extras?.map((extra) => (
                    <section key={extra.title} className="space-y-6">
                        <SectionHeading title={extra.title ?? ''} />
                        {extra.paragraphs?.map((paragraph) => (
                            <p key={paragraph} className="leading-relaxed">
                                {paragraph}
                            </p>
                        ))}
                        {extra.lead_in && (
                            <p className="font-medium">{extra.lead_in}</p>
                        )}
                        {extra.items.length > 0 && (
                            <BulletList items={extra.items} />
                        )}
                        {extra.closing && (
                            <p className="leading-relaxed">{extra.closing}</p>
                        )}
                    </section>
                ))}

                {/* Funding */}
                {detail.funding && (
                    <section id="funding" className="space-y-6">
                        <SectionHeading
                            title="Funding &amp; Payment Options"
                            intro={detail.funding.intro}
                        />
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            {detail.funding.items.map((option) => (
                                <Card
                                    key={option.title}
                                    className="border-primary/20 bg-secondary-orange/5"
                                >
                                    <CardContent className="space-y-3 p-6">
                                        <p className="font-semibold text-primary">
                                            {option.title}
                                        </p>
                                        {option.paragraphs.map((paragraph) => (
                                            <p
                                                key={paragraph}
                                                className="text-sm leading-relaxed"
                                            >
                                                {paragraph}
                                            </p>
                                        ))}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>
                )}

                {/* Closing call to action */}
                {detail.cta && (
                    <section id="get-started" className="space-y-6">
                        <SectionHeading title={detail.cta.title} />
                        {detail.cta.paragraphs.map((paragraph) => (
                            <p key={paragraph} className="leading-relaxed">
                                {paragraph}
                            </p>
                        ))}
                        <div className="flex flex-col gap-4 sm:flex-row">
                            <Button asChild>
                                <Link
                                    href="/intake/apply"
                                    onClick={() => window.scroll(0, 0)}
                                >
                                    Start Intake
                                </Link>
                            </Button>
                            <ScrollToContactButton className="border border-primary bg-white text-primary hover:bg-white">
                                Contact Us
                            </ScrollToContactButton>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

ServiceDetail.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            show_ready: true,
            show_contact: true,
            title: "Let's Work Together",
            desc: "Connect with our team to learn more about our services and how we can support your child's strengths, needs, goals, and everyday participation.",
            is_career: false,
        }}
    >
        {page}
    </PublicLayout>
);
