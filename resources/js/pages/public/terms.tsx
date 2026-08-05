import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ReceiptText } from 'lucide-react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';

export default function Terms() {
    return (
        <>
            <Head title="Terms and Conditions" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <Button
                    asChild
                    variant="outline"
                    className="m-5 rounded-[5px] border border-primary text-primary md:mx-20 md:mt-20"
                >
                    <Link href="/">
                        <ArrowLeft /> Back to Home
                    </Link>
                </Button>

                <div className="rounded-md p-3 shadow-2xl md:m-20 md:p-10">
                    <div className="flex flex-row items-center gap-2 border-b pb-10">
                        <div className="rounded-sm bg-secondary-orange/5 p-5 text-primary">
                            <ReceiptText />
                        </div>

                        <div>
                            <p className="text-2xl text-primary">
                                Terms and Conditions
                            </p>
                            <p className="text-sm text-charcoal-gray">
                                Last Updated: November 14, 2025
                            </p>
                        </div>
                    </div>

                    <div className="max-h-[80%] overflow-auto scroll-smooth">
                        {/* Acceptance */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                1. Acceptance of Terms
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Welcome to Creative Abilities Therapy Services
                                ("Company", "we", "our", or "us"). By accessing
                                or using our services, website, or submitting
                                any forms, you agree to be bound by these Terms
                                and Conditions. If you do not agree to these
                                terms, please do not use our services.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                These Terms and Conditions govern your
                                relationship with Creative Abilities Therapy
                                Services and apply to all clients, parents,
                                guardians, and users of our therapy services.
                            </p>
                        </div>

                        {/* Services */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                2. Services Provided
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Creative Abilities Therapy Services provides
                                therapeutic and support services for children
                                and families, including but not limited to:
                            </p>

                            <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                <li>Occupational Therapy</li>
                                <li>Physiotherapy</li>
                                <li>Speech and Language Therapy</li>
                                <li>Behaviour Consulting</li>
                                <li>Psychological Supporty</li>
                                <li>Counselling Servicesy</li>
                                <li>Behavioural Aide Services</li>
                            </ul>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                All services are subject to availability and
                                appropriate therapist matching. We reserve the
                                right to determine service eligibility based on
                                clinical assessment and available resources.
                            </p>
                        </div>

                        {/* Client Requirements */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                3. Client Responsibilitiesd
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    3.1 Accurate Information
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Clients and guardians agree to provide
                                    accurate, complete, and current information
                                    in all intake forms, applications, and
                                    communications with our staff. Any changes
                                    to contact information, medical conditions,
                                    or relevant circumstances must be
                                    communicated promptly.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    3.2 Attendance and Punctuality
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Clients are expected to attend scheduled
                                    appointments on time. Late arrivals may
                                    result in shortened sessions, and the full
                                    session fee will still apply.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    3.3 Cancellation Polic
                                </p>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        <span className="font-bold">
                                            24-Hour Notice:
                                        </span>{' '}
                                        Appointments must be cancelled or
                                        rescheduled at least 24 hours in advance
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            {' '}
                                            Late Cancellations
                                        </span>
                                        : Cancellations made with less than 24
                                        hours notice may be subject to a
                                        cancellation fee equal to 50% of the
                                        session cost
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            No-Shows
                                        </span>
                                        : Failure to attend a scheduled
                                        appointment without prior notice will
                                        result in a full session fee charge
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Emergency Situations:
                                        </span>{' '}
                                        Exceptions may be made for documented
                                        emergencies or illness
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* Payment Terms */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                4. Payment Terms
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.1 Fees and Billing
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Service fees are based on the type of
                                    therapy, session duration, and therapist
                                    qualifications. Detailed fee schedules will
                                    be provided prior to commencing services.
                                    Payment is due at the time of service unless
                                    alternative arrangements have been made in
                                    writing.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.2 Accepted Payment Methods
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We accept cash, debit, credit cards,
                                    e-transfer, and direct billing to approved
                                    funding sources (where applicable).
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.3 Insurance and Funding
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    While we may provide direct billing services
                                    to certain funding sources, clients remain
                                    ultimately responsible for all fees. It is
                                    the client's responsibility to understand
                                    their insurance coverage and funding
                                    limitations.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.4 Outstanding Balances
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Services may be suspended if accounts are 30
                                    days or more overdue. A fee of $25 will be
                                    charged for NSF (non-sufficient funds)
                                    payments.
                                </p>
                            </div>
                        </div>

                        {/* Confidentiality */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                5. Confidentiality and Records
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                All client information is maintained in strict
                                confidence in accordance with applicable privacy
                                legislation and professional standards. Client
                                records are maintained securely and may only be
                                released with written consent, except where
                                required by law.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Therapists may consult with other professionals
                                regarding client care while maintaining
                                confidentiality. Clients will be informed of
                                such consultations where appropriate.
                            </p>

                            <p>
                                Please refer to our Privacy Policy for detailed
                                information about how we collect, use, and
                                protect your personal information.
                            </p>
                        </div>

                        {/* Professional */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                6. Professional Standards
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                All therapists and practitioners at Creative
                                Abilities Therapy Services are licensed,
                                certified, or registered with their respective
                                professional regulatory bodies. Our staff adhere
                                to professional codes of ethics and practice
                                standards
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Services are provided within the scope of
                                practice of the assigned therapist. Referrals to
                                other specialists may be recommended when
                                appropriate.
                            </p>
                        </div>

                        {/* Safety */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                7. Safety and Conduct
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.1 Safe Environment
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We are committed to providing a safe,
                                    respectful environment for all clients,
                                    families, and staff. Aggressive, abusive, or
                                    threatening behavior toward staff will not
                                    be tolerated and may result in immediate
                                    termination of services.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.2 Supervision Requirements
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Parents or guardians are required to remain
                                    on-site during therapy sessions unless
                                    alternative arrangements have been agreed
                                    upon with the therapist.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.3 Child Protection
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Our staff are mandated reporters under
                                    provincial child protection legislation. If
                                    there are reasonable grounds to believe a
                                    child is at risk of harm, we are legally
                                    required to report to the appropriate
                                    authorities.
                                </p>
                            </div>
                        </div>

                        {/* Liability */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                8. Liability and Limitations
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    8.1 Scope of Services
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    While we strive to provide high-quality
                                    therapeutic services, we cannot guarantee
                                    specific outcomes or results. Therapeutic
                                    progress depends on multiple factors
                                    including client engagement, family support,
                                    and individual circumstances.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    8.2 Medical Emergencies
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    In the event of a medical emergency during a
                                    session, we will contact emergency services
                                    and the client's emergency contacts. By
                                    using our services, you authorize us to seek
                                    emergency medical care if necessary.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    8.3 Limitation of Liability
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    To the fullest extent permitted by law,
                                    Creative Abilities Therapy Services shall
                                    not be liable for any indirect, incidental,
                                    consequential, or special damages arising
                                    from the use of our services
                                </p>
                            </div>
                        </div>

                        {/* Termination */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                9. Termination of Services
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    9.1 Client-Initiated Termination
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Clients may discontinue services at any time
                                    by providing written notice. Outstanding
                                    balances must be settled upon termination.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    9.2 Company-Initiated Termination
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We reserve the right to discontinue services
                                    in cases of:
                                </p>

                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        Non-payment or persistent late payment
                                    </li>
                                    <li>
                                        Repeated no-shows or late cancellations
                                    </li>
                                    <li>
                                        Behavior that compromises staff or
                                        client safety
                                    </li>
                                    <li>
                                        Non-compliance with treatment
                                        recommendations
                                    </li>
                                    <li>
                                        Circumstances where services are no
                                        longer clinically appropriate
                                    </li>
                                </ul>

                                <p className="leading-relaxed text-muted-foreground">
                                    Reasonable notice will be provided when
                                    possible, along with referral resources when
                                    appropriate.
                                </p>
                            </div>
                        </div>

                        {/* Electronic Communications */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                10. Communication and Technology
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    10.1 Electronic Communications
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We may communicate with clients via phone,
                                    email, text message, or secure client
                                    portal. By providing contact information,
                                    you consent to receive communications
                                    through these channels including appointment
                                    confirmations, service updates, and
                                    administrative notifications.
                                </p>

                                <p className="leading-relaxed text-muted-foreground">
                                    While we implement security measures,
                                    electronic communications carry inherent
                                    risks. Clients acknowledge these risks when
                                    choosing electronic communication methods.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    10.2 Online Forms and Document Submission
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Our website provides electronic intake forms
                                    and career application forms. By submitting
                                    forms electronically, you acknowledge that:
                                </p>

                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        Information is transmitted using secure
                                        encryption technology
                                    </li>
                                    <li>
                                        Form submissions generate automated
                                        email notifications to our
                                        administrative team
                                    </li>
                                    <li>
                                        All information provided will be stored
                                        in our secure database and document
                                        management system
                                    </li>
                                    <li>
                                        You have the legal authority to submit
                                        the information provided
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    10.3 File Uploads
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Certain forms allow you to upload documents
                                    such as resumes, cover letters,
                                    certifications, medical reports, FSCD
                                    approval letters, and assessments. By
                                    uploading files, you confirm that:
                                </p>

                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        You have the legal right to share the
                                        uploaded documents
                                    </li>
                                    <li>
                                        Documents are in acceptable formats
                                        (PDF, DOC, DOCX, JPG, PNG)
                                    </li>
                                    <li>
                                        Files do not contain viruses or
                                        malicious software
                                    </li>
                                    <li>
                                        Uploaded documents become part of your
                                        official record and are subject to our
                                        retention policies
                                    </li>
                                </ul>

                                <p className="leading-relaxed text-muted-foreground">
                                    We reserve the right to reject or remove
                                    uploaded files that are inappropriate,
                                    contain malware, or violate these Terms and
                                    Conditions.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    10.4 Admin Portal Access
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Our administrative staff use a secure portal
                                    to manage intake submissions, applications,
                                    and client records. Only authorized
                                    personnel have access to this system, and
                                    all access is logged for security purposes.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    10.5 Virtual Services
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We may offer virtual/telehealth services
                                    where appropriate. Technical requirements
                                    and limitations will be communicated prior
                                    to virtual sessions.
                                </p>
                            </div>
                        </div>

                        {/* Intellectual Property */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                11. Intellectual Property
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                All materials, resources, assessment tools, and
                                therapy programs developed or used by Creative
                                Abilities Therapy Services remain our
                                intellectual property. Materials provided to
                                clients for home use are licensed for personal,
                                non-commercial use only.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Clients may not reproduce, distribute, or create
                                derivative works from our proprietary materials
                                without written permission.
                            </p>
                        </div>

                        {/* Complaints */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                12. Complaints and Grievances
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We are committed to client satisfaction and
                                addressing concerns promptly. If you have
                                concerns about services:
                            </p>

                            <ul className="ml-4 list-inside list-decimal text-muted-foreground">
                                <li>
                                    First, discuss concerns directly with your
                                    therapist
                                </li>
                                <li>
                                    If unresolved, contact our Clinical Director
                                    or Management
                                </li>
                                <li>
                                    Submit a formal written complaint if
                                    necessary
                                </li>
                                <li>
                                    You may also contact the relevant
                                    professional regulatory bodyg
                                </li>
                            </ul>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We will investigate all complaints thoroughly
                                and respond within 10 business days.
                            </p>
                        </div>

                        {/* Amendments */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                13. Amendments to Terms
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                We reserve the right to modify these Terms and
                                Conditions at any time. Updated terms will be
                                posted on our website with the revision date.
                                Continued use of our services after changes
                                constitutes acceptance of the modified terms.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                Significant changes affecting existing clients
                                will be communicated via email or written
                                notice.
                            </p>
                        </div>

                        {/* Governing Law */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                14. Governing Law
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                These Terms and Conditions are governed by the
                                laws of the Province of Alberta, Canada. Any
                                disputes shall be resolved in the courts of
                                Alberta.
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                If any provision of these Terms is found to be
                                unenforceable, the remaining provisions shall
                                remain in full effect
                            </p>
                        </div>

                        {/* Contact */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                15. Contact Information
                            </p>

                            <p className="mt-3 leading-loose text-muted-foreground">
                                For questions regarding these Terms and
                                Conditions, please contact us:
                            </p>

                            <div className="rounded-sm bg-secondary-orange/5 p-5">
                                <p className="font-bold">
                                    Creative Abilities Therapy Services
                                </p>
                                <p>Email: info@creativeabilitiestherapy.ca</p>
                                <p>Phone: (587) 422-8780</p>
                                <p>Address: Calgary, Alberta, Canada</p>
                            </div>
                        </div>

                        <div className="mt-10 border-b pb-3">
                            <div className="rounded-sm bg-secondary-orange/5 p-5">
                                <p className="my-5 text-lg text-primary">
                                    Acknowledgment
                                </p>

                                <p>
                                    By using our services and submitting intake
                                    forms or applications, you acknowledge that
                                    you have read, understood, and agree to be
                                    bound by these Terms and Conditions. If you
                                    are submitting information on behalf of a
                                    minor, you confirm that you have the legal
                                    authority to do so and accept these terms on
                                    their behalf.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

Terms.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: false,
            title: 'Ready to Access FSCD Services?',
            desc: 'If your family is eligible for FSCD support and you are seeking specialized services for your child, contact Creative Abilities Therapy Services today. Our team will help you navigate the FSCD process, from application to service delivery, ensuring your child receives the best possible care.',
        }}
    >
        {page}
    </PublicLayout>
);
