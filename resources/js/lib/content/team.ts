/**
 * Team page content. The founder's own profile lives in
 * `@/components/founder-profile` because it is a one-off layout with a photo
 * and credentials; the remaining leadership profiles share one shape and are
 * listed here.
 */

export interface LeaderRole {
    title: string;
    description: string;
}

export interface LeaderEntry {
    id: number;
    name: string;
    position: string;
    /** Portrait; currently a generated placeholder until real photos land. */
    photo: string;
    tagline: string;
    /** Body copy, rendered as consecutive paragraphs. */
    paragraphs: string[];
    roles: LeaderRole[];
    commitmentTitle: string;
    commitment: string;
    contactLabel: string;
}

export const Leaders: LeaderEntry[] = [
    {
        id: 1,
        name: 'Bernard Lerit',
        position: 'Operations & Chief Financial Officer',
        photo: '/images/leader-placeholder-bernard.svg',
        tagline: 'Supporting the Operations Behind Quality Care',
        paragraphs: [
            'Bernard Lerit serves as the Operations & Chief Financial Officer of Creative Abilities Therapy Services. He plays an important role in supporting the organization’s day-to-day operations, financial administration, and internal systems to help ensure services are delivered efficiently and responsibly.',
            'Bernard works closely with the leadership and administrative teams to support organizational planning, financial processes, contractor administration, and the systems that help CATS operate smoothly. His work behind the scenes helps ensure that clinicians, support professionals, and families have the administrative foundation needed for effective service delivery.',
            'With a strong focus on organization, accountability, and continuous improvement, Bernard helps oversee the operational and financial processes that support the growth and sustainability of Creative Abilities Therapy Services.',
        ],
        roles: [
            {
                title: 'Operations Management',
                description:
                    'Supporting the day-to-day operational needs of the organization and helping maintain effective internal systems and processes.',
            },
            {
                title: 'Financial Oversight',
                description:
                    'Overseeing financial administration, invoicing processes, payments, and other financial responsibilities that support the organization’s services.',
            },
            {
                title: 'Administrative Systems',
                description:
                    'Helping develop and maintain organized processes that support contractors, clinicians, families, and the administrative team.',
            },
            {
                title: 'Organizational Planning',
                description:
                    'Working collaboratively with leadership to support the continued development, efficiency, and sustainability of CATS.',
            },
            {
                title: 'Team Support',
                description:
                    'Supporting the systems and processes that allow CATS professionals to focus on providing quality services to children and families.',
            },
        ],
        commitmentTitle: 'Commitment to Creative Abilities',
        commitment:
            'Bernard is committed to building strong operational and financial systems that support the mission of Creative Abilities Therapy Services. Through thoughtful planning, organization, and collaboration, he helps create a strong foundation that allows CATS to continue growing while maintaining its commitment to children and families.',
        contactLabel: 'Contact Bernard',
    },
    {
        id: 2,
        name: 'Bryan Lerit',
        photo: '/images/leader-placeholder-bryan.svg',
        position:
            'Operations & Program Lead, Behavioural & Respite Support Services',
        tagline: 'Connecting Families, Support Teams & Programs',
        paragraphs: [
            'Bryan Lerit serves as the Operations & Program Lead for Behavioural & Respite Support Services at Creative Abilities Therapy Services. He supports the day-to-day coordination of behavioural, developmental, community, and respite services while helping ensure families and support professionals have the information and resources they need throughout service delivery.',
            'Bryan oversees and coordinates Behavioural & Developmental Aides, Community Aides, and Respite Aides. His role includes supporting contractor onboarding, aide coordination, scheduling, communication, program operations, and the organization of services for children and families.',
            'Working closely with families, aides, clinicians, and the leadership team, Bryan helps strengthen communication and coordination across services. His focus is on creating organized, responsive systems that support positive experiences for both families and the professionals working with them.',
        ],
        roles: [
            {
                title: 'Behavioural & Developmental Aide Services',
                description:
                    'Supporting the coordination of Behavioural & Developmental Aides and helping connect families with appropriate support based on service needs and availability.',
            },
            {
                title: 'Community & Respite Support Services',
                description:
                    'Coordinating Community and Respite Aide Services and supporting the day-to-day operations of respite and community-based programming.',
            },
            {
                title: 'Program Operations',
                description:
                    'Supporting the planning, organization, scheduling, and ongoing coordination of programs and support services.',
            },
            {
                title: 'Contractor Onboarding & Coordination',
                description:
                    'Helping new aides through onboarding and supporting contractors with the information, processes, and resources needed to begin providing services.',
            },
            {
                title: 'Family & Team Communication',
                description:
                    'Supporting communication between families, aides, clinicians, and the leadership team to help services remain organized and coordinated.',
            },
            {
                title: 'Service Delivery Support',
                description:
                    'Helping manage the operational details behind behavioural, developmental, community, and respite services so support can be delivered effectively.',
            },
        ],
        commitmentTitle: 'Commitment to Children & Families',
        commitment:
            'Bryan is committed to creating organized, welcoming, and responsive support experiences for children, families, and the CATS team. Through communication, coordination, and program development, he helps ensure that families can access services while aides and support professionals have the structure they need to provide meaningful care.',
        contactLabel: 'Contact Bryan',
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
        description:
            'We stay current with best practices through ongoing professional development and training.',
    },
];
