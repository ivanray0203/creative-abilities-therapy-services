/**
 * FSCD page content, ported 1:1 from cats-frontend/src/json/fscd.tsx.
 */

export interface FscdStep {
    id: number;
    title: string;
    desc: string;
}

export const Process: FscdStep[] = [
    {
        id: 1,
        title: 'Approval from FSCD',
        desc: 'Your family connects with FSCD and completes the intake and assessment process. If approved, FSCD will determine the level and type of support your child is eligible for, such as Specialized Services or Behavioural and Developmental Support.',
    },
    {
        id: 2,
        title: 'Choosing a Service Provider',
        desc: 'Once approved, families can choose Creative Abilities Therapy Services as their provider. We are experienced in delivering FSCD-funded supports and follow all required protocols and documentation processes.',
    },
    {
        id: 3,
        title: 'Initial Planning Meeting',
        desc: "We meet with your family and your FSCD caseworker to review your child's strengths, challenges, and goals. This meeting helps shape a collaborative, individualized plan that outlines the type of support and the professionals who will be involved.",
    },
    {
        id: 4,
        title: 'Team-Based Support',
        desc: 'Depending on the services approved, your child may work with a multidisciplinary team that could include occupational therapists, speech-language pathologists, behavioural consultants, psychologists, child development facilitators, and a clinical coordinator who oversees and ensures coordination.',
    },
    {
        id: 5,
        title: 'Ongoing Monitoring and Collaboration',
        desc: "We provide regular updates, track progress, and communicate closely with families and FSCD caseworkers. Service plans are reviewed and adjusted as your child's needs evolve.",
    },
];

export interface FscdWhyPoint {
    id: number;
    title: string;
    desc: string;
    icon: string;
}

export const WhyFscd: FscdWhyPoint[] = [
    {
        id: 1,
        title: 'Direct Billing & FSCD-Approved Rates',
        desc: 'We offer the convenience of direct billing to FSCD and strictly follow FSCD-approved rates. No out-of-pocket costs or reimbursement delays.',
        icon: 'Shield',
    },
    {
        id: 2,
        title: 'FSCD Expertise',
        desc: 'Our team is highly experienced in working within the FSCD framework, understanding documentation, reporting, and planning processes.',
        icon: 'ReceiptText',
    },
    {
        id: 3,
        title: 'Multidisciplinary Team Approach',
        desc: 'Our comprehensive team collaborates closely to provide holistic and integrated care across all areas of development.',
        icon: 'Users2',
    },
    {
        id: 4,
        title: 'Individualized Support Plans',
        desc: "Every child is unique. We tailor each intervention plan to your child's developmental needs, strengths, and goals.",
        icon: 'Target',
    },
    {
        id: 5,
        title: 'Family-Centered Care',
        desc: "We value parents as partners, empowering families with tools, strategies, and support for your child's success at home and in the community.",
        icon: 'Heart',
    },
    {
        id: 6,
        title: 'Inclusive & Culturally Sensitive',
        desc: 'We provide respectful and inclusive care, especially for families new to Canada, adapting to diverse values, backgrounds, and languages.',
        icon: 'Globe',
    },
    {
        id: 7,
        title: 'Seamless Communication',
        desc: 'Our team offers clear guidance, regular updates, and open communication to ensure you feel confident and supported throughout.',
        icon: 'MessageCircleIcon',
    },
    {
        id: 8,
        title: 'School & Community Collaboration',
        desc: 'We work directly with schools, daycare providers, and other support systems to promote consistency and integration across environments.',
        icon: 'School',
    },
];
