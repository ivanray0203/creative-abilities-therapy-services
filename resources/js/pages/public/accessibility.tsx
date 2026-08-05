import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Ear, Heart, Users2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';

const FEATURES = [
    {
        title: 'Screen Reader Compatible',
        desc: 'Our website is optimized for screen readers with proper semantic HTML, ARIA labels, and descriptive alt text for all images.',
    },
    {
        title: 'Keyboard Navigation',
        desc: 'All interactive elements can be accessed and operated using only a keyboard, with clear focus indicators.',
    },
    {
        title: 'Clear Visual Design',
        desc: 'High contrast ratios, readable font sizes, and clear visual hierarchy ensure content is easy to read for users with visual impairments.',
    },
    {
        title: 'Responsive & Mobile-Friendly',
        desc: "Our website adapts to different screen sizes and devices, ensuring accessibility whether you're on desktop, tablet, or mobile.",
    },
    {
        title: 'Plain Language',
        desc: 'We use clear, concise language and avoid jargon to ensure our content is understandable for all visitors.',
    },
    {
        title: 'Consistent Navigation',
        desc: 'Our navigation is predictable and consistent across all pages, making it easy for users to find information.',
    },
];

const DISABILITY_TYPES = [
    {
        title: 'Visual Disabilities',
        desc: 'Including difficulty using a mouse or keyboard',
    },
    {
        title: 'Hearing Disabilities',
        desc: 'Including deafness and hearing loss',
    },
    {
        title: 'Motor Disabilities',
        desc: 'Including difficulty using a mouse or keyboar',
    },
    {
        title: 'Cognitive Disabilities',
        desc: 'Including learning disabilities and neurodivergence',
    },
];

const ASSISTIVE_TECH = [
    {
        label: 'Screen readers',
        desc: 'and other assistive reading technologies',
    },
    {
        label: 'Keyboard navigation',
        desc: 'Keyboard navigation tools and alternative input devices',
    },
    {
        label: 'Screen magnification',
        desc: 'Screen magnification software and browser zoom functionality',
    },
    {
        label: 'Speech recognition',
        desc: 'Speech recognition software and voice control tools',
    },
    { label: 'Browser text-to-speech', desc: 'and reading mode features' },
];

const ONGOING_EFFORTS = [
    {
        label: 'Regular Testing: ',
        desc: 'We conduct regular accessibility audits and testing with assistive technologies to identify and fix issues.',
    },
    {
        label: 'Team Training ',
        desc: 'Our team receives ongoing training on accessibility best practices and inclusive design principles.',
    },
    {
        label: 'User Feedback: ',
        desc: 'We actively seek and incorporate feedback from users with disabilities to improve our website.',
    },
    {
        label: 'Standards Updates: ',
        desc: 'We monitor changes to accessibility standards and update our practices accordingly.',
    },
];

