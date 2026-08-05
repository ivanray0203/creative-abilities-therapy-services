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
    duration: string;
}

export const ApplicationProcess: ApplicationProcessStep[] = [
    {
        id: 1,
        icon: 'Luggage',
        title: 'Apply',
        description: 'Submit your application through our online portal',
        duration: 'Same day',
    },
    {
        id: 2,
        icon: 'CircleCheckIcon',
        title: 'Review',
        description: 'Our team reviews your qualifications and experience',
        duration: '1-2 business days',
    },
    {
        id: 3,
        icon: 'MessageCircleIcon',
        title: 'Interview',
        description: 'Meet with our team to discuss the role and fit',
        duration: '1-2 weeks',
    },
    {
        id: 4,
        icon: 'Medal',
        title: 'Offer',
        description: 'Receive your offer and begin onboarding',
        duration: '1 week',
    },
];

export interface BenefitGroup {
    id: number;
    title: string;
    desc: string[];
    icon: string;
}

export const Benefits: BenefitGroup[] = [
    {
        id: 1,
        title: 'Compensation & Financial',
        desc: ['Competive contract rates'],
        icon: 'DollarSign',
    },
    {
        id: 2,
        title: 'Flexibility & Balance',
        desc: ['Choose your own schedule', 'Client location-based services'],
        icon: 'Clock',
    },
    {
        id: 3,
        title: 'Professional Development',
        desc: ['Conference attendance support', 'Mentorship program)'],
        icon: 'GraduationCap',
    },
    {
        id: 5,
        title: 'Community & Culture',
        desc: ['Team building events', 'Quarterly team retreats'],
        icon: 'Users',
    },
    {
        id: 6,
        title: 'Additional Perks',
        desc: ['Admin support provided', 'Resource library access'],
        icon: 'Sparkle',
    },
];

export const WEEK_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

export const RESIDENT_STATUS = ['Canadian Citizen', 'Permanent Resident', 'Work Permit', 'International Student'];

export const EDUCATION_OPTIONS = [
    { key: 'high_school_or_less', label: 'High school or less' },
    { key: 'some_post_secondary', label: 'Some post-secondary (college, university, trades)' },
    { key: 'bachelors', label: "Bachelor's degree" },
    { key: 'graduate_or_professional', label: 'Graduate or professional degree' },
] as const;

export const LEAD_SOURCE_OPTIONS = [
    { key: 'social_media', label: 'Social media (Facebook, Instagram, etc.)' },
    { key: 'doctor_referral', label: 'Referral from doctor/physician' },
    { key: 'family_friend', label: 'Referral from family/friend' },
    { key: 'client_referral', label: 'Referral from another client' },
    { key: 'calgary_counselling', label: 'Calgary Counselling Centre / Counselling Alberta' },
    { key: 'website_ad', label: 'Website / online ad' },
    { key: 'flyer', label: 'Flyer / poster' },
    { key: 'walk_in', label: 'Walk-in / self-referral' },
    { key: 'other', label: 'Other (please specify)' },
] as const;
