/**
 * FSCD page content: the service process, the BDS/SS comparison, and the
 * reasons families choose CATS as their FSCD provider.
 */

export interface FscdStep {
    id: number;
    title: string;
    paragraphs: string[];
    /** Introduces `options`, when a step offers the family a choice. */
    lead_in?: string;
    options?: string[];
    /** Paragraphs rendered after the options list. */
    after?: string[];
}

export const Process: FscdStep[] = [
    {
        id: 1,
        title: 'FSCD Approval',
        paragraphs: [
            'Families apply directly through the Family Support for Children with Disabilities (FSCD) program. FSCD determines your child’s eligibility and the type and level of support approved within your family’s agreement.',
        ],
    },
    {
        id: 2,
        title: 'Choose Creative Abilities Therapy Services',
        paragraphs: [
            'Once services have been approved, your family may choose Creative Abilities Therapy Services as your service provider.',
            'Our team will complete the intake process, review your child’s approved services, learn more about your family’s needs and availability, and begin coordinating the appropriate service team.',
        ],
    },
    {
        id: 3,
        title: 'Planning & Goal Development',
        paragraphs: [
            'Your family, service providers, and other appropriate team members work together to identify your child’s strengths, needs, priorities, and goals.',
            'The appropriate service plan, such as a Service Providers Program Plan (SPPP) or Individualized Service Plan (ISP), is developed based on the services approved through FSCD.',
        ],
    },
    {
        id: 4,
        title: 'Build Your Service Team & Begin Services',
        paragraphs: [
            'Your child is connected with the professionals and support providers included in their approved services.',
            'For Behavioural and Developmental Support (BDS), the team may include a Behavioural & Developmental Aide and up to two clinicians.',
            'For Specialized Services (SS), the team may include a Clinical Coordinator, Behavioural & Developmental Aide, and up to four clinicians.',
            'Once the team is established and planning is complete, regular services begin.',
        ],
    },
    {
        id: 5,
        title: 'Progress Review & Ongoing Collaboration',
        paragraphs: [
            'Throughout services, the team communicates with your family, monitors progress toward identified goals, and reviews strategies as your child’s needs develop.',
            'Behavioural and Developmental Support (BDS) is typically provided over approximately six months. Toward the end of the service period, the family and service team review the child’s progress, current needs, and goals.',
        ],
        lead_in:
            'Depending on the child’s needs and the family’s circumstances, families may:',
        options: [
            'Renew BDS and continue with another service period when ongoing BDS support is appropriate.',
            'Transition to Specialized Services (SS) when the child requires a more intensive, multidisciplinary level of support and the family is ready to move forward.',
            'Adjust services or goals based on the child’s progress and current needs.',
        ],
        after: [
            'Specialized Services (SS) is typically provided over a 12-month period and includes ongoing multidisciplinary collaboration, service coordination, progress monitoring, and review of the child’s individualized goals.',
            'The appropriate next step is determined collaboratively based on the child’s needs, family priorities, and the services approved through FSCD.',
        ],
    },
];

/** One row of the BDS-versus-Specialized-Services comparison table. */
export interface ServiceComparisonRow {
    label: string;
    bds: string;
    ss: string;
}

export const ServiceComparison: ServiceComparisonRow[] = [
    {
        label: 'Service Length',
        bds: 'Typically 6 months',
        ss: 'Typically 12 months',
    },
    {
        label: 'Level of Support',
        bds: 'Less intensive',
        ss: 'More intensive and multidisciplinary',
    },
    {
        label: 'Clinical Team',
        bds: 'Up to 2 clinicians',
        ss: 'Up to 4 clinicians',
    },
    {
        label: 'Aide Support',
        bds: 'Behavioural & Developmental Aide',
        ss: 'Behavioural & Developmental Aide',
    },
    {
        label: 'Clinical Coordination',
        bds: '—',
        ss: 'Clinical Coordinator',
    },
    {
        label: 'Service Planning',
        bds: 'Individualized planning based on the child’s goals and approved services',
        ss: 'Coordinated multidisciplinary planning based on the child’s goals and approved services',
    },
    {
        label: 'Focus',
        bds: 'Behavioural and developmental needs requiring targeted support',
        ss: 'More complex needs requiring support across multiple areas of development',
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
        desc: 'We provide direct billing to FSCD for eligible approved services and follow applicable FSCD-approved rates. This helps simplify the billing process for families and reduces the need to submit reimbursement claims for covered services.',
        icon: 'Shield',
    },
    {
        id: 2,
        title: 'FSCD Expertise',
        desc: 'Our team is experienced in providing FSCD-funded services and understands the documentation, reporting, service planning, and collaboration requirements involved in BDS and Specialized Services.',
        icon: 'ReceiptText',
    },
    {
        id: 3,
        title: 'Multidisciplinary Team Approach',
        desc: 'Our professionals work collaboratively to provide coordinated support across different areas of a child’s development. By working toward shared goals, the team helps create greater consistency across services and everyday environments.',
        icon: 'Users2',
    },
    {
        id: 4,
        title: 'Individualized Support Plans',
        desc: 'Each child’s services are planned around their strengths, needs, goals, and family priorities. Depending on the service, this may include a Service Providers Program Plan (SPPP) or an Individualized Service Plan (ISP) that helps guide the team’s support and track progress over time.',
        icon: 'Target',
    },
    {
        id: 5,
        title: 'Family-Centred Care',
        desc: 'Families are important partners throughout service planning and delivery. We work closely with parents and caregivers to understand family priorities, share practical strategies, and support consistency across home and community settings.',
        icon: 'Heart',
    },
    {
        id: 6,
        title: 'Inclusive & Respectful Care',
        desc: 'We provide inclusive, respectful support that recognizes each family’s values, background, preferences, and goals. Our team works to create a welcoming environment where children and families feel heard, supported, and valued.',
        icon: 'Globe',
    },
    {
        id: 7,
        title: 'Clear & Ongoing Communication',
        desc: 'We maintain open communication with families throughout services by sharing updates, discussing progress, and keeping families informed about planning and next steps.',
        icon: 'MessageCircleIcon',
    },
    {
        id: 8,
        title: 'School & Community Collaboration',
        desc: 'When appropriate and with family consent, we collaborate with schools, childcare providers, and other community support to promote consistency across the environments that are important in a child’s everyday life.',
        icon: 'School',
    },
];
