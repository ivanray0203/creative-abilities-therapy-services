import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Cookie as CookieIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';

export default function Cookie() {
    return (
        <>
            <Head title="Cookie Policy" />

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
                            <CookieIcon />
                        </div>

                        <div>
                            <p className="text-2xl text-primary">
                                Cookie Policy
                            </p>
                            <p className="text-sm text-charcoal-gray">
                                Last Updated: November 14, 2025
                            </p>
                        </div>
                    </div>

                    <div className="max-h-[80%] overflow-auto scroll-smooth">
                        {/* What are Cookies */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                What Are Cookies?
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Cookies are small text files that are stored on
                                your device (computer, tablet, or mobile phone)
                                when you visit our website. They help our
                                website remember information about your visit,
                                which makes it easier to use and more helpful.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Think of cookies like bookmarks - they help the
                                website remember who you are and what you
                                prefer, so you don't have to start from scratch
                                every time you visit.
                            </p>
                        </div>

                        {/* How We Use Cookies */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                How We Use Cookies
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We use cookies on our website to:
                            </p>

                            <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                <li>
                                    Keep you logged in to our secure portals
                                </li>
                                <li>Remember your preferences and settings</li>
                                <li>
                                    Ensure the website works properly and
                                    securely
                                </li>
                                <li>
                                    Understand how people use our website so we
                                    can make improvements
                                </li>
                            </ul>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                All cookies we use are intended to improve your
                                experience on our website and help us provide
                                better service to our clients and families.
                            </p>
                        </div>

                        {/* Types of Cookies */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Types of Cookies We Use
                            </p>

                            <div className="mt-3 rounded-sm bg-secondary-orange/5 p-5">
                                <p className="mt-3 font-semibold text-primary">
                                    Essential Cookies
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    These cookies are necessary for the website
                                    to work. They enable basic functions like
                                    logging in, accessing secure areas, and
                                    submitting forms. Without these cookies, the
                                    website cannot function properly.
                                </p>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    <span className="font-bold">Examples:</span>{' '}
                                    Login sessions, security features, form
                                    submissions
                                </p>
                            </div>

                            <div className="mt-3 rounded-sm bg-secondary-orange/5 p-5">
                                <p className="mt-3 font-semibold text-primary">
                                    Performance Cookies
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    These cookies help us understand how
                                    visitors use our website by collecting
                                    information about which pages are visited
                                    most often and if visitors get error
                                    messages. This information helps us improve
                                    how our website works.
                                </p>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    <span className="font-bold">Examples:</span>{' '}
                                    Page visit tracking, error reporting
                                </p>
                            </div>

                            <div className="mt-3 rounded-sm bg-secondary-orange/5 p-5">
                                <p className="mt-3 font-semibold text-primary">
                                    Functionality Cookies
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    These cookies allow our website to remember
                                    choices you make (such as your language
                                    preference or region) and provide enhanced,
                                    more personal features.
                                </p>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    <span className="font-bold">Examples:</span>{' '}
                                    Language settings, accessibility preferences
                                </p>
                            </div>
                        </div>

                        {/* Managing Your Cookie Preferences */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Managing Your Cookie Preferences
                            </p>

                            <div>
                                <p className="mt-3 text-muted-foreground">
                                    When you first visit our website, you'll see
                                    a message asking if you accept cookies. You
                                    can choose to accept or decline at that
                                    time.
                                </p>

                                <p className="mt-3 text-muted-foreground">
                                    You can also control cookies through your
                                    web browser settings. Most browsers allow
                                    you to:
                                </p>

                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        See what cookies are stored on your
                                        device
                                    </li>
                                    <li>Delete some or all cookies</li>
                                    <li>Block cookies from being set</li>
                                    <li>
                                        Get notified when a website tries to set
                                        a cookie
                                    </li>
                                    <li>
                                        Circumstances where services are no
                                        longer clinically appropriate
                                    </li>
                                </ul>
                            </div>

                            <p className="mt-3 text-sm text-muted-foreground">
                                <span className="font-bold">Please note: </span>{' '}
                                If you choose to block or delete cookies, some
                                parts of our website may not work properly. For
                                example, you may not be able to log in to our
                                secure portals or submit forms.
                            </p>
                        </div>

                        {/* Browser Cookie Settings */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Browser Cookie Settings
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Each browser handles cookies differently. Here
                                are links to help pages for popular browsers:
                            </p>

                            <p className="mt-3">
                                <a
                                    href="https://support.google.com/chrome/answer/95647"
                                    className="text-primary"
                                >
                                    Google Chrome
                                </a>
                            </p>

                            <p className="mt-3">
                                <a
                                    href="https://support.apple.com/en-ca/guide/safari/sfri11471/mac"
                                    className="text-primary"
                                >
                                    Apple Safari
                                </a>
                            </p>
                            <p className="mt-3">
                                <a
                                    href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer"
                                    className="text-primary"
                                >
                                    Mozilla Firefox
                                </a>
                            </p>
                            <p className="mt-3">
                                <a
                                    href="https://support.microsoft.com/en-us/windows/manage-cookies-in-microsoft-edge-view-allow-block-delete-and-use-168dab11-0753-043d-7c16-ede5947fc64d"
                                    className="text-primary"
                                >
                                    Microsoft Edge
                                </a>
                            </p>
                        </div>

                        {/* Changes to This Policy */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                Changes to This Policy
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We may update this Cookie Policy from time to
                                time. When we make changes, we will update the
                                "Last Updated" date at the top of this page. We
                                encourage you to check this page occasionally to
                                stay informed about how we use cookies.
                            </p>
                        </div>

                        {/* Questions */}
                        <div className="border-b pb-3">
                            <div className="rounded-sm bg-secondary-orange/5 p-5">
                                <p className="text-2xl text-primary">
                                    Questions?
                                </p>
                                <p className="mt-3">
                                    If you have questions about our use of
                                    cookies, please contact us:
                                </p>

                                <p className="mt-3 font-bold">
                                    Creative Abilities Therapy Services
                                </p>
                                <p>Email: info@creativeabilitiestherapy.ca</p>
                                <p>Phone: (403) 555-0100</p>

                                <p className="text-sm">
                                    For more information about how we protect
                                    your privacy, please see our{' '}
                                    <Link
                                        href="/privacypolicy"
                                        className="text-primary"
                                    >
                                        Privacy Policy.
                                    </Link>{' '}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

Cookie.layout = (page: React.ReactNode) => (
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
