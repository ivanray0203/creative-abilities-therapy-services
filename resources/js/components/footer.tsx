import { Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowUp,
    Clock,
    Heart,
    Mail,
    MapPin,
    MessageCircleIcon,
    Phone,
    Send,
    Sparkles,
} from 'lucide-react';

import { Button } from '@/components/ui/button';

/**
 * lucide-react (this project's installed version) no longer ships brand
 * icons (Facebook/Instagram/LinkedIn), so these are small inline SVGs
 * matching the reference's icon glyphs instead of adding a new dependency.
 */
function FacebookIcon(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z" />
        </svg>
    );
}

function InstagramIcon(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            {...props}
        >
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
        </svg>
    );
}

function LinkedinIcon(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.03-1.85-3.03-1.85 0-2.14 1.45-2.14 2.94v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.12 20.45H3.56V9h3.56v11.45Z" />
        </svg>
    );
}

export interface FooterProps {
    show_ready?: boolean;
    show_contact?: boolean;
    title?: string;
    desc?: string;
    is_career?: boolean;
}

/**
 * Ported 1:1 from cats-frontend/src/components/Footer.tsx. The contact form
 * is inlined here (rather than a separate ContactForm component) and posts
 * to the `public.contacts.store` route via Inertia's useForm, replacing the
 * reference's react-hook-form + contactAPI.create() call.
 */
