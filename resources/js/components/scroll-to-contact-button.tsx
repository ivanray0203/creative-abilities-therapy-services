import { Button } from '@/components/ui/button';

/**
 * Jumps to the footer's contact form. The page must render the footer with
 * `show_contact: true` for the `#contact_us` anchor to exist; without it the
 * plain `href` is left to the browser rather than smooth-scrolling.
 */
export default function ScrollToContactButton({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <Button asChild className={className}>
            <a
                href="#contact_us"
                onClick={(e) => {
                    const el = document.getElementById('contact_us');

                    if (el) {
                        e.preventDefault();
                        el.scrollIntoView({ behavior: 'smooth' });
                    }
                }}
            >
                {children}
            </a>
        </Button>
    );
}
