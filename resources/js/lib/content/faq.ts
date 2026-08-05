/**
 * FAQ content, ported 1:1 from cats-frontend/src/json/faq.tsx.
 */

export interface FaqQuestion {
    question: string;
    answer: string;
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
                answer: 'We offer a comprehensive range of therapy and support services:\n\n• Speech and Language Therapy\n• Psychological Support\n• Counselling\n• Occupational Therapy\n• Physiotherapy\n• Behaviour Consulting\n• Behavioural Aide Services\n• Community Aide\n• Respite Aide\n\nEach service is tailored to meet the unique needs of children, youth, and families.',
            },
            {
                question: 'Do You Offer direct billing?',
                answer: 'Yes! We offer direct billing for clients with FSCD (Family Support for Children with Disabilities) contracts.\n\nDuring the intake process, you can upload your FSCD contract, and we will handle the billing directly with FSCD on your behalf.\n\nFor clients with insurance or private pay arrangements, we issue invoices at the end of the month. Receipts can be provided for you to submit to your insurance provider.',
            },
            {
                question: 'What are your rates',
                answer: 'We follow FSCD-approved rates for all services funded through Family Support for Children with Disabilities.\n\nFor private pay clients, rates vary depending on the type of service and the professional providing care. Please contact us directly for specific fee information and to discuss payment options.\n\nWe are committed to making our services accessible and will work with you to find the best funding and payment solution for your family.',
            },
            {
                question: 'What age groups do you work with?',
                answer: `Our primary focus is supporting children and youth ages 4 to 18.
                \n\nWe also provide select services for young adults and families, depending on the specific need and service type.
                \n\nDuring the intake process, we'll discuss your situation to ensure we can provide appropriate support for your family's needs.`,
            },
            {
                question:
                    'What if  i need to cancel or reschedule an appointment?',
                answer: `We understand that schedules can change. We ask that you provide at least 24 hours' notice if you need to cancel or reschedule an appointment.

                Cancellations made with less than 24 hours’ notice, or missed appointments with or without notice, may be subject to a cancellation fee.

                We appreciate your understanding, as this policy allows us to offer appointment times to other families in need and ensures our therapists can plan their schedules effectively.

                If you have an emergency or extenuating circumstances, please contact us as soon as possible, and we'll do our best to work with you.`,
            },
            {
                question: 'Where can services be provided?',
                answer: "We offer flexible service delivery to meet your family's needs and preferences:\n\n\t**•In-Home Services:** We can provide therapy in the comfort and familiarity of your own home.\n\n\t**•Community Settings:** Services can also be provided in community locations that support your child's goals.\n\n\t**•Online/Telehealth:** We offer secure virtual sessions for families who prefer or require remote service delivery.\n\nService location depends on FSCD funding requirements, therapist availability, and what works best for your child's therapeutic goals. We'll discuss options during the intake process.",
            },
            {
                question: "How do you track my child's progress?",
                answer: `Your therapist will:

\t•Establish clear, measurable goals with your family at the start of services
\t•Document progress notes after each session
 \t•We also provide quarterly progress updates as required by FSCD, along with review meetings to discuss achievements and \tadjust goals as needed
\t•Provide regular progress reports (typically quarterly or as required by FSCD)
\t•Schedule review meetings to discuss achievements and adjust goals as needed
You'll always be informed about your child's progress and involved in decision-making about their care.`,
            },
            {
                question: 'How often will we have Therapy sessions?',
                answer: "Session frequency is customized based on your child's needs, therapeutic goals, and funding availability.\n\nTypical schedules range from:\n\t**•Behavioural Aide Support:** We typically offer 2 to 5 sessions per week, depending on your available funding and your family’s availability.\n\t**•Therapy Support:** We typically offer 1 to 2 sessions per month, depending on your FSCD contract and your family’s availability.\n\t**•Insurance/Private Pay:** Depending on the family’s request.\n\nYour therapist will recommend an optimal schedule during the initial assessment and will adjust frequency as your child progresses toward their goals.",
            },
        ],
    },
    {
        category: 'FSCD',
        questions: [
            {
                question: 'What is FSCD and do I qualify?',
                answer:
                    '**What us FSCD?**\n\nFSCD stands for Family Support for Children with Disabilities.\n\n' +
                    "It's a provincial program run by the Government of Alberta to help families caring for a child with a disability.\n\n" +
                    'The goal is to provide support and services that help your child develop, participate at home and in the community, and help your family manage some of the extra costs.\n\n' +
                    '**Who Qualifies for FSCD?**\n\n' +
                    'To be eligible:\n\n' +
                    '**Age & Residency**\n' +
                    '\t •Your child must be under 18 years old.\n' +
                    '\t •The child must live in Alberta.\n' +
                    "\t •The person applying must be the child's guardian.\n\n" +
                    '**Citizenship / Immigration Status**\n' +
                    '\t•Your child must be a Canadian citizen or permanent resident.\n\n' +
                    '**Disability Requirement**\n' +
                    '\t • You need medical documentation from a qualified health professional confirming your child has a disability or is in the process of getting a diagnosis.\n' +
                    '\t•The disability should be chronic and developmental, physical, sensory, mental, or neurological in nature.\n' +
                    "\t• If your child's main need is just medical treatment (without major daily life impact), that may not qualify — unless the condition significantly limits daily living activities.\n\n" +
                    'For more details, visit: https://www.alberta.ca/fscd-eligibility',
            },
            {
                question: 'How do I apply for funding?',
                answer:
                    "Here's how to apply for FSCD funding, based on the Alberta government website:\n\n" +
                    '1. Apply online through the FSCD portal via your Alberta.ca account.\n\n' +
                    "2. If you can't apply online or need help, call Alberta Supports at 1-877-644-9992.\n\n" +
                    '3. Prepare the following documents to include with your application:\n\n' +
                    "\t•Proof of your child's citizenship or immigration status (e.g., birth certificate, passport, or permanent resident card)\n\n" +
                    "\t•Medical documentation from a qualified health professional that confirms your child's diagnosis or probable diagnosis\n\n" +
                    '\t•Proof of your relationship or guardianship of the child (if needed)\n\n' +
                    '4. Submit all supporting documents along with the application. A complete application includes the form + all required proof documents.\n\n' +
                    '5. After you apply, a Disability Services worker will review your application. They may:\n\n' +
                    '\t•Request more information, or\n\n' +
                    '\t•Explain why your child is not eligible, or\n\n' +
                    '\t•Confirm eligibility and discuss next steps, including supports and services.\n\n' +
                    'For more details, visit: https://www.alberta.ca/fscd-how-to-apply\n\n',
            },
        ],
    },
    {
        category: 'Getting Started',
        questions: [
            {
                question: 'How soon can we begin services?',
                answer: `In most cases, services can begin within 3 to 4 weeks after completing the intake process, depending on therapist availability and your family's schedule.

Once we receive your completed intake form and have had an initial phone call to assign you with the therapist, we will work to schedule your first appointment as soon as possible.

There may be a short waitlist for certain services. We will keep you informed and do our best to accommodate your needs promptly.`,
            },
            {
                question: 'What happens during the intake process?',
                answer:
                    'Our intake process is designed to be simple and supportive:\n\n' +
                    ' 1. **Complete the Intake Form:** Fill out our online form with information about your child, their needs, and your service preferences.\n\n' +
                    " 2. **Initial Phone Call:** We'll schedule a brief phone call to discuss your child's needs, answer any questions, and assign you with the most appropriate therapist or service provider.\n\n" +
                    ' 3. **Sign Consent & Service Agreement:** Review and sign consent documents to allow us to provide services and communicate with relevant parties (schools, doctors, etc.) as needed.\n\n' +
                    " 4. **Begin Services:** Once assigned, we'll schedule your first session and begin working together toward your goals.\n\n" +
                    'All information you provide is kept strictly confidential and is used only to ensure we provide the best possible care for your family.',
            },
            {
                question: 'What qualifications do your therapists have?',
                answer:
                    'All our therapists are highly qualified, registered professionals with the appropriate credentials for their discipline:\n\n' +
                    '\t•**Speech-Language Pathologists:** Registered with Alberta College of Speech-Language Pathologists and Audiologists (ACSLPA)\n\n' +
                    '\t•**Psychologists:**  Registered with the College of Alberta Psychologists (CAP)\n\n' +
                    '\t•**Occupational Therapists:**  Registered with the Alberta College of Occupational Therapists (ACOT)\n\n' +
                    '\t•**Physiotherapists:**  Registered with the College of Physiotherapists of Alberta (CPTA)\n\n' +
                    '\t•**Behaviour Consultants:**  Board Certified Behavior Analysts (BCBA) or equivalent credentials\n\n' +
                    'In addition to professional registration, our team members have specialized training in pediatric therapy and working with children with diverse needs. Many hold additional certifications in specific therapeutic approaches.',
            },
        ],
    },
    {
        category: 'Privacy & Safety',
        questions: [
            {
                question: "How do you protect my family's privacy?",
                answer:
                    "We take privacy and confidentiality very seriously and follow strict protocols to protect your family's personal and health information:\n\n" +
                    "\t•**Compliance:** We comply with Alberta's Health Information Act (HIA) and Personal Information Protection Act (PIPA)\n\n" +
                    '\t•**Secure storage:** All records are stored securely with encryption and restricted access\n\n' +
                    "\t•**Limited access: **Only authorized team members directly involved in your child's care can access records\n\n" +
                    '\t•**Consent required:**We only share information with third parties (schools, doctors, FSCD) with your written consent\n\n' +
                    '\t•**Staff training:** All team members receive training on privacy and confidentiality requirements\n\n' +
                    '\t•**Regular audits: **We conduct periodic reviews to ensure compliance with privacy standards\n\n' +
                    'For complete details, please review our Privacy Policy.',
            },
            {
                question: 'Do you share information with schools or doctors?',
                answer:
                    'We only share information with schools, doctors, or other professionals when you provide explicit written consent.\n\n' +
                    'With your permission, we may share information to:\n\n' +
                    "\t•**Coordinate care:** Work collaboratively with your child's healthcare team for comprehensive support\n\n" +
                    '\t•**School school support:** We collaborate with the school team to ensure a common approach in intervention.\n\n' +
                    '\t•**FSCD reporting: **Submit required progress reports and documentation for funding purposes\n\n' +
                    '\t•**Obtain records: **Request assessments or medical information that inform treatment planning\n\n' +
                    "During intake, we'll ask you to specify which individuals or organizations you authorize us to communicate with. You can update these permissions at any time, and we'll always confirm with you before sharing sensitive information.",
            },
        ],
    },
];