export default function Footer({
    show_ready = true,
    show_contact = false,
    title = 'Start Your Journey Today',
    desc = "Reach out and let's talk about how we can support your child and family.",
    is_career = false,
}: FooterProps) {
    return (
        <div className="relative">
            <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 transform">
                <button
                    onClick={() =>
                        window.scrollTo({ top: 0, behavior: 'smooth' })
                    }
                    className="rounded-full bg-primary p-3 text-white shadow-lg transition hover:bg-primary/90"
                    aria-label="Scroll to top"
                >
                    <ArrowUp className="h-6 w-6" />
                </button>
            </div>

            {show_ready && (
                <section className="bg-primary py-20 text-white">
                    <div className="container mx-auto space-y-6 text-center">
                        <div className="inline-block rounded-full bg-white/20 px-3 py-1 text-sm font-medium">
                            <Sparkles className="mr-1 inline h-3 w-3" />
                            Start Your Journey with CATS
                        </div>
                        <h2 className="text-3xl font-bold">{title}</h2>
                        <p className="mx-auto max-w-2xl text-white/90">
                            {desc}
                        </p>

                        {!is_career ? (
                            <div className="flex justify-center gap-4">
                                <Button
                                    asChild
                                    size="lg"
                                    variant="ghost"
                                    className="rounded-[5px] border-white bg-white text-primary shadow-md hover:bg-white hover:text-primary"
                                >
                                    <Link href="/intake/apply">
                                        Start Intake
                                    </Link>
                                </Button>

                                <Button
                                    asChild
                                    variant="outline"
                                    size="lg"
                                    className="rounded-[5px] border-white bg-transparent text-white shadow-sm hover:bg-white hover:text-primary"
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
                                        Contact Us
                                    </a>
                                </Button>
                            </div>
                        ) : (
                            <div className="flex justify-center gap-4">
                                <Button
                                    asChild
                                    size="lg"
                                    variant="ghost"
                                    className="rounded-[5px] border-white bg-white text-primary shadow-md hover:bg-white hover:text-primary"
                                >
                                    <Link href="/careers/apply">Apply Now</Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    size="lg"
                                    className="rounded-[5px] border-white bg-transparent text-white shadow-sm hover:bg-white hover:text-primary"
                                >
                                    <Link href="/careers">
                                        View All Openings
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>
                </section>
            )}

            {show_contact && (
                <section
                    id="contact_us"
                    className="bg-secondary-orange/5 py-20"
                >
                    <div className="container mx-auto w-full max-w-3xl rounded-[10px] border-2 border-gray-200 bg-white p-6 shadow-lg sm:p-10">
                        <div className="space-y-6">
                            <div className="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                                <div className="flex-shrink-0 rounded-sm bg-secondary-orange p-3 text-white shadow-lg">
                                    <MessageCircleIcon className="h-6 w-6" />
                                </div>
                                <div>
                                    <h4 className="text-xl font-light">
                                        Have a Question?
                                    </h4>
                                    <p className="text-muted-foreground">
                                        Not sure where to start or which service
                                        may be right for your family? Send us a
                                        message and our team will be happy to
                                        help.
                                    </p>
                                </div>
                            </div>

                            <ContactForm />
                        </div>
                    </div>
                </section>
            )}

            <footer className="border-t bg-charcoal-gray py-8">
                <div className="container mx-auto">
                    <div className="flex flex-col justify-between gap-10 py-10 text-sm md:flex-row">
                        <div className="flex flex-1 flex-col gap-5">
                            <div className="flex flex-row gap-3">
                                <div className="flex items-center justify-center rounded bg-secondary/20 p-2">
                                    <Heart className="h-5 w-5 text-white" />
                                </div>
                                <div className="flex flex-col text-white">
                                    <p className="text-sm">
                                        Creative Abilities
                                    </p>
                                    <p className="text-xs">Therapy Services</p>
                                </div>
                            </div>

                            <p className="mt-5 text-white">
                                Creative Abilities Therapy Services provides
                                individualized, family-centred therapy and
                                developmental support to help children grow,
                                participate, and thrive.
                            </p>

                            <div className="mt-5 flex flex-row gap-3">
                                <a
                                    href="https://www.facebook.com/profile.php?id=61560217375273"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center justify-center rounded bg-secondary/20 p-2"
                                >
                                    <FacebookIcon className="h-5 w-5 text-white" />
                                </a>
                                <a
                                    href="https://www.instagram.com/creativeabilitiestherapy/#"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center justify-center rounded bg-secondary/20 p-2"
                                >
                                    <InstagramIcon className="h-5 w-5 text-white" />
                                </a>
                                <a
                                    href="https://www.linkedin.com/company/creative-abilities-therapy-services/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center justify-center rounded bg-secondary/20 p-2"
                                >
                                    <LinkedinIcon className="h-5 w-5 text-white" />
                                </a>
                            </div>
                        </div>

                        <div className="flex flex-1 flex-col items-start justify-start gap-4">
                            <div className="rounded-e border-l-4 border-l-white px-2">
                                <p className="text-white">Quick Links</p>
                            </div>

                            {[
                                { label: 'Home', href: '/' },
                                { label: 'About Us', href: '/about' },
                                { label: 'Services', href: '/services' },
                                { label: 'Our Team', href: '/team' },
                                { label: 'Careers', href: '/careers' },
                                { label: 'FAQ', href: '/faq' },
                            ].map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="group flex items-center text-white transition-colors hover:text-primary"
                                >
                                    <ArrowLeft className="hidden -translate-x-3 transform opacity-0 transition-all duration-300 group-hover:inline-block group-hover:translate-x-0 group-hover:opacity-100" />
                                    <span className="transform transition-transform duration-300 group-hover:translate-x-2">
                                        {link.label}
                                    </span>
                                </Link>
                            ))}
                        </div>

                        <div className="flex flex-1 flex-col items-start justify-start gap-4">
                            <div className="rounded-e border-l-4 border-l-white px-2">
                                <p className="text-white">Our Services</p>
                            </div>

                            {[
                                {
                                    label: 'Speech-Language Therapy',
                                    href: '/services#SLTS-202',
                                },
                                {
                                    label: 'Psychology Services',
                                    href: '/services#BCCPS-303',
                                },
                                {
                                    label: 'Counselling',
                                    href: '/services#BCCPS-303',
                                },
                                {
                                    label: 'Occupational Therapy',
                                    href: '/services#OTS-101',
                                },
                                {
                                    label: 'Physiotherapy',
                                    href: '/services#P-303',
                                },
                                {
                                    label: 'Behavioural Consulting',
                                    href: '/services#BTC-505',
                                },
                                {
                                    label: 'Behavioural & Developmental Aide',
                                    href: '/services#BDAS-303',
                                },
                                {
                                    label: 'Community & Respite Service',
                                    href: '/services#CRAS-404',
                                },
                            ].map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="text-white transition-colors hover:text-primary"
                                >
                                    {link.label}
                                </Link>
                            ))}
                            <Link
                                href="/intake/apply"
                                className="rounded-[4px] bg-primary px-3 py-1 text-white transition-colors hover:text-primary"
                            >
                                Start Intake
                            </Link>
                        </div>

                        <div className="flex flex-1 flex-col items-start justify-start gap-4">
                            <div className="rounded-e border-l-4 border-l-white px-2">
                                <p className="text-white">Get in Touch</p>
                            </div>

                            <a
                                href="https://www.google.com/maps/search/Calgary,+Airdrie,+Chestermere,+Strathmore,+Cochrane,+Okotoks"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex flex-row gap-2"
                            >
                                <div className="flex items-center justify-center rounded bg-secondary/20 p-2">
                                    <MapPin className="h-5 w-5 text-white" />
                                </div>
                                <div className="flex flex-col text-white">
                                    <p className="text-sm">Service Area:</p>
                                    <p className="text-xs">
                                        Calgary, Airdrie, Chestermere, Cochrane,
                                        Okotoks, Strathmore &amp; surrounding
                                        communities
                                    </p>
                                </div>
                            </a>

                            <a
                                href="tel:+15874369825"
                                className="flex flex-row gap-2"
                            >
                                <div className="flex items-center justify-center rounded bg-secondary/20 p-2">
                                    <Phone className="h-5 w-5 text-white" />
                                </div>
                                <div className="flex flex-col text-white">
                                    <p className="text-sm">Phone:</p>
                                    <p className="text-xs">(587) 436-9825</p>
                                </div>
                            </a>

                            <a
                                href="mailto:info@creativeabilitiestherapyservices.ca"
                                className="flex flex-row gap-2"
                            >
                                <div className="flex items-center justify-center rounded bg-secondary/20 p-2">
                                    <Mail className="h-5 w-5 text-white" />
                                </div>
                                <div className="flex flex-col text-white">
                                    <p className="text-sm">Email:</p>
                                    <p className="text-xs">
                                        info@creativeabilitiestherapyservices.ca
                                    </p>
                                </div>
                            </a>

                            <div className="flex flex-row gap-2">
                                <div className="flex items-center justify-center rounded bg-secondary/20 p-2">
                                    <Clock className="h-5 w-5 text-white" />
                                </div>
                                <div className="flex flex-col text-white">
                                    <p className="text-sm">Hours:</p>
                                    <p className="text-xs">
                                        Monday&ndash;Friday: 8:00 AM - 6:00 PM
                                    </p>
                                    <p className="text-xs">
                                        Saturday: By Appointment
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="my-5 flex flex-col items-center justify-between border-t-2 border-white/20 pt-4 lg:flex-row lg:items-start">
                        <div className="grid grid-cols-2 gap-10 md:flex">
                            <Link
                                href="/termsandconditions"
                                className="text-white transition-colors hover:text-primary"
                            >
                                Terms & Conditions
                            </Link>
                            <Link
                                href="/privacypolicy"
                                className="text-white transition-colors hover:text-primary"
                            >
                                Privacy Policy
                            </Link>
                            <Link
                                href="/cookiepolicy"
                                className="text-white transition-colors hover:text-primary"
                            >
                                Cookie Policy
                            </Link>
                            <Link
                                href="/accessibility"
                                className="text-white transition-colors hover:text-primary"
                            >
                                Accessibility
                            </Link>
                        </div>
                        <p className="mt-10 text-sm text-white lg:mt-0">
                            © {new Date().getFullYear()} Creative Abilities
                            Therapy Services. Powered by EasyTech Innovations.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

