import { Head, useForm } from '@inertiajs/react';
import {
    RefreshCw,
    CheckCircle2,
    Download,
    FileText,
    PenLine,
    Upload,
    X,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/public-layout';

/** Only what the page shows — the client record itself never reaches it. */
interface ConsentSummary {
    child_name: string;
    parent_name: string | null;
    reference_number: string | null;
    signed_at: string | null;
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function Step({
    number,
    icon,
    title,
    children,
}: {
    number: number;
    icon: React.ReactNode;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex gap-4">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                {icon}
            </div>
            <div className="flex flex-1 flex-col gap-2">
                <p className="font-semibold">
                    Step {number}: {title}
                </p>
                {children}
            </div>
        </div>
    );
}

/**
 * The file picker and submit button, shared by the first upload and by a
 * replacement after the parent spots they sent the wrong file.
 */
function SignedConsentUpload({
    uploadUrl,
    submitLabel,
}: {
    uploadUrl: string;
    submitLabel: string;
}) {
    const { data, setData, post, processing, errors, progress } = useForm<{
        files: File[];
    }>({ files: [] });

    const fileErrors = Object.entries(errors as Record<string, string>)
        .filter(([key]) => key === 'files' || key.startsWith('files.'))
        .map(([, message]) => message);

    const addFiles = (list: FileList | null) => {
        if (!list) {
            return;
        }

        setData('files', [...data.files, ...Array.from(list)]);
    };

    const removeFile = (index: number) => {
        setData(
            'files',
            data.files.filter((_, position) => position !== index),
        );
    };

    const submit = () => {
        if (data.files.length === 0) {
            return;
        }

        post(uploadUrl, { preserveScroll: true, forceFormData: true });
    };

    return (
        <div className="flex flex-col gap-2">
            <p className="text-sm text-muted-foreground">
                Upload a scanned PDF, or clear photos of every signed page (PDF,
                JPG or PNG, up to 10 MB each).
            </p>

            <div>
                <Label htmlFor="signed-consent" className="sr-only">
                    Signed consent files
                </Label>
                <Input
                    id="signed-consent"
                    type="file"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png"
                    className="rounded-[10px]"
                    onChange={(event) => {
                        addFiles(event.target.files);
                        event.target.value = '';
                    }}
                />
            </div>

            {data.files.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {data.files.map((file, index) => (
                        <li
                            key={`${file.name}-${index}`}
                            className="flex items-center justify-between rounded-[10px] border px-3 py-2 text-sm"
                        >
                            <span className="truncate">{file.name}</span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                aria-label={`Remove ${file.name}`}
                                onClick={() => removeFile(index)}
                            >
                                <X />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}

            {fileErrors.map((message) => (
                <p key={message} className="text-sm text-red-600">
                    {message}
                </p>
            ))}

            {progress && (
                <progress
                    value={progress.percentage}
                    max="100"
                    className="w-full"
                />
            )}

            <div>
                <Button
                    className="rounded-[10px]"
                    onClick={submit}
                    disabled={processing || data.files.length === 0}
                >
                    <Upload /> {submitLabel}
                </Button>
            </div>
        </div>
    );
}

export default function ClientConsent({
    consent,
    downloadUrl,
    uploadUrl,
}: {
    consent: ConsentSummary;
    downloadUrl: string;
    uploadUrl: string | null;
}) {
    if (consent.signed_at) {
        return (
            <>
                <Head title="Therapy Consent" />
                <section className="mx-3 my-20 lg:mx-[15%]">
                    <Card className="flex flex-col items-center gap-4 p-10 text-center">
                        <CheckCircle2 className="h-12 w-12 text-green-600" />
                        <h1 className="text-2xl font-semibold">
                            We have your signed consent
                        </h1>
                        <p className="max-w-prose text-muted-foreground">
                            Thank you — the signed consent for{' '}
                            <strong>{consent.child_name}</strong> was received
                            on {formatDate(consent.signed_at)}. Our team will be
                            in touch about scheduling your first sessions.
                        </p>
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            asChild
                        >
                            <a href={downloadUrl}>
                                <Download /> Download a blank copy
                            </a>
                        </Button>
                    </Card>

                    {uploadUrl && (
                        <Card className="mt-6 flex flex-col gap-3 p-6 md:p-10">
                            <h2 className="flex items-center gap-2 text-lg font-semibold">
                                <RefreshCw className="h-5 w-5" /> Uploaded the
                                wrong file?
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Upload the correct signed copy below. It will
                                replace what you sent before.
                            </p>
                            <SignedConsentUpload
                                uploadUrl={uploadUrl}
                                submitLabel="Replace Signed Consent"
                            />
                        </Card>
                    )}
                </section>
            </>
        );
    }

    return (
        <>
            <Head title="Therapy Consent" />

            <section className="mx-3 my-14 flex flex-col gap-6 lg:mx-[15%]">
                <div className="flex flex-col gap-2">
                    <p className="flex items-center gap-2 text-sm tracking-wide text-muted-foreground uppercase">
                        <FileText className="h-4 w-4" /> Private &amp;
                        Confidential
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Therapy Consent &amp; Family Service Agreement
                    </h1>
                    <p className="text-muted-foreground">
                        Every service requested for{' '}
                        <strong>{consent.child_name}</strong> has been approved.
                        Before sessions begin, please sign and return the
                        consent package below.
                    </p>
                </div>

                <Card className="flex flex-col gap-8 p-6 md:p-10">
                    <Step
                        number={1}
                        icon={<Download className="h-5 w-5" />}
                        title="Download the consent"
                    >
                        <p className="text-sm text-muted-foreground">
                            The PDF includes the Therapy Consent Form, the
                            Family Service Agreement and the Funding Source
                            page, already filled in with the details from your
                            intake.
                        </p>
                        <div>
                            {/*
                             * A file download, so a plain anchor rather than
                             * an Inertia <Link>.
                             */}
                            <Button className="rounded-[10px]" asChild>
                                <a href={downloadUrl}>
                                    <Download /> Download Consent PDF
                                </a>
                            </Button>
                        </div>
                    </Step>

                    <Step
                        number={2}
                        icon={<PenLine className="h-5 w-5" />}
                        title="Sign it"
                    >
                        <p className="text-sm text-muted-foreground">
                            Print the PDF, check the details, and sign and date
                            both signature lines — one under the Therapy Consent
                            Form and one under the Family Service Agreement. If
                            you have an approved FSCD contract, include a copy
                            of your child&apos;s FSCD Agreement.
                        </p>
                    </Step>

                    <Step
                        number={3}
                        icon={<Upload className="h-5 w-5" />}
                        title="Upload the signed copy"
                    >
                        {uploadUrl && (
                            <SignedConsentUpload
                                uploadUrl={uploadUrl}
                                submitLabel="Submit Signed Consent"
                            />
                        )}
                    </Step>

                    <p className="text-xs text-muted-foreground">
                        {consent.reference_number &&
                            `Reference ${consent.reference_number}. `}
                        This link is personal to you. If it stops working, reply
                        to the email we sent you and we will send a new one.
                    </p>
                </Card>
            </section>
        </>
    );
}

ClientConsent.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: false,
            title: 'Questions about the consent?',
            desc: 'Reply to the email we sent you and our team will be happy to help.',
        }}
    >
        {page}
    </PublicLayout>
);
