/**
 * Careers configuration/static content, ported from cats-frontend/src/json/careers.tsx.
 * The `Careers` listing array itself is intentionally NOT ported — Phase 4 sources the
 * live listing from the database via CareerController.
 */

export interface ApplicationProcessStep {
    id: number;
    icon: string;
    title: string;
    description: string;
}

/**
 * The same four steps appear on the careers index and on every posting's
 * detail page, so both read them from here.
 */
export const ApplicationProcess: ApplicationProcessStep[] = [
    {
        id: 1,
        icon: 'Luggage',
        title: 'Apply',
        description:
            'Submit your application through our online application portal.',
    },
    {
        id: 2,
        icon: 'CircleCheckIcon',
        title: 'Review',
        description:
            'Our team reviews your application, qualifications, and relevant experience to determine whether your background aligns with the role.',
    },
    {
        id: 3,
        icon: 'MessageCircleIcon',
        title: 'Interview',
        description:
            'Meet with our team to learn more about the role, discuss your experience and availability, and determine whether the opportunity is a good fit for both you and Creative Abilities Therapy Services.',
    },
    {
        id: 4,
        icon: 'Medal',
        title: 'Offer & Onboarding',
        description:
            'If selected, you will receive an offer outlining the contractor opportunity and next steps. Once accepted, we will begin the onboarding process and prepare you for your Contract Review and Orientation.',
    },
];

export const APPLICATION_TIMELINE = {
    label: 'Typical Timeline: Approximately 1–2 Weeks',
    note: 'Timelines may vary depending on the position, interview availability, reference checks, and completion of onboarding requirements.',
};

export interface WhyWorkPoint {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const WhyWorkWithCats: WhyWorkPoint[] = [
    {
        id: 1,
        title: 'Competitive Contract Rates',
        desc: 'We offer competitive contract rates based on the role and services provided. Specific rates are outlined within each position posting.',
        icon: 'DollarSign',
    },
    {
        id: 2,
        title: 'Flexible Scheduling',
        desc: 'Set your availability based on your schedule and caseload.',
        icon: 'Clock',
    },
    {
        id: 3,
        title: 'Community-Based Opportunities',
        desc: 'Provide services in client homes and community settings throughout Calgary and surrounding areas.',
        icon: 'MapPin',
    },
    {
        id: 4,
        title: 'Learning & Growth Opportunities',
        desc: 'Access opportunities for ongoing learning, mentorship, and professional development when available.',
        icon: 'GraduationCap',
    },
    {
        id: 5,
        title: 'Training & Resources',
        desc: 'Contractors may have access to workshops, educational resources, and other learning opportunities offered through CATS.',
        icon: 'BookOpen',
    },
    {
        id: 6,
        title: 'Collaborative Team Environment',
        desc: 'Be part of a supportive team that values communication, collaboration, and shared learning.',
        icon: 'Users',
    },
    {
        id: 7,
        title: 'Team Connection',
        desc: 'Opportunities for team gatherings, workshops, and community-building activities may be offered throughout the year.',
        icon: 'Heart',
    },
    {
        id: 8,
        title: 'Administrative Support',
        desc: 'Access administrative support for service coordination, documentation processes, and other operational needs.',
        icon: 'ClipboardList',
    },
    {
        id: 9,
        title: 'Resources & Tools',
        desc: 'Contractors may have access to shared resources, templates, and materials to support their work with children and families.',
        icon: 'Sparkle',
    },
];

export const WEEK_DAYS = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];

export const RESIDENT_STATUS = [
    'Canadian Citizen',
    'Permanent Resident',
    'Work Permit',
    'International Student',
];

export const EDUCATION_OPTIONS = [
    { key: 'high_school_or_less', label: 'High school or less' },
    {
        key: 'some_post_secondary',
        label: 'Some post-secondary (college, university, trades)',
    },
    { key: 'bachelors', label: "Bachelor's degree" },
    {
        key: 'graduate_or_professional',
        label: 'Graduate or professional degree',
    },
] as const;

export const LEAD_SOURCE_OPTIONS = [
    { key: 'social_media', label: 'Social media (Facebook, Instagram, etc.)' },
    { key: 'doctor_referral', label: 'Referral from doctor/physician' },
    { key: 'family_friend', label: 'Referral from family/friend' },
    { key: 'client_referral', label: 'Referral from another client' },
    {
        key: 'calgary_counselling',
        label: 'Calgary Counselling Centre / Counselling Alberta',
    },
    { key: 'website_ad', label: 'Website / online ad' },
    { key: 'flyer', label: 'Flyer / poster' },
    { key: 'walk_in', label: 'Walk-in / self-referral' },
    { key: 'other', label: 'Other (please specify)' },
] as const;