/**
 * Embedded contact form, ported from cats-frontend/src/forms/ContactForm.tsx.
 * Posts straight to the Laravel `public.contacts.store` route via Inertia's
 * useForm instead of react-hook-form + zod + contactAPI.create().
 */
function ContactForm() {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        recentlySuccessful,
    } = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/contacts', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div>
                <label
                    htmlFor="contact-name"
                    className="mb-2 block text-sm font-medium"
                >
                    Name
                </label>
                <input
                    id="contact-name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="w-full rounded-[5px] border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-primary"
                />
                {errors.name && (
                    <p className="mt-1 text-xs text-red-500">{errors.name}</p>
                )}
            </div>

            <div>
                <label
                    htmlFor="contact-email"
                    className="mb-2 block text-sm font-medium"
                >
                    Email
                </label>
                <input
                    id="contact-email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="w-full rounded-[5px] border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-primary"
                />
                {errors.email && (
                    <p className="mt-1 text-xs text-red-500">{errors.email}</p>
                )}
            </div>

            <div>
                <label
                    htmlFor="contact-phone"
                    className="mb-2 block text-sm font-medium"
                >
                    Phone (Optional)
                </label>
                <input
                    id="contact-phone"
                    type="text"
                    value={data.phone}
                    placeholder="Optional"
                    maxLength={12}
                    onChange={(e) =>
                        setData(
                            'phone',
                            e.target.value.replace(/[^0-9()+-\s]/g, ''),
                        )
                    }
                    className="w-full rounded-[5px] border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-primary"
                />
                {errors.phone && (
                    <p className="mt-1 text-xs text-red-500">{errors.phone}</p>
                )}
            </div>

            <div>
                <label
                    htmlFor="contact-subject"
                    className="mb-2 block text-sm font-medium"
                >
                    Subject
                </label>
                <input
                    id="contact-subject"
                    type="text"
                    value={data.subject}
                    onChange={(e) => setData('subject', e.target.value)}
                    maxLength={255}
                    className="w-full rounded-[5px] border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-primary"
                />
                {errors.subject && (
                    <p className="mt-1 text-xs text-red-500">
                        {errors.subject}
                    </p>
                )}
            </div>

            <div>
                <label
                    htmlFor="contact-message"
                    className="mb-2 flex items-center justify-between text-sm font-medium"
                >
                    <span>How can we help?</span>
                    <span className="text-xs text-gray-500">
                        {data.message.length}/500
                    </span>
                </label>
                <textarea
                    id="contact-message"
                    value={data.message}
                    onChange={(e) =>
                        setData('message', e.target.value.slice(0, 500))
                    }
                    rows={4}
                    maxLength={500}
                    className="w-full rounded-[5px] border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-primary"
                />
                {errors.message && (
                    <p className="mt-1 text-xs text-red-500">
                        {errors.message}
                    </p>
                )}
            </div>

            {recentlySuccessful && (
                <p className="text-sm font-medium text-green-600">
                    We will contact you as soon as possible.
                </p>
            )}

            <Button
                id="contact-submit"
                type="submit"
                className="w-full rounded-[5px]"
                disabled={processing}
            >
                <Send className="mr-2 inline h-4 w-4" />{' '}
                {processing ? 'Sending' : 'Send Message'}
            </Button>
        </form>
    );
}
