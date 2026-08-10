/**
 * Two cupped hands cradling a radiant heart — the mark used on the public
 * About page's welcome badge.
 *
 * Drawn inline rather than imported as a file so it inherits the page's
 * sizing classes and stays crisp at any scale. Each hand is a tapered filled
 * shape (narrow at the wrist, wide across the palm) with a lighter thumb
 * laid over it, and the two never meet at the bottom — joined strokes read
 * as a single smile rather than a pair of hands.
 */
export default function CaringHandsHeart({
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
                    id="chh-heart"
                    gradientUnits="userSpaceOnUse"
                    x1="32"
                    y1="14"
                    x2="62"
                    y2="46"
                >
                    <stop offset="0" stopColor="#F4744F" />
                    <stop offset="1" stopColor="#D8452A" />
                </linearGradient>
                <linearGradient
                    id="chh-left"
                    gradientUnits="userSpaceOnUse"
                    x1="14"
                    y1="50"
                    x2="46"
                    y2="94"
                >
                    <stop offset="0" stopColor="#2C8F99" />
                    <stop offset="1" stopColor="#125C68" />
                </linearGradient>
                <linearGradient
                    id="chh-right"
                    gradientUnits="userSpaceOnUse"
                    x1="82"
                    y1="50"
                    x2="50"
                    y2="94"
                >
                    <stop offset="0" stopColor="#F5825E" />
                    <stop offset="1" stopColor="#DF4A28" />
                </linearGradient>
            </defs>

            {/* Rays */}
            <g stroke="#F79463" strokeWidth="4.5" strokeLinecap="round">
                <path d="M23.1 29.8 17.1 29.3" />
                <path d="M25.7 20.7 20.4 17.9" />
                <path d="M31.6 13.1 27.7 8.6" />
                <path d="M39.9 8.4 37.9 2.7" />
                <path d="M48 7 48 1" />
                <path d="M56.1 8.4 58.1 2.7" />
                <path d="M64.4 13.1 68.3 8.6" />
                <path d="M70.3 20.7 75.6 17.9" />
                <path d="M72.9 29.8 78.9 29.3" />
            </g>

            {/* Heart */}
            <path
                d="M48 45C43.6 40.6 32 33.4 32 25.2 32 19.6 36.4 16 40.8 16c3.2 0 5.8 1.9 7.2 4 1.4-2.1 4-4 7.2-4C59.6 16 64 19.6 64 25.2 64 33.4 52.4 40.6 48 45Z"
                fill="url(#chh-heart)"
            />
            {/* Highlight on the upper-left lobe, as on the reference */}
            <path
                d="M41.2 20.6c-2.3 1-3.5 3-3.3 5.4"
                stroke="#FDE7D6"
                strokeWidth="2.1"
                strokeLinecap="round"
            />

            {/*
             * Each hand is nudged outward so they cup the heart without
             * merging into a single bowl at the bottom.
             */}
            <g transform="translate(-2.5 0)">
                <path
                    d="M47 93c5-7 1-15-7-21-6-5-12-12-14-20-1-4-7-5-9-1-3 6-5 15-3 23 3 9 14 17 24 20 4 1 8 1 9-1Z"
                    fill="url(#chh-left)"
                />
                {/* Thumb, laid across the palm */}
                <path
                    d="M43 84c-6-4-10-9-12-15"
                    stroke="#4CA6AE"
                    strokeWidth="5.5"
                    strokeLinecap="round"
                />
                {/* Finger separations */}
                <g
                    stroke="#0E4A54"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.55"
                >
                    <path d="M18.5 55c-1.4 3.4-1.7 6.8-1 10.2" />
                    <path d="M25.5 61c-.9 2.8-1 5.6-.4 8.4" />
                </g>
            </g>

            <g transform="translate(2.5 0)">
                <path
                    d="M49 93c-5-7-1-15 7-21 6-5 12-12 14-20 1-4 7-5 9-1 3 6 5 15 3 23-3 9-14 17-24 20-4 1-8 1-9-1Z"
                    fill="url(#chh-right)"
                />
                <path
                    d="M53 84c6-4 10-9 12-15"
                    stroke="#F79070"
                    strokeWidth="5.5"
                    strokeLinecap="round"
                />
                <g
                    stroke="#A8331A"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.5"
                >
                    <path d="M77.5 55c1.4 3.4 1.7 6.8 1 10.2" />
                    <path d="M70.5 61c.9 2.8 1 5.6.4 8.4" />
                </g>
            </g>
        </svg>
    );
}
