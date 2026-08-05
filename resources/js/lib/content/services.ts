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
    { id: 1, title: 'Intake', desc: 'Complete out online form', icon: 'ReceiptText' },
    { id: 2, title: 'Observation', desc: 'Informal Assesment', icon: 'CheckCircle' },
    { id: 3, title: 'Plan', desc: 'Individualizes service plan', icon: 'GraduationCap' },
    { id: 4, title: 'Therapy', desc: 'Regular sessions begin', icon: 'Heart' },
    { id: 5, title: 'Progress', desc: 'Ongoing review & growth', icon: 'Sparkle' },
];

export interface RegulatedBody {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const Regulated: RegulatedBody[] = [
    { id: 1, title: 'ACSLPA', desc: 'Alberta College of Speech-Language Pathologists', icon: 'Activity' },
    { id: 2, title: 'CAP', desc: 'College of Alberta Psychologists', icon: 'MessageCircle' },
    { id: 3, title: 'ACOT', desc: 'Alberta College of Occupational Therapists', icon: 'Brain' },
    { id: 4, title: 'CPTA', desc: 'College of Physiotherapist of Alberta ', icon: 'Heart' },
];

export interface FundingSourceInfo {
    id: number;
    title: string;
    desc: string;
    sub_desc: string;
}

export const Funding: FundingSourceInfo[] = [
    {
        id: 1,
        title: 'FSCD',
        desc: 'Family Support for Children with Disabilities provides funding for eligible Alberta families.',
        sub_desc: 'We are approved FSCD service providers',
    },
    {
        id: 2,
        title: 'Private Insurance',
        desc: 'Most extended health benefit plans cover therapy services with proper documentation.',
        sub_desc: 'We provide detailed invoices for insurance submission and direct billing when available.',
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
    { id: 1, title: 'Services Brochure', desc: 'Complete overview of all therapy services', path: '/assets', icon: 'ReceiptText' },
    { id: 2, title: 'Intake Guide', desc: 'What to expect during intake', path: '/assets', icon: 'CircleCheck' },
    { id: 3, title: 'FSCD Application Help', desc: 'Step-by-step funding guide', path: '/assets', icon: 'GraduationCap' },
    { id: 4, title: 'Parent Resources', desc: 'Tips for supporting your child', path: '/assets', icon: 'Heart' },
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
        title: 'Alberta Regulatory Standards',
        desc: "All our services meet or exceed Alberta's professional regulatory standards, ensuring the highest quality of care",
        icon: 'GraduationCap',
    },
    {
        id: 2,
        title: 'Home Community',
        desc: 'We provide services in the settings where children live, learn, and play—maximizing real-world application.',
        icon: 'Heart',
    },
    {
        id: 3,
        title: 'Culturally Sensitive Care',
        desc: 'We proudly support immigrant families with navigation assistance and inclusive, culturally responsive therapy.',
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
