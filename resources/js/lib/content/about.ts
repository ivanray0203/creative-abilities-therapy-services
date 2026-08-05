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
            "We provide individualized, evidence-based support that meets your child where they are and helps them grow in confidence, skills, and independence.",
    },
    {
        id: 2,
        icon: 'Users',
        title: 'Collaborative Approach',
        description:
            "Parents are at the heart of a child's support system. We work closely with you to create individualized plans that reflect your child's goals and your family's values.",
    },
    {
        id: 3,
        icon: 'Activity',
        title: 'Nurturing Environments',
        description:
            'As Child Development Facilitators, we create nurturing environments where children feel safe to explore, learn, and grow through play, social interaction, and connection.',
    },
];

export const Approach: AboutPoint[] = [
    {
        id: 1,
        icon: 'Heart',
        title: 'Child-Centered Care',
        description:
            "Every intervention is designed around your child's unique strengths, interests, and developmental stage. We celebrate individual differences and build on what your child already does well.",
    },
    {
        id: 2,
        icon: 'Users',
        title: 'Collaborative Partnership',
        description:
            'We work closely with families, educators, and other professionals to create a unified support network. Your insights and goals drive our intervention planning.',
    },
    {
        id: 3,
        icon: 'Sparkle',
        title: 'Evidence-Based Methods',
        description:
            "Our therapies are grounded in current research and best practices, aligned with Alberta's professional standards and regulatory requirements.",
    },
    {
        id: 4,
        icon: 'Target',
        title: 'Functional Goals',
        description:
            'We focus on skills that make a real difference in daily life—helping children participate more fully at home, school, and in their communities.',
    },
    {
        id: 5,
        icon: 'Brain',
        title: 'Holistic Development',
        description:
            'We address the whole child—physical, cognitive, social, and emotional development—recognizing that all areas are interconnected.',
    },
    {
        id: 6,
        icon: 'Activity',
        title: 'Play-Based Learning',
        description:
            'Children learn best through play. We use engaging, motivating activities that feel fun while building essential skills and confidence.',
    },
];

export const Approach2: AboutPoint[] = [
    {
        id: 1,
        icon: 'Target',
        title: 'Individualized Behavioural Support',
        description:
            'We design personalized intervention plans to address communication, social skills, emotional regulation, and daily living skills. This approach ensures meaningful progress for each child, using strategies tailored to their learning style.',
    },
    {
        id: 2,
        icon: 'Home',
        title: 'Family Involvement and Education',
        description:
            "We believe that parents are key partners in their child's progress. Our team provides guidance, resources, and training to equip families with the tools they need to support their child's development at home and beyond.",
    },
    {
        id: 3,
        icon: 'School',
        title: 'School and Community Collaboration',
        description:
            'We collaborate with schools, daycare providers, and community organizations to ensure that your child receives consistent support across all areas of their life. This collaboration creates a seamless network of care, helping children succeed in every environment.',
    },
    {
        id: 4,
        icon: 'Checklist',
        title: 'Comprehensive Assessments and Evaluations',
        description:
            "Our thorough assessments and evaluations, in compliance with Alberta's guidelines, help us understand your child's specific needs. These evaluations guide our intervention plans, ensuring we address all aspects of their development and maximize their potential.",
    },
];
