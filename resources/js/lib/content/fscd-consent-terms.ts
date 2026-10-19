/**
 * Full terms behind the required FSCD consent on the intake form. The short
 * label is what sits beside the checkbox; the clauses are what the applicant
 * can review from the "Terms and Conditions" link beside it.
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
];
