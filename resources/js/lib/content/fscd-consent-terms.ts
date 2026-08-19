/**
 * Full terms behind each of the three required FSCD consents on the intake
 * form. The short label is what sits beside the checkbox; the clauses are
 * what the applicant must scroll through before the checkbox unlocks.
 *
 * Unlike the `consent_documents` records these are fixed program terms with
 * no per-applicant substitution, so they live in content rather than the
 * database — the same treatment as the FSCD page copy in `fscd.ts`.
 */
export interface FscdConsentClause {
    heading: string;
    body: string;
}

export interface FscdConsentTerms {
    /** Also the checkbox's DOM id. */
    id: string;
    /** Sentence shown next to the checkbox. */
    label: string;
    /** Heading of the review modal. */
    title: string;
    clauses: FscdConsentClause[];
}

export const fscdConsentTerms: FscdConsentTerms[] = [
    {
        id: 'consentFSCD1',
        label: 'I consent to Creative Abilities Therapy Services communicating with my FSCD worker regarding services, progress, and billing',
        title: 'Consent to Communicate with Your FSCD Worker',
        clauses: [
            {
                heading: 'What you are agreeing to',
                body: 'You authorize Creative Abilities Therapy Services to speak and correspond directly with the Family Support for Children with Disabilities (FSCD) caseworker named on your application about your child’s services under your FSCD contract.',
            },
            {
                heading: 'What may be discussed',
                body: 'Communication is limited to what the FSCD program needs in order to fund and oversee your child’s services: the services approved and delivered, scheduling and attendance, your child’s progress toward the goals in the service plan, changes to the team supporting your child, and billing or invoicing tied to your FSCD contract.',
            },
            {
                heading: 'What is not shared',
                body: 'We do not discuss matters unrelated to your FSCD-funded services, and we do not share information about other family members who are not part of your child’s service plan.',
            },
            {
                heading: 'Who may communicate',
                body: 'Only the clinicians, clinical coordinators, and administrative staff directly involved in your child’s care or billing will communicate with your FSCD caseworker. All staff are bound by our confidentiality policies and by Alberta’s Health Information Act and Personal Information Protection Act.',
            },
            {
                heading: 'How long this consent lasts',
                body: 'This consent remains in effect for the duration of your FSCD contract with us, unless you withdraw it sooner.',
            },
            {
                heading: 'Withdrawing your consent',
                body: 'You may withdraw this consent at any time by contacting us in writing. Withdrawal takes effect once we receive it and does not undo communication that already took place. Because FSCD requires provider communication as a condition of funding, withdrawing this consent may mean your services can no longer be funded through FSCD.',
            },
        ],
    },
    {
        id: 'consentFSCD2',
        label: 'I consent to sharing reports and session notes with the FSCD program as required',
        title: 'Consent to Share Reports and Session Notes with FSCD',
        clauses: [
            {
                heading: 'What you are agreeing to',
                body: 'You authorize Creative Abilities Therapy Services to release your child’s clinical documentation to the FSCD program where the program requires it as a condition of funding.',
            },
            {
                heading: 'What documentation is covered',
                body: 'This includes assessment and re-assessment reports, individualized service plans and goal updates, session notes and service logs, progress summaries and discharge reports, and attendance records supporting the hours we invoice.',
            },
            {
                heading: 'Why FSCD requires it',
                body: 'FSCD uses this documentation to confirm that approved services were delivered, to review your child’s progress, and to make decisions about renewing or adjusting your contract. Without it, the program may decline to fund the services provided.',
            },
            {
                heading: 'Your right to review',
                body: 'You may request a copy of any report or note about your child before or after it is sent, and you may ask us to correct factual errors in your child’s record.',
            },
            {
                heading: 'How information is transmitted',
                body: 'Documentation is sent through the secure channels the FSCD program designates. We keep a record of what was released and when.',
            },
            {
                heading: 'Withdrawing your consent',
                body: 'You may withdraw this consent in writing at any time. Withdrawal applies to future releases only and does not retrieve documentation already provided to FSCD. It may also end your eligibility for FSCD-funded services with us.',
            },
        ],
    },
    {
        id: 'consentFSCD3',
        label: 'I understand I am responsible for any costs not approved by FSCD (e.g., top-ups, cancellations)',
        title: 'Acknowledgement of Costs Not Covered by FSCD',
        clauses: [
            {
                heading: 'What you are acknowledging',
                body: 'FSCD funds only the services, hours, and rates set out in your approved contract. You are personally responsible for any amount that falls outside that approval.',
            },
            {
                heading: 'Rate top-ups',
                body: 'Where our published rate for a service exceeds the rate FSCD approves, the difference is billed to you unless we have agreed otherwise in writing before the service begins.',
            },
            {
                heading: 'Sessions beyond your approved hours',
                body: 'If you request sessions after your approved hours for the contract period are used, or before a renewal is approved, those sessions are billed to you at our private rate.',
            },
            {
                heading: 'Late cancellations and missed appointments',
                body: 'FSCD does not fund appointments cancelled with less than the notice set out in our cancellation policy, nor appointments where no one attends. These are billed to you at the full session rate.',
            },
            {
                heading: 'Services outside your contract',
                body: 'Assessments, reports, consultations, or therapy types that are not listed in your FSCD approval are billed to you directly, and we will confirm the cost with you before proceeding.',
            },
            {
                heading: 'Notice and payment',
                body: 'We will tell you before delivering any service we expect FSCD will not cover. Invoices for uncovered amounts are issued to you and are due on the terms shown on the invoice.',
            },
            {
                heading: 'If FSCD declines a claim',
                body: 'If FSCD denies or reverses funding for a service already delivered, the amount becomes payable by you. We will share the reason given and support you in appealing the decision where an appeal is available.',
            },
        ],
    },
];
