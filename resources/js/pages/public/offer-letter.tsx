import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock, FileText, XCircle } from 'lucide-react';
import { useState } from 'react';

import SignaturePad from '@/components/signature-pad';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';

/** Only what the letter shows — the admin payload never reaches this page. */
interface OfferCandidate {
    id: number;
    first_name: string;
    full_name: string;
    position_applied: string | null;
    hourly_rate: string | null;
    reference_number: string | null;
    preferred_start_date: string | null;
    offer_sent_at: string | null;
    offer_expires_at: string | null;
}

/** Anything but `open` means the pad is not shown. */
type OfferState = 'open' | 'accepted' | 'declined' | 'expired' | 'withdrawn';

const CLOSED_COPY: Record<
    Exclude<OfferState, 'open'>,
    { title: string; body: string }
> = {
    accepted: {
        title: 'You have already signed this offer',
        body: 'Thank you — your signed offer letter is on file. We will be in touch with your next steps shortly.',
    },
    declined: {
        title: 'You have declined this offer',
        body: 'Thank you for letting us know. We wish you all the best, and we would be glad to hear from you about a future opening.',
    },
    expired: {
        title: 'This offer has expired',
        body: 'The date for accepting this offer has passed. If you are still interested, please reply to the email we sent you and we will be happy to talk.',
    },
    withdrawn: {
        title: 'This offer is no longer available',
        body: 'We could not find an active offer for this link. If you think this is a mistake, please reply to the email we sent you.',
    },
};

function formatDate(value: string | null): string {
    if (!value) {
        return 'To be confirmed';
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex flex-col gap-0.5 border-b border-dashed py-2 last:border-0 sm:flex-row sm:gap-4">
            <span className="w-full font-semibold sm:w-56">{label}</span>
            <span className="text-muted-foreground">{value}</span>
        </div>
    );
}

