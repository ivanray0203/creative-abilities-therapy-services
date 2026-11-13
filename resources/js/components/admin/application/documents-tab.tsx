import {
    AlertTriangle,
    CheckCircle,
    Download,
    ReceiptText,
} from 'lucide-react';
import { useState } from 'react';

import DocumentViewerModal from '@/components/document-viewer-modal';
import type { ViewableDocument } from '@/components/document-viewer-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/helpers';
import type { Application, ApplicationOnboarding } from '@/types/application';

/**
 * Everything the candidate has handed in, in one place: the materials that
 * came with the application and the offer, then the onboarding documents
 * they upload through their profile once their account exists.
 */
export default function DocumentsTab({
    application,
    onboarding,
}: {
    application: Application;
    onboarding: ApplicationOnboarding | null;
}) {
    const [documentToView, setDocumentToView] =
        useState<ViewableDocument | null>(null);

    const filesUrl = `/files/applications/${application.id}`;
    const applicationFiles: {
        label: string;
        isProvided: boolean;
        url: string;
    }[] = [
        {
            label: 'Resume',
            isProvided: Boolean(application.resume),
            url: `${filesUrl}/resume`,
        },
        {
            label: 'Cover Letter',
            isProvided: Boolean(application.cover_letter),
            url: `${filesUrl}/cover-letter`,
        },
        {
            label: 'Offer Letter',
            isProvided: Boolean(application.offer_letter),
            url: `${filesUrl}/offer-letter`,
        },
        {
            label: 'Signed Offer Letter',
            isProvided: Boolean(application.signed_offer_letter),
            url: `${filesUrl}/signed-offer-letter`,
        },
    ];

    const missingDocuments = onboarding?.missing_documents ?? [];

    return (
        <div className="grid grid-cols-1 gap-3 p-5">
            <Card className="rounded-[10px]">
                <CardContent className="space-y-3 p-5">
                    <p className="font-semibold">Application Materials</p>
                    <p className="text-sm text-muted-foreground">
                        Files submitted with the application and the offer
                    </p>

                    {applicationFiles.map((file) => (
                        <div
                            key={file.label}
                            className="flex flex-col gap-2 rounded-[5px] bg-gray-100 p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="font-bold">{file.label}</p>
                                <p className="text-sm text-muted-foreground">
                                    {file.isProvided
                                        ? 'Uploaded'
                                        : 'Not Provided'}
                                </p>
                            </div>

                            {file.isProvided && (
                                <Button
                                    variant="outline"
                                    className="mt-2 rounded-[5px] whitespace-nowrap sm:mt-0"
                                    onClick={() =>
                                        setDocumentToView({
                                            title: file.label,
                                            url: file.url,
                                        })
                                    }
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    View
                                </Button>
                            )}
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="space-y-3 p-5">
                    <p className="font-semibold">Onboarding Documents</p>
                    <p className="text-sm text-muted-foreground">
                        Uploaded by the candidate through their profile
                    </p>

                    {onboarding === null ? (
                        <p className="rounded-[5px] bg-gray-100 p-3 text-sm text-muted-foreground">
                            The candidate has not started onboarding yet. Their
                            upload area opens once onboarding begins.
                        </p>
                    ) : (
                        <>
                            {onboarding.required_documents.length > 0 &&
                                (missingDocuments.length > 0 ? (
                                    <div className="flex flex-col gap-2 rounded-[5px] border border-red-400 bg-red-50 p-4 text-red-800">
                                        <p className="flex items-center gap-2 font-medium">
                                            <AlertTriangle className="h-4 w-4" />{' '}
                                            Missing Required Documents
                                        </p>
                                        <ul className="ml-6 list-disc text-sm">
                                            {missingDocuments.map((doc) => (
                                                <li key={doc}>{doc}</li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-2 rounded-[5px] border border-green-400 bg-green-50 p-4 text-green-800">
                                        <CheckCircle className="h-4 w-4" /> All
                                        required documents have been submitted.
                                    </div>
                                ))}

                            {onboarding.documents.length > 0 ? (
                                onboarding.documents.map((document) => (
                                    <div
                                        key={document.id}
                                        className="flex flex-col gap-2 rounded-[5px] bg-gray-100 p-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="flex min-w-0 flex-1 items-center gap-3">
                                            <div className="flex rounded-[5px] bg-secondary-orange/10 p-3 text-primary">
                                                <ReceiptText />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate font-bold">
                                                    {document.title}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {document.doc_type}
                                                    {document.uploaded_at
                                                        ? ` • Uploaded ${formatDate(document.uploaded_at)}`
                                                        : ''}
                                                </p>
                                            </div>
                                        </div>

                                        {document.drive_web_view && (
                                            <Button
                                                variant="outline"
                                                className="mt-2 rounded-[5px] whitespace-nowrap sm:mt-0"
                                                onClick={() =>
                                                    setDocumentToView({
                                                        title: document.title,
                                                        url: `/files/client-documents/${document.id}`,
                                                    })
                                                }
                                            >
                                                <Download className="mr-2 h-4 w-4" />
                                                View
                                            </Button>
                                        )}
                                    </div>
                                ))
                            ) : (
                                <p className="rounded-[5px] bg-gray-100 p-3 text-center text-sm text-muted-foreground">
                                    No documents uploaded yet
                                </p>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>

            <DocumentViewerModal
                document={documentToView}
                onClose={() => setDocumentToView(null)}
            />
        </div>
    );
}
