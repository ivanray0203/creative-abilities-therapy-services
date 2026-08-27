/**
 * FAQ content. Answers use the lightweight markup that `public/faq`'s
 * `renderAnswer` understands: newlines split paragraphs, `**bold**` emphasises,
 * and a leading tab indents a bullet.
 */

export interface FaqQuestion {
    question: string;
    answer: string;
    /** Optional call-to-action rendered under the answer. */
    link?: { label: string; href: string };
}

export interface FaqCategory {
    category: string;
    questions: FaqQuestion[];
}

export const faqData: FaqCategory[] = [
    {
        category: 'Services & Billing',
        questions: [
            {
                question: 'What services do you offer?',
                answer: 'Creative Abilities Therapy Services offers Speech-Language Therapy, Psychology Services & Counselling, Occupational Therapy, Physiotherapy, Behavioural Therapy & Consulting, Behavioural & Developmental Aide Services, and Community & Respite Aide Services. Services are individualized based on each child’s strengths, needs, goals, and family priorities.',
            },
            {
                question: 'Do you offer direct billing?',
                answer: 'Yes. For eligible FSCD-funded services, Creative Abilities Therapy Services provides direct billing to FSCD at applicable FSCD-approved rates. For private insurance, direct billing may be available depending on the insurance provider and plan. When direct billing is not available, we can provide appropriate invoices or receipts for submission to your insurance provider.',
            },
            {
                question: 'What are your rates?',
                answer: 'Rates vary depending on the service, funding source, and type of support provided. For eligible FSCD-funded services, we follow applicable FSCD-approved rates. Private-pay rates may vary by service. Please contact our team for current pricing or questions about your specific service needs.',
            },
            {
                question: 'What age groups do you work with?',
                answer: 'Creative Abilities Therapy Services primarily supports children and youth ages 3–17. Services are individualized based on each child’s strengths, needs, goals, and family priorities.',
            },
            {
                question:
                    'What if I need to cancel or reschedule an appointment?',
                answer: 'If you need to cancel or reschedule a session, please provide at least 24 hours’ notice whenever possible. If a cancellation is made with less than 24 hours’ notice and the session cannot be rescheduled, the applicable cancellation fee or service rate may be charged. If the session can be rescheduled, no cancellation fee will apply.',
            },
            {
                question: 'Where can services be provided?',
                answer: 'Services may be provided in the home, community, through secure virtual sessions, and in other appropriate settings depending on the service, your child’s needs, and the service plan. Availability may vary by discipline and funding arrangement.',
            },
            {
                question: 'How do you track my child’s progress?',
                answer: 'Progress is monitored throughout services using observations, documentation, goal reviews, and ongoing communication with families and the service team. Goals and strategies may be adjusted over time based on your child’s progress, strengths, needs, and changing priorities.',
            },
            {
                question: 'How often will we have therapy sessions?',
                answer:
                    'Session frequency depends on the type of service your child is receiving:\n\n' +
                    '\t•**Behavioural and Developmental Support (BDS):** typically 1–2 clinician sessions per month\n' +
                    '\t•**Specialized Services (SS):** typically 2–4 clinician sessions per month\n' +
                    '\t•**Private Services:** scheduled based on the family’s request, clinician availability, and the child’s needs\n\n' +
                    'The exact frequency may vary depending on the child’s goals, approved services, and service plan.',
            },
        ],
    },
    {
        category: 'FSCD',
        questions: [
            {
                question: 'What is FSCD and do I qualify?',
                answer:
                    'Family Support for Children with Disabilities (FSCD) is a Government of Alberta program that provides funding and support to eligible families of children with disabilities. Eligibility is determined directly by FSCD based on the child’s age, residency, disability-related needs, and impact on daily functioning.\n\n' +
                    'Creative Abilities Therapy Services does not determine FSCD eligibility. Families must apply directly through FSCD. If your child is approved and you are looking for a service provider, CATS can provide eligible BDS and Specialized Services included in your FSCD agreement.',
            },
            {
                question: 'How do I apply for FSCD funding?',
                answer:
                    'Families apply directly through the Government of Alberta’s Family Support for Children with Disabilities (FSCD) program. FSCD will review your application and determine your child’s eligibility and approved support.\n\n' +
                    'Once your child has been approved, you may choose Creative Abilities Therapy Services as your service provider for eligible services included in your FSCD agreement.',
                link: {
                    label: 'Learn More About FSCD Eligibility',
                    href: 'https://www.alberta.ca/fscd-eligibility',
                },
            },
        ],
    },
    {
        category: 'Getting Started',
        questions: [
            {
                question: 'How soon can we begin services?',
                answer:
                    'Service start times vary depending on the type of service requested, provider availability, your location, family scheduling, and funding or service requirements. Once your intake is completed, our team will review your information and begin working to connect your family with the appropriate service provider.\n\n' +
                    'We will keep you informed throughout the process and contact you once an appropriate provider is available.',
            },
            {
                question: 'What happens during the intake process?',
                answer:
                    'The intake process helps us learn more about your child, your family’s needs, and the services you are looking for. After you complete the intake form, our team reviews the information, connects with you to discuss next steps, and begins coordinating the appropriate service provider or team based on availability, location, funding, and your child’s needs.\n\n' +
                    'For FSCD-funded services, we also review the services included in your agreement so we can begin planning the appropriate BDS or Specialized Services supports.',
            },
            {
                question: 'What qualifications do your therapists have?',
                answer:
                    'Our regulated clinicians meet the education, registration, and professional requirements of their respective Alberta regulatory colleges:\n\n' +
                    '\t•**Occupational Therapists** — Alberta College of Occupational Therapists (ACOT)\n' +
                    '\t•**Speech-Language Pathologists** — Alberta College of Speech-Language Pathologists and Audiologists (ACSLPA)\n' +
                    '\t•**Physiotherapists** — College of Physiotherapists of Alberta (CPTA)\n' +
                    '\t•**Psychologists** — College of Alberta Psychologists (CAP)\n\n' +
                    'Our team also includes Behavioural Consultants and Behavioural & Developmental Aides with education, training, and experience relevant to their roles. All service providers are expected to work within their scope of practice and follow applicable professional and organizational standards.',
            },
        ],
    },
    {
        category: 'Privacy & Safety',
        questions: [
            {
                question: 'How do you protect my family’s privacy?',
                answer: 'We take your family’s privacy and confidentiality seriously. Personal and health information is handled in accordance with applicable privacy requirements, professional standards, and Creative Abilities Therapy Services policies. Information is only accessed or shared by appropriate team members when needed for service delivery, documentation, billing, or other authorized purposes.',
            },
            {
                question: 'Do you share information with schools or doctors?',
                answer: 'We only share information with schools, physicians, childcare providers, or other professionals when appropriate and with the family’s consent, unless disclosure is otherwise required or permitted by law. When information is shared, we limit it to what is relevant for coordination of services and supporting the child’s needs.',
            },
        ],
    },
];