export default function OfferLetter({
    application,
    state,
    acceptUrl,
    declineUrl,
}: {
    application: OfferCandidate;
    state: OfferState;
    acceptUrl: string | null;
    declineUrl: string | null;
}) {
    const [signature, setSignature] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [confirmingDecline, setConfirmingDecline] = useState(false);

    const rate = application.hourly_rate
        ? `$${Number(application.hourly_rate).toFixed(2)}/hour`
        : 'To be confirmed';

    const accept = () => {
        if (!signature || !acceptUrl) {
            return;
        }

        router.post(
            acceptUrl,
            { signature },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const decline = () => {
        if (!declineUrl) {
            return;
        }

        router.post(
            declineUrl,
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    if (state !== 'open') {
        const copy = CLOSED_COPY[state];

        return (
            <>
                <Head title="Your Offer" />
                <section className="mx-3 my-20 lg:mx-[15%]">
                    <Card className="flex flex-col items-center gap-4 p-10 text-center">
                        {state === 'accepted' ? (
                            <CheckCircle2 className="h-12 w-12 text-green-600" />
                        ) : state === 'expired' ? (
                            <Clock className="h-12 w-12 text-yellow-600" />
                        ) : (
                            <XCircle className="h-12 w-12 text-muted-foreground" />
                        )}
                        <h1 className="text-2xl font-semibold">{copy.title}</h1>
                        <p className="max-w-prose text-muted-foreground">
                            {copy.body}
                        </p>
                    </Card>
                </section>
            </>
        );
    }

    return (
        <>
            <Head title="Your Offer" />

            <section className="mx-3 my-14 flex flex-col gap-6 lg:mx-[15%]">
                <div className="flex flex-col gap-2">
                    <p className="flex items-center gap-2 text-sm tracking-wide text-muted-foreground uppercase">
                        <FileText className="h-4 w-4" /> Private &amp;
                        Confidential
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Offer of Independent Contractor Position
                    </h1>
                    <p className="text-muted-foreground">
                        Please read this offer and sign at the bottom by{' '}
                        <strong>
                            {formatDate(application.offer_expires_at)}
                        </strong>
                        .
                    </p>
                </div>

                <Card className="flex flex-col gap-4 p-6 md:p-10">
                    <p>Dear {application.full_name},</p>

                    <p>
                        On behalf of Creative Abilities Therapy Services (CATS),
                        we are pleased to offer you the position of{' '}
                        <strong>{application.position_applied}</strong> as an{' '}
                        <strong>Independent Contractor</strong>. The details of
                        this offer are as follows:
                    </p>

                    <div className="text-sm">
                        <DetailRow
                            label="Position"
                            value={application.position_applied ?? '—'}
                        />
                        <DetailRow
                            label="Service Area"
                            value={application.position_applied ?? '—'}
                        />
                        <DetailRow label="Compensation" value={rate} />
                        <DetailRow
                            label="Proposed Start Date"
                            value={formatDate(application.preferred_start_date)}
                        />
                        <DetailRow label="Location" value="Home or Community" />
                        <DetailRow
                            label="Schedule"
                            value="Flexible and based on client referrals and your availability."
                        />
                        <DetailRow
                            label="Reports To"
                            value="Operations & Program Lead, Creative Abilities Therapy Services"
                        />
                    </div>

                    <h2 className="mt-2 text-lg font-semibold">
                        Independent Contractor Status
                    </h2>
                    <p className="text-muted-foreground">
                        You will provide services as an Independent Contractor
                        and <strong>not</strong> as an employee of Creative
                        Abilities Therapy Services. As such, you are responsible
                        for your own income taxes, Canada Pension Plan (CPP)
                        contributions, insurance, and any other statutory
                        obligations.
                    </p>

                    <h2 className="mt-2 text-lg font-semibold">
                        Professional Requirements
                    </h2>
                    <p className="text-muted-foreground">
                        Where applicable, you are responsible for maintaining
                        your professional registration, liability insurance, and
                        any license or certifications required to practice
                        within your profession. You are also expected to comply
                        with the standards of your regulatory college or
                        professional association, as well as the policies and
                        procedures of Creative Abilities Therapy Services.
                    </p>

                    <h2 className="mt-2 text-lg font-semibold">
                        Conditions of Offer
                    </h2>
                    <p className="text-muted-foreground">
                        This offer is conditional upon:
                    </p>
                    <ul className="list-disc pl-6 text-muted-foreground">
                        <li>
                            Completion of all required onboarding documentation.
                        </li>
                        <li>Signing the Independent Contractor Agreement.</li>
                        <li>
                            Submission of all required credentials and
                            documentation applicable to your role.
                        </li>
                    </ul>
                </Card>

                <Card className="flex flex-col gap-4 p-6 md:p-10">
                    <h2 className="text-lg font-semibold tracking-wide">
                        ACCEPTANCE OF OFFER
                    </h2>
                    <p className="text-muted-foreground">
                        I, <strong>{application.full_name}</strong>, acknowledge
                        that I have read and understand this Offer Letter and
                        accept the position of {application.position_applied} as
                        an Independent Contractor with Creative Abilities
                        Therapy Services. I understand that the complete terms
                        and conditions of my engagement are contained within the
                        Independent Contractor Agreement.
                    </p>

                    <div className="mt-2">
                        <p className="mb-2 text-sm font-semibold">
                            Contractor Signature
                        </p>
                        <SignaturePad
                            caption={application.full_name}
                            onSignatureChange={setSignature}
                        />
                    </div>

                    <div className="mt-2 flex flex-col gap-3 sm:flex-row">
                        <Button
                            className="flex-1 rounded-[10px]"
                            onClick={accept}
                            disabled={!signature || processing}
                        >
                            {processing
                                ? 'Sending...'
                                : 'Sign and accept this offer'}
                        </Button>

                        {confirmingDecline ? (
                            <div className="flex flex-1 gap-2">
                                <Button
                                    variant="destructive"
                                    className="flex-1 rounded-[10px]"
                                    onClick={decline}
                                    disabled={processing}
                                >
                                    Confirm decline
                                </Button>
                                <Button
                                    variant="outline"
                                    className="rounded-[10px]"
                                    onClick={() => setConfirmingDecline(false)}
                                    disabled={processing}
                                >
                                    Cancel
                                </Button>
                            </div>
                        ) : (
                            <Button
                                variant="outline"
                                className="flex-1 rounded-[10px]"
                                onClick={() => setConfirmingDecline(true)}
                                disabled={processing}
                            >
                                Decline this offer
                            </Button>
                        )}
                    </div>

                    <p className="text-xs text-muted-foreground">
                        Reference {application.reference_number}. This link is
                        personal to you and stops working after{' '}
                        {formatDate(application.offer_expires_at)}.
                    </p>
                </Card>
            </section>
        </>
    );
}

OfferLetter.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: false,
            title: 'Questions about your offer?',
            desc: 'Reply to the email we sent you and our team will be happy to help.',
        }}
    >
        {page}
    </PublicLayout>
);
