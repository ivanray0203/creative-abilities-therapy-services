/**
 * A parent and child inside a heart, held in a cupped hand — the mark on the
 * public Services page's "Our Therapy Services" badge.
 *
 * Inline SVG rather than an image file so it inherits the page's sizing
 * classes and stays crisp at any scale. The heart is a stroked outline (not a
 * fill) so the two figures read through it, which is what carries the meaning
 * at badge size.
 */
export default function FamilyHeartHand({
    className = '',
}: {
    className?: string;
}) {
    return (
        <svg
            viewBox="0 0 96 96"
            className={className}
            role="img"
            aria-hidden="true"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                <linearGradient
                    id="fhh-heart"
                    gradientUnits="userSpaceOnUse"
                    x1="24"
                    y1="52"
                    x2="72"
                    y2="14"
                >
                    <stop offset="0" stopColor="#22B573" />
                    <stop offset="0.35" stopColor="#1E86E0" />
                    <stop offset="0.72" stopColor="#8E2BC9" />
                    <stop offset="1" stopColor="#E8188F" />
                </linearGradient>
                <linearGradient
                    id="fhh-hand"
                    gradientUnits="userSpaceOnUse"
                    x1="16"
                    y1="56"
                    x2="80"
                    y2="78"
                >
                    <stop offset="0" stopColor="#3FC08A" />
                    <stop offset="1" stopColor="#0E9B93" />
                </linearGradient>
            </defs>

            {/* Heart outline */}
            <path
                d="M48 57c-6-6-21-15-21-27 0-7.7 5.6-13 12-13 4.3 0 7.4 2.4 9 5 1.6-2.6 4.7-5 9-5 6.4 0 12 5.3 12 13 0 12-15 21-21 27Z"
                stroke="url(#fhh-heart)"
                strokeWidth="6.5"
                strokeLinejoin="round"
            />

            {/* Parent */}
            <circle cx="41" cy="27.5" r="5.2" fill="#1565C0" />
            <path
                d="M33.8 47c0-7 3.2-11.2 7.2-11.2s7.2 4.2 7.2 11.2Z"
                fill="#1565C0"
            />

            {/* Child */}
            <circle cx="56" cy="33" r="4.2" fill="#F5A623" />
            <path
                d="M50.2 47c0-5.6 2.6-9 5.8-9s5.8 3.4 5.8 9Z"
                fill="#F5A623"
            />

            {/*
             * The hand sits high enough to touch the heart's point — dropped
             * any lower it reads as a smile under the mark rather than a
             * hand holding it. Fingertips flick up at both ends for the
             * same reason.
             */}
            <g transform="translate(0 -6)">
                <path
                    d="M18 55c-3.6 0-5.2 3.6-2.7 6.3 5 5.6 11.6 13 18.5 16.5 8.9 4.6 20 4.6 28.9 0 6.9-3.5 13.5-10.9 18.5-16.5 2.5-2.7.9-6.3-2.7-6.3-2 0-3.4 1.1-5.3 3.1-3.5 3.6-7 7.1-10.6 9.2-6.4 3.6-14.4 3.6-20.8 0-3.6-2.1-7.1-5.6-10.6-9.2-1.9-2-3.3-3.1-5.3-3.1Z"
                    fill="url(#fhh-hand)"
                />
                <g
                    stroke="#0B7F73"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                    opacity="0.35"
                >
                    <path d="M25 60c2.4 2.8 5 5.4 7.6 7.4" />
                    <path d="M71 60c-2.4 2.8-5 5.4-7.6 7.4" />
                </g>
            </g>
        </svg>
    );
}
