/**
 * Static "About" page content, ported 1:1 from cats-frontend/src/json/about.tsx.
 */

export interface AboutPoint {
    id: number;
    icon: string;
    title: string;
    description: string;
}

export const Philosophy: AboutPoint[] = [
    {
        id: 1,
        icon: 'Target',
        title: 'Meeting Children Where They Are',
        description:
            'We provide individualized, evidence-based support that builds on each child’s strengths while helping them develop confidence, skills, and greater independence in everyday life.',
    },
    {
        id: 2,
        icon: 'Users',
        title: 'Collaborative Approach',
        description:
            'Families are at the heart of a child’s support system. We work closely with parents and caregivers to create individualized plans that reflect each child’s goals, strengths, needs, and family priorities.',
    },
    {
        id: 3,
        icon: 'Activity',
        title: 'Nurturing Environments',
        description:
            'We create supportive, nurturing environments where children feel safe to explore, learn, and grow through play, social interaction, and meaningful connection.',
    },
];

export const Approach: AboutPoint[] = [
    {
        id: 1,
        icon: 'Heart',
        title: 'Child-Centred Care',
        description:
            'Our support is designed around each child’s unique strengths, interests, needs, and stage of development. We build on what each child already does well while supporting continued growth, confidence, and participation.',
    },
    {
        id: 2,
        icon: 'Users',
        title: 'Collaborative Partnerships',
        description:
            'We work closely with families, educators, and other professionals to create a coordinated support network around each child. Family insights and priorities help guide our planning and support.',
    },
    {
        id: 3,
        icon: 'Sparkle',
        title: 'Evidence-Based Methods',
        description:
            'Our services are informed by current research, best practices, and applicable professional standards. We use approaches that support safe, effective, and individualized care for children and families.',
    },
    {
        id: 4,
        icon: 'Target',
        title: 'Functional Goals',
        description:
            'We focus on practical, meaningful skills that support everyday life and help children participate more fully at home, at school, and in their community.',
    },
    {
        id: 5,
        icon: 'Brain',
        title: 'Holistic Development',
        description:
            'We support the whole child by considering physical, cognitive, social, emotional, and developmental needs together, recognizing that each area can influence a child’s overall growth and participation.',
    },
    {
        id: 6,
        icon: 'Activity',
        title: 'Play-Based Learning',
        description:
            'We use engaging, play-based activities to support learning, skill development, confidence, and participation in ways that are meaningful and motivating for each child.',
    },
];

export const Approach2: AboutPoint[] = [
    {
        id: 1,
        icon: 'Target',
        title: 'Individualized Behavioural Support',
        description:
            'We develop individualized support strategies based on each child’s strengths, needs, and goals. Support may focus on areas such as communication, social skills, emotional regulation, behaviour, and daily living skills.',
    },
    {
        id: 2,
        icon: 'Home',
        title: 'Family Involvement and Education',
        description:
            'We believe families are important partners in a child’s growth and development. Our team provides guidance, resources, and practical strategies to help parents and caregivers support their child at home and in everyday routines.',
    },
    {
        id: 3,
        icon: 'School',
        title: 'School and Community Collaboration',
        description:
            'When appropriate, we collaborate with schools, childcare providers, and community partners to support consistency across the environments that are important in a child’s everyday life.',
    },
    {
        id: 4,
        icon: 'Checklist',
        title: 'Comprehensive Assessments and Evaluations',
        description:
            'Our assessments and evaluations help us better understand each child’s strengths, needs, and areas for support. The information gathered helps guide individualized recommendations and service planning in accordance with applicable professional standards.',
    },
];
