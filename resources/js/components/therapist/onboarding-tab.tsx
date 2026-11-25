import { useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle } from 'lucide-react';
import { useState } from 'react';

import DocumentViewerModal from '@/components/document-viewer-modal';
import type { ViewableDocument } from '@/components/document-viewer-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TeamMemberDocument } from '@/types/team-member';

interface OnboardingForm {
    /** One slot per required document, in the same order. */
    files: (File | null)[];
    sin_number: string;
}

/**
 * The only tab an onboarding candidate sees: a file field for every
 * required document, then their SIN, submitted together. Rows already
 * handed in show the uploaded file; picking a new one adds another.
 */
export default function OnboardingTab({
    documents,
    requiredDocuments,
    missingDocuments,
    hasSin,
}: {
    documents: TeamMemberDocument[];
    requiredDocuments: string[];
    missingDocuments: string[];
    hasSin: boolean;
}) {
    const [documentToView, setDocumentToView] =
        useState<ViewableDocument | null>(null);
    // Bumped after a save so the uncontrolled file inputs clear.
    const [inputsVersion, setInputsVersion] = useState(0);

    const { data, setData, post, transform, processing, errors, reset } =
        useForm<OnboardingForm>({
            files: requiredDocuments.map(() => null),
            sin_number: '',
        });

    // Only rows with a file are sent, keyed by their row so a validation
    // error (documents.3.file) lands under the right field.
    transform((form) => ({
        sin_number: form.sin_number,
        documents: Object.fromEntries(
            form.files.flatMap((file, index) =>
                file ? [[index, { type: requiredDocuments[index], file }]] : [],
            ),
        ),
    }));

    const setFile = (index: number, file: File | null) => {
        setData(
            'files',
            data.files.map((current, slot) =>
                slot === index ? file : current,
            ),
        );
    };

    const submit = () => {
        post('/therapist/profile/onboarding', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setInputsVersion((version) => version + 1);
            },
        });
    };

    const fieldError = (index: number): string | undefined => {
        const keyed = errors as Record<string, string | undefined>;

        return (
            keyed[`documents.${index}.file`] ?? keyed[`documents.${index}.type`]
        );
    };

    const outstanding = missingDocuments.length + (hasSin ? 0 : 1);

    return (
        <>
            <Card className="rounded-[10px]">
                <CardContent className="grid gap-6 p-5">
                    <div>
                        <p className="font-bold text-primary">
                            Onboarding Form
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Upload each required document and enter your SIN,
                            then submit. You can submit what you have now and
                            come back for the rest.
                        </p>
                    </div>

                    {outstanding === 0 ? (
                        <div className="flex items-center gap-2 rounded-[5px] border border-green-400 bg-green-50 p-3 text-sm text-green-800">
                            <CheckCircle className="h-4 w-4" />
                            Everything is in. Our team is reviewing your
                            documents.
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 rounded-[5px] border border-red-400 bg-red-50 p-3 text-sm text-red-800">
                            <AlertTriangle className="h-4 w-4" />
                            {outstanding} item{outstanding === 1 ? '' : 's'}{' '}
                            still needed.
                        </div>
                    )}

                    <div className="grid gap-4">
                        <p className="font-semibold">Required Documents</p>

                        {requiredDocuments.map((documentType, index) => {
                            const uploaded = documents.filter(
                                (document) =>
                                    document.doc_type === documentType,
                            );
                            const isMissing =
                                missingDocuments.includes(documentType);
                            const error = fieldError(index);

                            return (
                                <div
                                    key={documentType}
                                    className="grid gap-2 rounded-[10px] border p-4 md:grid-cols-2 md:items-center"
                                >
                                    <div>
                                        <Label
                                            htmlFor={`onboarding-file-${index}`}
                                            className="flex items-center gap-2"
                                        >
                                            {isMissing ? (
                                                <AlertTriangle className="h-4 w-4 text-red-600" />
                                            ) : (
                                                <CheckCircle className="h-4 w-4 text-green-600" />
                                            )}
                                            {documentType}
                                            {isMissing && ' *'}
                                        </Label>
                                        {uploaded.map((document) => (
                                            <button
                                                key={document.id}
                                                type="button"
                                                className="mt-1 ml-6 block truncate text-left text-sm text-primary underline"
                                                onClick={() =>
                                                    setDocumentToView({
                                                        title: document.title,
                                                        url: `/files/client-documents/${document.id}`,
                                                    })
                                                }
                                            >
                                                {document.title ?? 'View file'}
                                            </button>
                                        ))}
                                    </div>
                                    <div>
                                        <Input
                                            key={`${inputsVersion}-${index}`}
                                            id={`onboarding-file-${index}`}
                                            type="file"
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                            className="rounded-[10px]"
                                            onChange={(event) =>
                                                setFile(
                                                    index,
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />
                                        {error && (
                                            <p className="mt-1 text-sm text-red-600">
                                                {error}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <div className="grid gap-2 md:max-w-md">
                        <p className="font-semibold">Social Insurance Number</p>
                        <Label
                            htmlFor="onboarding-sin"
                            className="flex items-center gap-2"
                        >
                            {hasSin ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-red-600" />
                            )}
                            SIN{!hasSin && ' *'}
                        </Label>
                        <Input
                            id="onboarding-sin"
                            type="password"
                            inputMode="numeric"
                            autoComplete="off"
                            maxLength={11}
                            value={data.sin_number}
                            placeholder={
                                hasSin
                                    ? 'On file. Leave blank to keep it'
                                    : '9-digit SIN'
                            }
                            className="rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'sin_number',
                                    event.target.value.replace(/[^\d\s-]/g, ''),
                                )
                            }
                        />
                        {errors.sin_number ? (
                            <p className="text-sm text-red-600">
                                {errors.sin_number}
                            </p>
                        ) : (
                            <p className="text-xs text-muted-foreground">
                                Stored securely. It cannot be shown back once
                                saved.
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end">
                        <Button
                            type="button"
                            className="rounded-[10px]"
                            disabled={processing}
                            onClick={submit}
                        >
                            {processing ? 'Submitting…' : 'Submit Onboarding'}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <DocumentViewerModal
                document={documentToView}
                onClose={() => setDocumentToView(null)}
            />
        </>
    );
}
