/**
 * Team page content, ported from cats-frontend/src/json/temp.tsx (TeamsData, Values).
 */

export interface TeamMemberEntry {
    id: number;
    title: string;
    position: string;
    department: string;
    first_name: string;
    last_name: string;
    photo: string;
    description: string;
    credentials: string[];
    email: string;
}

export const TeamsData: TeamMemberEntry[] = [
    {
        id: 1,
        title: 'Dr.',
        position: 'Lead Occupational Therapist',
        department: 'Sensory Integration & Fine Motor Skills',
        first_name: 'Sarah',
        last_name: 'Mitchelle',
        photo: '',
        description:
            'Dr. Mitchell brings over 12 years of experience in pediatric occupational therapy, specializing in sensory processing disorders and fine motor development. She is passionate about helping children build independence through meaningful, play-based interventions.',
        credentials: [
            'Master of Science in Occupational Therapy',
            'Registered with Alberta College of Occupational Therapists',
            'Certified in Sensory Integration Therapy',
            'Pediatric Feeding Specialist',
        ],
        email: 'drSarah@example.com',
    },
    {
        id: 2,
        title: 'Dr.',
        position: 'Pediatric Speech Therapist',
        department: 'Communication & Language Development',
        first_name: 'James',
        last_name: 'Anderson',
        photo: '',
        description:
            'Dr. Anderson has 10 years of experience supporting children with speech and language delays. He uses evidence-based interventions to improve communication, social interaction, and confidence.',
        credentials: [
            'Master of Speech-Language Pathology',
            'Certified in Augmentative & Alternative Communication',
            'Member of Speech-Language & Audiology Canada',
        ],
        email: 'james.anderson@example.com',
    },
    {
        id: 3,
        title: 'Ms.',
        position: 'Pediatric Physical Therapist',
        department: 'Gross Motor & Mobility',
        first_name: 'Emily',
        last_name: 'Clark',
        photo: '',
        description:
            'Emily specializes in helping children develop gross motor skills, strength, and coordination. She designs individualized therapy plans to enhance mobility and participation.',
        credentials: [
            'Bachelor of Physical Therapy',
            'Pediatric Physical Therapy Certification',
            'Member of Canadian Physiotherapy Association',
        ],
        email: 'emily.clark@example.com',
    },
    {
        id: 4,
        title: 'Ms.',
        position: 'Occupational Therapist',
        department: 'Sensory & Self-Care Skills',
        first_name: 'Olivia',
        last_name: 'Martinez',
        photo: '',
        description:
            "Olivia works with children on sensory processing, daily living skills, and functional independence. She creates engaging, play-based activities tailored to each child's needs.",
        credentials: [
            'Master of Occupational Therapy',
            'Registered with Ontario Society of Occupational Therapists',
            'Certified Sensory Integration Therapist',
        ],
        email: 'olivia.martinez@example.com',
    },
    {
        id: 5,
        title: 'Dr.',
        position: 'Child Psychologist',
        department: 'Behavioral & Emotional Support',
        first_name: 'Michael',
        last_name: 'Brown',
        photo: '',
        description:
            'Dr. Brown provides behavioral therapy, emotional support, and counseling for children and families. He focuses on fostering resilience and positive social-emotional development.',
        credentials: ['PhD in Child Psychology', 'Registered Psychologist with CPA', 'Certified in Cognitive Behavioral Therapy'],
        email: 'michael.brown@example.com',
    },
    {
        id: 6,
        title: 'Ms.',
        position: 'Speech & Language Assistant',
        department: 'Communication Support',
        first_name: 'Sophia',
        last_name: 'Lee',
        photo: '',
        description:
            'Sophia assists with speech therapy sessions, implementing activities to improve communication and language skills. She is skilled in augmentative communication tools.',
        credentials: ['Diploma in Speech-Language Pathology Assistance', 'Certified in Early Childhood Communication'],
        email: 'sophia.lee@example.com',
    },
];

export interface ValuePoint {
    id: number;
    icon: string;
    title: string;
    description: string;
}

export const Values: ValuePoint[] = [
    {
        id: 1,
        icon: 'Heart',
        title: 'Compassionate Care',
        description:
            'We approach every child and family with empathy, understanding, and respect for their unique journey.',
    },
    {
        id: 2,
        icon: 'Medal',
        title: 'Evidence-Based Practice',
        description:
            "Our interventions are grounded in the latest research and aligned with Alberta's regulatory standards.",
    },
    {
        id: 3,
        icon: 'GraduationCap',
        title: 'Continuous Learning',
        description: 'We stay current with best practices through ongoing professional development and training.',
    },
];