export default function Accessibility() {
    return (
        <>
            <Head title="Accessibility Statement" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <Button
                    asChild
                    variant="outline"
                    className="m-5 rounded-[5px] border border-primary text-primary md:mx-20 md:mt-20"
                >
                    <Link href="/">
                        <ArrowLeft /> Back to Home
                    </Link>
                </Button>

                <div className="rounded-md p-5 shadow-2xl md:m-20 md:p-10">
                    <div className="flex flex-row items-center gap-2 border-b pb-10">
                        <div className="rounded-sm bg-primary p-5 text-white">
                            <Heart />
                        </div>

                        <div>
                            <p className="text-2xl text-primary">
                                Accessibility Statement
                            </p>
                            <p className="text-sm text-charcoal-gray">
                                Last Updated: November 14, 2025
                            </p>
                        </div>
                    </div>

                    <div className="max-h-[80%] overflow-auto scroll-smooth">
                        {/* Commitment */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Our Commitment to Accessibility
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                At Creative Abilities Therapy Services, we are
                                deeply committed to ensuring digital
                                accessibility for people of all abilities. As a
                                therapy provider serving children with diverse
                                needs and their families, we understand the
                                critical importance of creating an inclusive
                                online experience for everyone.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We continuously work to improve the
                                accessibility and usability of our website to
                                ensure that all visitors, regardless of ability,
                                can access our information and services with
                                ease and dignity.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Our goal is to meet or exceed the Web Content
                                Accessibility Guidelines (WCAG) 2.1 Level AA
                                standards, recognized internationally as the
                                benchmark for web accessibility.
                            </p>
                        </div>

                        {/* Accessibility Features */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Accessibility Features
                            </p>

                            <div className="grid grid-cols-1 gap-5 md:m-10 md:grid-cols-2">
                                {FEATURES.map((f) => (
                                    <div
                                        key={f.title}
                                        className="flex gap-3 rounded-sm border-2 border-secondary-orange/10 p-5 shadow-lg hover:shadow-2xl"
                                    >
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary p-3 text-white">
                                            <Ear className="h-10 w-10" />
                                        </div>

                                        <div>
                                            <p className="font-semibold">
                                                {f.title}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {f.desc}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Web Accessibility Standards */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Web Accessibility Standards
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Our website strives to conform to the Web
                                Content Accessibility Guidelines (WCAG) 2.1
                                Level AA. These guidelines explain how to make
                                web content more accessible for people with
                                disabilities, including:
                            </p>

                            <div className="grid grid-cols-1 md:m-10 md:grid-cols-2">
                                {DISABILITY_TYPES.map((d) => (
                                    <div
                                        key={d.title}
                                        className="flex gap-3 p-5 hover:shadow-2xl"
                                    >
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full text-primary">
                                            <CheckCircle2 className="h-10 w-10" />
                                        </div>

                                        <div>
                                            <p className="font-semibold">
                                                {d.title}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {d.desc}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="rounded-sm bg-secondary-orange/5 p-5">
                                <p className="text-lg text-charcoal-gray">
                                    WCAG 2.1 Level AA Criteria Include:
                                </p>

                                <ul className="ml-4 list-inside list-disc text-charcoal-gray marker:text-primary">
                                    <li className="my-2">
                                        Text alternatives for non-text content
                                    </li>
                                    <li className="my-2">
                                        Sufficient color contrast between text
                                        and background
                                    </li>
                                    <li className="my-2">
                                        Keyboard accessibility for all
                                        functionality
                                    </li>
                                    <li className="my-2">
                                        Content that doesn't rely solely on
                                        sensory characteristics
                                    </li>
                                    <li className="my-2">
                                        Clear and consistent navigation and
                                        identification
                                    </li>
                                    <li className="my-2">
                                        Help users avoid and correct mistakes
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* Assistive Technology Support */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Assistive Technology Support
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Our website is built using standard web
                                technologies and follows accessibility best
                                practices to support common assistive
                                technologies. We design our website to work
                                with:
                            </p>

                            <div className="mt-10 rounded-sm bg-secondary-orange/5 p-5">
                                <ul className="ml-4 list-none text-charcoal-gray marker:text-primary">
                                    {ASSISTIVE_TECH.map((a) => (
                                        <li
                                            key={a.label}
                                            className="my-2 flex flex-row gap-2"
                                        >
                                            <CheckCircle2 className="text-primary" />{' '}
                                            <span className="font-bold">
                                                {' '}
                                                {a.label}
                                            </span>{' '}
                                            {a.desc}
                                        </li>
                                    ))}
                                </ul>

                                <p className="mt-3 text-sm text-charcoal-gray">
                                    We recommend using the latest versions of
                                    assistive technologies for the best
                                    experience. If you experience any issues
                                    with your assistive technology, please
                                    contact us so we can assist you and improve
                                    our website.
                                </p>
                            </div>
                        </div>

                        {/* Browser Compatibility */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Browser Compatibility
                            </p>

                            <div>
                                <p className="mt-3 leading-relaxed text-muted-foreground">
                                    Our website is tested and optimized for the
                                    latest versions of the following browsers:
                                </p>
                            </div>

                            <div className="m-10 grid grid-cols-1 gap-5 md:grid-cols-4">
                                <div className="flex items-center justify-center rounded-sm bg-secondary-orange/10 p-5">
                                    Google Chrome
                                </div>
                                <div className="flex items-center justify-center rounded-sm bg-secondary-orange/10 p-5">
                                    Microsoft Edge
                                </div>
                                <div className="flex items-center justify-center rounded-sm bg-secondary-orange/10 p-5">
                                    Safari
                                </div>
                                <div className="flex items-center justify-center rounded-sm bg-secondary-orange/10 p-5">
                                    Mozilla FireFox
                                </div>
                            </div>

                            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                For the best experience, we recommend using the
                                latest version of your preferred browser.
                            </p>
                        </div>

                        {/* Our Ongoing Efforts */}
                        <div className="border-b pb-3 text-charcoal-gray">
                            <p className="mt-5 text-xl text-primary">
                                Our Ongoing Efforts
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Accessibility is an ongoing commitment, not a
                                one-time achievement. We continuously work to:
                            </p>

                            {ONGOING_EFFORTS.map((e) => (
                                <div
                                    key={e.label}
                                    className="mt-3 flex flex-row gap-3"
                                >
                                    <div className="text-primary">
                                        <Users2 />
                                    </div>

                                    <div>
                                        <p className="text-charcoal-gray">
                                            <span className="font-bold">
                                                {e.label}
                                            </span>{' '}
                                            {e.desc}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Known Limitations */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Known Limitations
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Despite our best efforts, there may be some
                                limitations to the accessibility of our website.
                                We are aware of the following areas where we
                                continue to make improvements:
                            </p>

                            <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                <li className="my-2">
                                    Text alternatives for non-text content
                                </li>
                                <li className="my-2">
                                    Some third-party embedded content may not be
                                    fully accessible
                                </li>
                                <li className="my-2">
                                    Older PDF documents may not meet current
                                    accessibility standards (we are working to
                                    remediate these)
                                </li>
                                <li className="my-2">
                                    Some complex interactive features may
                                    require additional assistive technology
                                    support
                                </li>
                            </ul>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We are actively working to address these
                                limitations and appreciate your patience as we
                                continue to improve.
                            </p>
                        </div>

                        {/* Feedback & Assistance */}
                        <div className="border-b pb-3">
                            <div className="mt-10 rounded-sm bg-secondary-orange/5 p-5">
                                <p className="text-lg text-primary">
                                    Feedback & Assistance
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We welcome your feedback on the
                                    accessibility of our website. If you
                                    encounter any accessibility barriers or have
                                    suggestions for improvement, please contact
                                    us:
                                </p>

                                <div className="mt-5 flex flex-row gap-5">
                                    <div className="flex h-10 w-10 items-center justify-center rounded bg-white p-2">
                                        <Users2 className="text-center text-primary" />
                                    </div>

                                    <div>
                                        <p className="font-bold">
                                            Website Accessibility Support
                                        </p>
                                        <p>EasyTech Innovations</p>
                                        <p className="text-sm text-muted-foreground">
                                            On behalf of Creative Abilities
                                            Therapy Services
                                        </p>

                                        <p className="mt-3 text-sm">
                                            <span className="font-bold">
                                                Email:{' '}
                                            </span>
                                            support@easytechinnovations.ca{' '}
                                        </p>
                                        <p className="text-sm">
                                            <span className="font-bold">
                                                Phone:{' '}
                                            </span>
                                            (403) 555-0100{' '}
                                        </p>

                                        <p className="text-sm text-muted-foreground">
                                            We aim to respond to accessibility
                                            feedback within 1 business days.
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5 rounded-sm border-l-4 border-primary bg-white p-4 py-8">
                                    <p>
                                        <span className="font-bold">
                                            Need immediate assistance?{' '}
                                        </span>{' '}
                                        If you need help accessing any content
                                        or services on our website, please call
                                        us at (403) 555-0100 and we'll be happy
                                        to assist you in an alternative format.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Third-Party Content */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Third-Party Content
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Our website may contain links to third-party
                                websites or embedded content from third-party
                                services. We are not responsible for the
                                accessibility of third-party content. However,
                                we strive to work with partners who share our
                                commitment to accessibility.
                            </p>
                        </div>

                        {/* Updates to This Statement */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Updates to This Statementt
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                We regularly review and update this
                                Accessibility Statement to reflect our current
                                practices and any changes to accessibility
                                standards. This statement was last reviewed on
                                November 17, 2025.
                            </p>
                        </div>

                        <div className="mt-10 border-b pb-3">
                            <div className="rounded-sm bg-secondary-orange/5 p-5">
                                <p className="my-5 text-lg text-primary">
                                    Formal Complaints Process
                                </p>

                                <p>
                                    If you are not satisfied with our response
                                    to your accessibility concerns, you may file
                                    a formal complaint. We take all complaints
                                    seriously and will investigate thoroughly.
                                </p>

                                <p className="mt-4 text-sm text-muted-foreground">
                                    In Alberta, you also have the right to file
                                    a complaint with the{' '}
                                    <a
                                        href="https://albertahumanrights.ab.ca/"
                                        className="text-primary"
                                    >
                                        Alberta Human Rights Commission
                                    </a>{' '}
                                    if you believe you have experienced
                                    discrimination based on disability.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

Accessibility.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: false,
            title: 'Ready to Access FSCD Services?',
            desc: 'If your family is eligible for FSCD support and you are seeking specialized services for your child, contact Creative Abilities Therapy Services today. Our team will help you navigate the FSCD process, from application to service delivery, ensuring your child receives the best possible care.',
        }}
    >
        {page}
    </PublicLayout>
);
