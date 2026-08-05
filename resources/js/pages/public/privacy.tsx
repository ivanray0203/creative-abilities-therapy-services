import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Shield } from 'lucide-react';

import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';

export default function Privacy() {
    return (
        <>
            <Head title="Privacy Policy" />

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

                <div className="rounded-md p-5 shadow-2xl md:m-20 md:p-10">
                    <div className="flex flex-row items-center gap-2 border-b pb-10">
                        <div className="rounded-sm bg-secondary-orange/5 p-5 text-primary">
                            <Shield />
                        </div>

                        <div>
                            <p className="text-2xl text-primary">
                                Privacy Policy
                            </p>
                            <p className="text-sm text-charcoal-gray">
                                Last Updated: November 14, 2025
                            </p>
                        </div>
                    </div>

                    <div className="max-h-[80%] overflow-auto scroll-smooth">
                        {/* Introduction */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                1. Introduction
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                1. Introduction Creative Abilities Therapy
                                Services ("we", "our", or "us") is committed to
                                protecting the privacy and confidentiality of
                                personal information entrusted to us. This
                                Privacy Policy explains how we collect, use,
                                disclose, and safeguard your information when
                                you use our services or visit our website.
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                We comply with the Personal Information
                                Protection Act (PIPA) of Alberta, the Personal
                                Information Protection and Electronic Documents
                                Act (PIPEDA), and all applicable provincial and
                                federal privacy legislation.
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                By using our services, you consent to the
                                collection, use, and disclosure of your personal
                                information as described in this Privacy Policy.
                            </p>
                        </div>

                        {/* Information We Collect */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                2. Information We Collect
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    2.1 Personal Information
                                </p>

                                <p className="leading-relaxed text-muted-foreground">
                                    We collect personal information that you
                                    provide directly to us, including but not
                                    limited to:
                                </p>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        <span className="font-bold">
                                            Demographic Information:
                                        </span>{' '}
                                        Date of birth, age, gender, language
                                        preferences
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Family Information:
                                        </span>{' '}
                                        Parent/guardian information, emergency
                                        contacts, relationships
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            {' '}
                                            Educational Information
                                        </span>
                                        : School name, grade level, educational
                                        history
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Medical and Health Information:{' '}
                                        </span>
                                        Diagnoses, medical conditions,
                                        developmental history, medications,
                                        allergies
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Treatment Information:
                                        </span>{' '}
                                        Therapy goals, progress notes,
                                        assessment results, treatment plans
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Financial Information:
                                        </span>{' '}
                                        Billing details, insurance information,
                                        payment methods, funding sources
                                        (including FSCD contract numbers and
                                        details)
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Behavioral Information:
                                        </span>{' '}
                                        Observations, behavioral assessments,
                                        functional abilities
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Employment Information:
                                        </span>{' '}
                                        For job applicants - resumes, cover
                                        letters, references, certifications,
                                        employment history
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    2.2 Uploaded Documents
                                </p>

                                <p className="leading-relaxed text-muted-foreground">
                                    Through our online forms, you may upload
                                    documents including:
                                </p>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        FSCD approval letters and contract
                                        documentation
                                    </li>
                                    <li>Medical reports and assessments</li>
                                    <li>
                                        Doctor's prescriptions and referrals
                                    </li>
                                    <li>Consent forms and signed agreements</li>
                                    <li>Insurance documentation</li>
                                    <li>
                                        Progress reports and educational records
                                    </li>
                                    <li>
                                        Resumes, cover letters, and professional
                                        certifications (for job applicants)
                                    </li>
                                </ul>

                                <p className="mt-3 leading-relaxed text-muted-foreground">
                                    All uploaded files are scanned for security
                                    purposes and stored in our encrypted
                                    document management system.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    2.3 Information Collected Automatically
                                </p>

                                <p className="leading-relaxed text-muted-foreground">
                                    When you visit our website or use our online
                                    portals, we may automatically collect:
                                </p>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        Device information (IP address, browser
                                        type, operating system)
                                    </li>
                                    <li>
                                        Usage data (pages visited, time spent,
                                        navigation patterns)
                                    </li>
                                    <li>
                                        Cookie data (preferences, session
                                        information)
                                    </li>
                                    <li>
                                        Login information and access logs for
                                        admin portal users
                                    </li>
                                    <li>Form submission data and timestamps</li>
                                </ul>
                            </div>
                        </div>

                        {/* How we use your Information */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                3. How we use your information
                            </p>

                            <p className="leading-relaxed text-muted-foreground">
                                We collect and use personal information for the
                                following purposes:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        <span className="font-bold">
                                            Service Delivery:
                                        </span>{' '}
                                        To provide therapeutic services, match
                                        clients with appropriate therapists,
                                        develop treatment plans, and monitor
                                        progress
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            {' '}
                                            Communication:{' '}
                                        </span>
                                        To contact you regarding appointments,
                                        services, updates, and respond to
                                        inquiries
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Administrative Purposes:
                                        </span>{' '}
                                        To maintain client records, schedule
                                        appointments, process payments, and
                                        manage our practice
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Billing and Insurance:{' '}
                                        </span>{' '}
                                        To process payments, submit insurance
                                        claims, and coordinate with funding
                                        sources
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Legal and Regulatory Compliance:
                                        </span>{' '}
                                        To comply with professional standards,
                                        legal obligations, and reporting
                                        requirements
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Quality Improvement:
                                        </span>{' '}
                                        To evaluate and improve our services,
                                        training programs, and client
                                        satisfaction
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Safety and Protection:
                                        </span>{' '}
                                        To ensure the safety of clients,
                                        families, and staff, and to fulfill
                                        child protection obligations
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* Information Sharing and Disclosure */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                4. Information Sharing and Disclosure
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.1 Consent-Based Sharing
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We will share your personal information only
                                    with your written consent, except where
                                    permitted or required by law. With consent,
                                    we may share information with:
                                </p>

                                <div>
                                    <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                        <li>
                                            Other healthcare providers involved
                                            in your child's care
                                        </li>
                                        <li>
                                            Schools and educational institutions
                                            for coordination of services
                                        </li>
                                        <li>
                                            Insurance companies and funding
                                            sources for billing purposes
                                        </li>
                                        <li>
                                            Family members or authorized
                                            representatives you designate
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.2 Legal Requirements
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We may disclose personal information without
                                    consent when required or permitted by law,
                                    including:
                                </p>

                                <div>
                                    <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                        <li>
                                            Child protection concerns (mandatory
                                            reporting obligations)
                                        </li>
                                        <li>
                                            Court orders, subpoenas, or legal
                                            proceedings
                                        </li>
                                        <li>
                                            Regulatory body investigations or
                                            professional conduct reviews
                                        </li>
                                        <li>
                                            Imminent risk of serious harm to
                                            self or others
                                        </li>
                                        <li>
                                            Public health emergencies or disease
                                            reporting requirements
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    4.3 Service Providers
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    We may engage third-party service providers
                                    to support our operations, including:
                                </p>

                                <div>
                                    <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                        <li>
                                            Electronic medical records and
                                            practice management systems
                                        </li>
                                        <li>
                                            Billing and payment processing
                                            services
                                        </li>
                                        <li>
                                            IT support and cloud storage
                                            providers
                                        </li>
                                        <li>
                                            Professional liability insurance
                                            providers
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div>
                                <p className="mt-3 leading-relaxed text-muted-foreground">
                                    All service providers are contractually
                                    obligated to maintain confidentiality and
                                    use information only for specified purposes.
                                </p>
                            </div>
                        </div>

                        {/* Data Security */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                5. Data Security
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                We implement comprehensive security measures to
                                protect your personal information from
                                unauthorized access, use, disclosure,
                                alteration, or destruction:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        <span className="font-bold">
                                            Physical Security:
                                        </span>{' '}
                                        Secure facilities with controlled
                                        access, locked filing cabinets for paper
                                        records
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            {' '}
                                            Technical Security:{' '}
                                        </span>
                                        Encryption, firewalls, secure servers,
                                        password protection, regular security
                                        updates
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Organizational Security:
                                        </span>{' '}
                                        Staff training on privacy and
                                        confidentiality, confidentiality
                                        agreements, access controls, regular
                                        audits
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Electronic Records:{' '}
                                        </span>{' '}
                                        Encrypted databases, secure cloud
                                        storage, regular backups, access logging
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Admin Portal Security:
                                        </span>{' '}
                                        Role-based access controls, multi-factor
                                        authentication options, session
                                        management, activity logging, and
                                        regular security audits ensure only
                                        authorized personnel can access
                                        sensitive information
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Document Upload Security:
                                        </span>{' '}
                                        All uploaded files are scanned for
                                        malware, encrypted during transmission
                                        and storage, and accessible only to
                                        authorized personnel with legitimate
                                        business need
                                    </li>
                                </ul>
                            </div>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                While we implement robust security measures, no
                                system is completely secure. We cannot guarantee
                                absolute security of information transmitted
                                electronically.
                            </p>
                        </div>

                        {/* Admin Portal And Access Controls */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                5A. Admin Portal and Access Controls
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Our administrative portal is used by authorized
                                staff to manage client information, intake
                                submissions, and job applications. We maintain
                                strict controls over portal access:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        <span className="font-bold">
                                            Access Authorization:
                                        </span>{' '}
                                        Secure Only authorized employees with
                                        legitimate business need are granted
                                        portal access
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            {' '}
                                            User Authentication:{' '}
                                        </span>
                                        All users must authenticate with secure
                                        credentials before accessing the system
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Activity Logging:{' '}
                                        </span>{' '}
                                        All portal activities are logged,
                                        including user logins, data access,
                                        modifications, and document
                                        uploads/downloads
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Role-Based Permissions:{' '}
                                        </span>{' '}
                                        Users are assigned specific roles with
                                        permissions appropriate to their job
                                        functions
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Regular Audits:
                                        </span>{' '}
                                        Access logs are regularly reviewed to
                                        ensure appropriate use and detect any
                                        unauthorized access attempts
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Session Timeouts:
                                        </span>{' '}
                                        User sessions automatically expire after
                                        periods of inactivity to prevent
                                        unauthorized access
                                    </li>
                                </ul>
                            </div>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Admin portal users are bound by confidentiality
                                agreements and receive regular privacy and
                                security training.
                            </p>
                        </div>

                        {/* Email Notifications and Automated Processing */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                5B. Email Notifucations and Automated Processing
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                When you submit forms through our website,
                                automated email notifications are sent to our
                                administrative team
                                (info@creativeabilitiestherapy.ca) containing:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>Summary of submitted information</li>
                                    <li>Notification of uploaded documents</li>
                                    <li>Timestamp and submission details</li>
                                    <li>
                                        Applicant/client contact information
                                    </li>
                                </ul>
                            </div>
                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                These notifications are:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        Sent via secure email protocols where
                                        possible
                                    </li>
                                    <li>
                                        Accessed only by authorized personnel
                                    </li>
                                    <li>
                                        Retained according to our data retention
                                        policies
                                    </li>
                                    <li>
                                        Subject to the same confidentiality
                                        standards as other client information
                                    </li>
                                </ul>
                            </div>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Email is not a completely secure communication
                                method. We recommend not including highly
                                sensitive personal information in email
                                communications unless using encrypted email
                                services.
                            </p>
                        </div>

                        {/* Data Retention */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                6. Data Retention
                            </p>

                            <p className="leading-relaxed text-muted-foreground">
                                We retain personal information only as long as
                                necessary to fulfill the purposes for which it
                                was collected and to comply with legal and
                                regulatory requirements:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        <span className="font-bold">
                                            Physical Security:
                                        </span>{' '}
                                        Secure facilities with controlled
                                        access, locked filing cabinets for paper
                                        records
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            {' '}
                                            Client Records:
                                        </span>
                                        Retained for a minimum of 10 years after
                                        the last service date (or until the
                                        client reaches age 21, whichever is
                                        longer), as required by professional
                                        regulatory bodies
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Financial Records:{' '}
                                        </span>{' '}
                                        Retained for 7 years for tax and
                                        accounting purposes
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Intake Forms and Applications{' '}
                                        </span>{' '}
                                        Retained as part of the client record
                                    </li>
                                    <li>
                                        <span className="font-bold">
                                            Marketing Communications:
                                        </span>{' '}
                                        Retained until consent is withdrawn
                                    </li>
                                </ul>
                            </div>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                After the retention period, personal information
                                is securely destroyed using methods such as
                                shredding (paper records) or secure deletion
                                (electronic records).
                            </p>
                        </div>

                        {/* Privacy Rights */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                7. Your Privacy Rights
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.1 Access
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    You have the right to access your personal
                                    information in our custody or control. You
                                    may request a copy of your records, subject
                                    to limited exceptions under privacy
                                    legislation.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.2 Correction
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    You may request corrections to inaccurate or
                                    incomplete personal information. We will
                                    make corrections where appropriate and
                                    notify relevant third parties if necessary.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.3 Withdrawal of Consent
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    You may withdraw consent for specific uses
                                    or disclosures of your information, subject
                                    to legal and contractual restrictions.
                                    Withdrawal of consent may affect our ability
                                    to provide certain services.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    7.4 Complaints
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    If you have concerns about how we handle
                                    your personal information, you may file a
                                    complaint with:
                                </p>

                                <div>
                                    <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                        <li>
                                            Our Privacy Officer (contact
                                            information below)
                                        </li>
                                        <li>
                                            The Office of the Information and
                                            Privacy Commissioner of Alberta
                                        </li>
                                        <li>
                                            The Privacy Commissioner of Canada
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {/* Minor And Guardian Consent */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                8. Minors and Guardian Consent
                            </p>

                            <p className="leading-relaxed text-muted-foreground">
                                Our services are primarily provided to children
                                and youth. Personal information about minors is
                                collected with parental or guardian consent.
                                Parents and guardians have the right to access
                                their child's personal information, subject to
                                considerations of the child's best interests and
                                applicable legislation.
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                As children mature, we recognize their growing
                                autonomy and may involve them in decisions about
                                their information, consistent with professional
                                standards and the child's capacity to
                                understand.
                            </p>
                        </div>

                        {/* Website and Technology */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                9. Website and Technology
                            </p>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    9.1 Cookies and Tracking
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Our website uses cookies and similar
                                    tracking technologies to enhance user
                                    experience, remember preferences, and
                                    analyze site usage. Cookies are small text
                                    files stored on your device that help our
                                    website function properly and provide you
                                    with a personalized experience.
                                </p>

                                <p className="mt-3 leading-relaxed text-muted-foreground">
                                    We use different types of cookies for
                                    various purposes:
                                </p>

                                <div>
                                    <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                        <li>
                                            <span className="font-bold">
                                                Essential Cookies:{' '}
                                            </span>{' '}
                                            Required for the website to function
                                            properly (e.g., security, session
                                            management, authentication). These
                                            cannot be disabled.
                                        </li>
                                        <li>
                                            <span className="font-bold">
                                                {' '}
                                                Functional Cookies:{' '}
                                            </span>
                                            Enable enhanced features and
                                            personalization (e.g., language
                                            preferences, accessibility
                                            settings). These are optional.
                                        </li>
                                        <li>
                                            <span className="font-bold">
                                                Analytics Cookies:{' '}
                                            </span>{' '}
                                            Help us understand how visitors use
                                            our website to improve our services
                                            (e.g., Google Analytics). These are
                                            optional.
                                        </li>
                                        <li>
                                            <span className="font-bold">
                                                Marketing Cookies{' '}
                                            </span>{' '}
                                            Used to deliver relevant
                                            advertisements and measure campaign
                                            effectiveness. These are optional.
                                        </li>
                                    </ul>
                                </div>
                                <p className="mt-3 leading-relaxed text-muted-foreground">
                                    You can control cookie settings through your
                                    browser or our cookie preference center.
                                    Disabling certain cookies may affect website
                                    functionality. For detailed information
                                    about the cookies we use and how to manage
                                    them, please see our Cookie Policy.
                                </p>

                                <p className="mt-3 leading-relaxed text-muted-foreground">
                                    We respect your privacy choices and provide
                                    clear options to accept, decline, or
                                    customize cookie settings when you first
                                    visit our website.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    9.2 Third-Party Links
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Our website may contain links to third-party
                                    websites. We are not responsible for the
                                    privacy practices of external sites. We
                                    encourage you to review the privacy policies
                                    of any third-party sites you visit.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    9.3 Online Forms
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Certain forms allow you to upload documents
                                    such as resumes, Information submitted
                                    through online forms (intake forms, contact
                                    forms) is transmitted securely using
                                    encryption technology. However, email is not
                                    a completely secure communication method.
                                </p>
                            </div>

                            <div>
                                <p className="mt-3 font-semibold text-muted-foreground">
                                    9.4 Telehealth Services
                                </p>
                                <p className="leading-relaxed text-muted-foreground">
                                    When providing services via
                                    videoconferencing or telehealth platforms,
                                    we use secure, encrypted platforms that
                                    comply with privacy regulations. Clients are
                                    responsible for ensuring privacy on their
                                    end (private location, secure internet
                                    connection).
                                </p>
                            </div>
                        </div>

                        {/* Marketing and Communications */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                10. Marketing Communications
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                With your consent, we may send you information
                                about our services, updates, newsletters, and
                                educational resources. You may opt out of
                                marketing communications at any time by:
                            </p>

                            <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                <li>
                                    Clicking the "unsubscribe" link in emails
                                </li>
                                <li>
                                    Contacting us directly to update your
                                    preferences
                                </li>
                                <li>
                                    Opting out through your client portal
                                    account settings
                                </li>
                            </ul>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Opting out of marketing communications will not
                                affect service-related communications
                                (appointment reminders, billing notices,
                                important updates).
                            </p>
                        </div>

                        {/* Cross-Border Data Transfer */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                11. Cross-Border Data Transfer
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Your personal information is primarily stored
                                and processed in Canada. If we use service
                                providers located outside Canada, we ensure they
                                provide an adequate level of privacy protection
                                through contractual obligations and compliance
                                measures.
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Information stored or processed outside Canada
                                may be subject to foreign laws and accessible to
                                foreign governments, courts, and law enforcement
                                agencies under lawful access provisions.
                            </p>
                        </div>

                        {/* Research and Quality Improvement */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                12. . Research and Quality Improvement
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                We may use de-identified, aggregated data for
                                research, quality improvement, and educational
                                purposes. This data cannot be used to identify
                                individual clients.
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                If we wish to use personal information for
                                research purposes, we will seek separate consent
                                or approval from an appropriate research ethics
                                board.
                            </p>
                        </div>

                        {/* Privacy Breach Protocol */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                13. Privacy Breach Protocol
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                In the event of a privacy breach that poses a
                                real risk of significant harm, we will:
                            </p>

                            <div>
                                <ul className="ml-4 list-inside list-disc text-muted-foreground">
                                    <li>
                                        Contain and investigate the breach
                                        immediately
                                    </li>
                                    <li>
                                        Notify affected individuals as soon as
                                        reasonably possible
                                    </li>
                                    <li>
                                        Report to relevant privacy commissioners
                                        as required by law
                                    </li>
                                    <li>
                                        Take steps to prevent similar breaches
                                        in the future
                                    </li>
                                    <li>Maintain records of all breaches</li>
                                </ul>
                            </div>
                        </div>

                        {/* Updates to Privacy Policy */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                14. Updates to Privacy Policy
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                We may update this Privacy Policy from time to
                                time to reflect changes in our practices,
                                technology, legal requirements, or other
                                factors. Updates will be posted on our website
                                with the revision date.
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Significant changes that affect how we handle
                                your personal information will be communicated
                                directly to clients via email or written notice.
                                Continued use of our services after changes
                                constitutes acceptance of the updated Privacy
                                Policy.
                            </p>
                        </div>

                        {/* Contact */}
                        <div className="border-b pb-3">
                            <p className="mt-5 text-xl text-primary">
                                15. Contact Information
                            </p>

                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                Our Privacy Officer is responsible for ensuring
                                compliance with privacy legislation and
                                addressing privacy-related inquiries and
                                complaints
                            </p>

                            <div className="rounded-sm bg-secondary-orange/5 p-5">
                                <p className="font-bold">Privacy Officer</p>
                                <p>Creative Abilities Therapy Services</p>
                                <p>Email: info@creativeabilitiestherapy.ca</p>
                                <p>Phone: (587) 422-8780</p>
                                <p>Address: Calgary, Alberta, Canada</p>
                            </div>

                            <p className="mt-3 font-bold">
                                For privacy complaints in Alberta
                            </p>

                            <div className="rounded-sm bg-gray-200 p-5">
                                <p>
                                    Office of the Information and Privacy
                                    Commissioner of Alberta
                                </p>
                                <p>
                                    Phone: (403) 297-2728 | Toll-free:
                                    1-888-878-4044
                                </p>
                                <p>Website: www.oipc.ab.ca</p>
                            </div>

                            <p className="mt-3 font-bold">
                                For privacy complaints at the federal level:
                            </p>

                            <div className="rounded-sm bg-gray-200 p-5">
                                <p>
                                    Office of the Privacy Commissioner of Canada
                                </p>
                                <p>Phone: 1-800-282-1376</p>
                                <p>Website: www.priv.gc.ca</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

Privacy.layout = (page: React.ReactNode) => (
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
