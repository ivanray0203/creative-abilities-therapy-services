import { Link, usePage } from '@inertiajs/react';
import { LogIn, Menu } from 'lucide-react';
import { useState } from 'react';
import type { PropsWithChildren } from 'react';

import CookieConsent from '@/components/cookie-consent';
import Footer from '@/components/footer';
import type { FooterProps } from '@/components/footer';
import LoginModal from '@/components/login-modal';
import { Button } from '@/components/ui/button';

const NAV_ITEMS = [
    { label: 'Home', path: '/' },
    { label: 'About Us', path: '/about' },
    { label: 'Services', path: '/services' },
    { label: 'Programs', path: '/programs' },
    { label: 'FSCD', path: '/fscd' },
    { label: 'Careers', path: '/careers' },
    { label: 'FAQ', path: '/faq' },
];

interface PublicLayoutProps extends PropsWithChildren {
    /** Per-page overrides for the shared Footer's "ready" CTA / contact section. */
    footer?: FooterProps;
}

/**
 * Shell for public marketing pages: header nav, page content, the full
 * ported Footer (CTA, embedded contact form, site/service/legal links), and
 * a globally-mounted cookie consent banner.
 */
export default function PublicLayout({ children, footer }: PublicLayoutProps) {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [loginOpen, setLoginOpen] = useState(false);
    const isActive = (path: string) => url === path;

    return (
        <div className="flex min-h-screen flex-col">
            <header className="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container mx-auto flex items-center justify-between py-3">
                    <Link href="/" className="flex items-center gap-3">
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center">
                            <img
                                src="/CatsLogo/web-app-manifest-512x512.png"
                                alt="CATS Logo"
                                className="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <p className="text-lg font-medium text-charcoal-gray md:text-xl">
                                Creative Abilities Therapy Services
                            </p>
                            <p className="text-xs font-semibold tracking-wide text-primary md:text-sm">
                                PLAY . GROW . THRIVE.
                            </p>
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-8 lg:flex">
                        {NAV_ITEMS.map((item) => (
                            <Link
                                key={item.path}
                                href={item.path}
                                className={`border-b-2 pb-1 text-sm font-medium transition-colors ${isActive(item.path) ? 'border-primary text-primary' : 'border-transparent hover:text-primary'}`}
                            >
                                {item.label}
                            </Link>
                        ))}

                        <Button
                            asChild
                            variant="default"
                            className="rounded-[5px] hover:border hover:border-primary hover:bg-white hover:text-primary-orange"
                        >
                            <Link href="/intake/apply">Start Intake</Link>
                        </Button>

                        <Button
                            variant="outline"
                            className="rounded-[5px] border-primary text-primary-orange hover:bg-primary-orange hover:text-white"
                            onClick={() => setLoginOpen(true)}
                        >
                            <LogIn className="mr-2 h-4 w-4" />
                            Login
                        </Button>
                    </nav>

                    <button
                        className="p-2 lg:hidden"
                        aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
                        onClick={() => setMobileOpen((v) => !v)}
                    >
                        {mobileOpen ? (
                            <span className="text-2xl font-bold">×</span>
                        ) : (
                            <Menu className="h-6 w-6" />
                        )}
                    </button>
                </div>

                {mobileOpen && (
                    <div className="absolute top-full right-0 z-40 w-full bg-white shadow-lg">
                        <nav className="mx-5 flex flex-col items-start gap-3 rounded bg-gray-100 pb-5">
                            {NAV_ITEMS.map((item) => (
                                <Link
                                    key={item.path}
                                    href={item.path}
                                    onClick={() => setMobileOpen(false)}
                                    className={`flex w-full px-2 py-2 text-lg transition-colors ${isActive(item.path) ? 'bg-secondary-orange/10 text-primary' : ''}`}
                                >
                                    {item.label}
                                </Link>
                            ))}
                            <Button
                                asChild
                                variant="default"
                                size="sm"
                                className="w-full flex-1 rounded py-2"
                            >
                                <Link
                                    href="/intake/apply"
                                    onClick={() => setMobileOpen(false)}
                                >
                                    Start Intake
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full flex-1 rounded border-primary py-2 text-primary-orange"
                                onClick={() => {
                                    setMobileOpen(false);
                                    setLoginOpen(true);
                                }}
                            >
                                <LogIn className="mr-2 h-4 w-4" />
                                Login
                            </Button>
                        </nav>
                    </div>
                )}
            </header>

            <main className="flex-1">{children}</main>

            <Footer {...footer} />
            <CookieConsent />
            <LoginModal open={loginOpen} onOpenChange={setLoginOpen} />
        </div>
    );
}
