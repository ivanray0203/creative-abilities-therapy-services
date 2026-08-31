/**
 * Services page marketing content (journey/regulated bodies/funding/why),
 * ported 1:1 from cats-frontend/src/json/services.tsx.
 */

export interface JourneyStep {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const Journey: JourneyStep[] = [
    {
        id: 1,
        title: 'Intake',
        desc: 'Complete our online intake form to tell us about your child, your family’s needs, and the services you’re looking for.',
        icon: 'ReceiptText',
    },
    {
        id: 2,
        title: 'Observation & Informal Assessment',
        desc: 'We learn more about your child’s strengths, needs, and goals through observation and informal assessment, depending on the service.',
        icon: 'CheckCircle',
    },
    {
        id: 3,
        title: 'Service Planning',
        desc: 'Together with your family and service team, we develop the appropriate plan for your child’s services, such as a Service Providers Program Plan (SPPP) or Individualized Service Plan (ISP), based on your child’s strengths, needs, goals, and family priorities.',
        icon: 'GraduationCap',
    },
    {
        id: 4,
        title: 'Begin Services',
        desc: 'Your child begins regular sessions with the appropriate members of their service team based on the goals and plan developed with your family.',
        icon: 'Heart',
    },
    {
        id: 5,
        title: 'Review Progress',
        desc: 'Your child’s progress is reviewed regularly, and goals or strategies may be adjusted as needed to continue supporting their development and participation.',
        icon: 'Sparkle',
    },
];

export interface RegulatedBody {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const Regulated: RegulatedBody[] = [
    {
        id: 1,
        title: 'ACSLPA',
        desc: 'Alberta College of Speech-Language Pathologists and Audiologists',
        icon: 'Activity',
    },
    {
        id: 2,
        title: 'CAP',
        desc: 'College of Alberta Psychologists',
        icon: 'MessageCircle',
    },
    {
        id: 3,
        title: 'ACOT',
        desc: 'Alberta College of Occupational Therapists',
        icon: 'Brain',
    },
    {
        id: 4,
        title: 'CPTA',
        desc: 'College of Physiotherapists of Alberta',
        icon: 'Heart',
    },
];

export interface FundingSourceInfo {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const Funding: FundingSourceInfo[] = [
    {
        id: 1,
        title: 'FSCD',
        desc: 'Family Support for Children with Disabilities (FSCD) provides funding for eligible Alberta families. Creative Abilities Therapy Services is an approved FSCD service provider and offers eligible services at FSCD-approved rates.',
        icon: 'HandCoins',
    },
    {
        id: 2,
        title: 'Private Insurance',
        desc: 'Some extended health benefit plans may cover eligible therapy services. We provide detailed invoices for insurance submission and may offer direct billing when available.',
        icon: 'ShieldCheck',
    },
];

export interface ResourceLink {
    id: number;
    title: string;
    desc: string;
    path: string;
    icon: string;
}

export const Resources: ResourceLink[] = [
    {
        id: 1,
        title: 'Services Brochure',
        desc: 'Complete overview of all therapy services',
        path: '/assets',
        icon: 'ReceiptText',
    },
    {
        id: 2,
        title: 'Intake Guide',
        desc: 'What to expect during intake',
        path: '/assets',
        icon: 'CircleCheck',
    },
    {
        id: 3,
        title: 'FSCD Application Help',
        desc: 'Step-by-step funding guide',
        path: '/assets',
        icon: 'GraduationCap',
    },
    {
        id: 4,
        title: 'Parent Resources',
        desc: 'Tips for supporting your child',
        path: '/assets',
        icon: 'Heart',
    },
];

export interface WhyPoint {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const Why: WhyPoint[] = [
    {
        id: 1,
        title: 'Home & Community Services',
        desc: 'We provide services in home and community settings to support children in everyday environments where they live, learn, play, and participate.',
        icon: 'Heart',
    },
    {
        id: 2,
        title: 'Inclusive & Respectful Care',
        desc: 'We provide inclusive, family-centred support that respects each family’s background, values, preferences, and goals. Our team works to create a welcoming environment where children and families feel supported and understood.',
        icon: 'Sparkle',
    },
];

export interface Location {
    id: number;
    type: string;
    desc: string;
}

export const locations: Location[] = [
    { id: 1, type: 'Virtual', desc: 'Virtual video Session' },
    { id: 2, type: 'Clinic', desc: 'Session at CATS facility' },
    { id: 3, type: 'Home Visit', desc: 'Session at clients home' },
];
