import { Eye } from 'lucide-react';
import { useState } from 'react';

import DocumentViewerModal from '@/components/document-viewer-modal';
import type { ViewableDocument } from '@/components/document-viewer-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Application } from '@/types/application';

/** Reference: cats-frontend/src/pages/admin/applicationTabs/ApplicationTab.tsx */
export default function ApplicationTab({
    application,
}: {
    application: Application;
}) {
    const [documentToView, setDocumentToView] =
        useState<ViewableDocument | null>(null);

    const documents: {
        key: 'resume' | 'cover_letter';
        label: string;
        segment: string;
    }[] = [
        { key: 'resume', label: 'Resume', segment: 'resume' },
        { key: 'cover_letter', label: 'Cover Letter', segment: 'cover-letter' },
    ];

    return (
        <div className="grid grid-cols-1 gap-3 p-5">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p>Why Join Us</p>
                    <p className="text-sm text-muted-foreground">
                        Candidate&apos;s motivation for applying
                    </p>

                    <div className="mt-3 rounded-[5px] bg-gray-100 p-3">
                        <p className="font-mono">
                            {application.reason_for_applying || 'Not Specified'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p>How They Heard About Us</p>
                    <p className="text-sm text-muted-foreground">
                        Application source
                    </p>

                    <div className="mt-3 rounded-[5px] bg-gray-100 p-3">
                        <p className="font-mono">
                            {application.lead_source || 'Not Specified'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="space-y-3 p-5">
                    <p className="font-semibold">Resume &amp; Documents</p>
                    <p className="text-sm text-muted-foreground">
                        Uploaded application materials
                    </p>

                    {documents.map((document) => (
                        <div
                            key={document.key}
                            className="flex flex-col gap-2 rounded-[5px] bg-gray-100 p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="font-bold">{document.label}</p>
                                <p className="text-sm text-muted-foreground">
                                    {application[document.key]
                                        ? 'Uploaded'
                                        : 'Not Provided'}
                                </p>
                            </div>

                            {application[document.key] && (
                                <Button
                                    variant="outline"
                                    className="mt-2 rounded-[5px] whitespace-nowrap sm:mt-0"
                                    onClick={() =>
                                        setDocumentToView({
                                            title: document.label,
                                            url: `/files/applications/${application.id}/${document.segment}`,
                                        })
                                    }
                                >
                                    <Eye className="mr-2" /> View
                                </Button>
                            )}
                        </div>
                    ))}
                </CardContent>
            </Card>

            <DocumentViewerModal
                document={documentToView}
                onClose={() => setDocumentToView(null)}
            />
        </div>
    );
}
