import { Download, Plus, ReceiptText } from 'lucide-react';
import { useState } from 'react';

import DeleteDocumentModal from '@/components/admin/client/delete-document-modal';
import UploadDocumentModal from '@/components/admin/client/upload-document-modal';
import DocumentViewerModal from '@/components/document-viewer-modal';
import type { ViewableDocument } from '@/components/document-viewer-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/helpers';
import type { Client, ClientDocument } from '@/types/client';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Documents.tsx */
export default function DocumentsTab({
    client,
    isPreview,
}: {
    client: Client;
    isPreview?: boolean;
}) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const [documentToDelete, setDocumentToDelete] =
        useState<ClientDocument | null>(null);

    const [documentToView, setDocumentToView] =
        useState<ViewableDocument | null>(null);

    const documents = client.documents ?? [];
    // Files the family sent with the intake application. They stay on the
    // intake record, so they are listed here read-only.
    const intakeDocuments = client.original_intake?.documents ?? [];

    return (
        <>
            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <div className="flex flex-col justify-between md:flex-row">
                            <div>
                                <p className="flex flex-row items-center gap-3">
                                    Uploaded Documents
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Files attached to this client's record
                                </p>
                            </div>

                            {!isPreview && (
                                <Button
                                    variant="outline"
                                    className="mt-5 rounded-[5px] bg-gray-100 md:mt-0"
                                    onClick={() => setUploadOpen(true)}
                                >
                                    <Plus />
                                    Upload Document
                                </Button>
                            )}
                        </div>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            {documents.length > 0 ? (
                                documents.map((document) => (
                                    <div
                                        key={document.id}
                                        className="flex flex-row justify-between rounded-sm border p-3"
                                    >
                                        <div className="flex flex-row items-center gap-3">
                                            <div className="flex rounded-[5px] bg-secondary-orange/10 p-3 text-primary">
                                                <ReceiptText />
                                            </div>
                                            <div>
                                                <p>{document.title}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {document.doc_type} •
                                                    Uploaded{' '}
                                                    {formatDate(
                                                        document.uploaded_at,
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        {!isPreview && (
                                            <div className="flex gap-3">
                                                <Button
                                                    variant="ghost"
                                                    className="rounded-[5px]"
                                                    onClick={() =>
                                                        setDocumentToView({
                                                            title: document.title,
                                                            url: `/files/client-documents/${document.id}`,
                                                        })
                                                    }
                                                >
                                                    View
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    className="rounded-[5px] text-red-700 hover:bg-red-600 hover:text-white"
                                                    onClick={() =>
                                                        setDocumentToDelete(
                                                            document,
                                                        )
                                                    }
                                                >
                                                    Delete
                                                </Button>
                                                <a
                                                    href={`/files/client-documents/${document.id}?download=1`}
                                                    download
                                                >
                                                    <Button
                                                        variant="outline"
                                                        className="rounded-[5px]"
                                                    >
                                                        <Download />
                                                    </Button>
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                ))
                            ) : (
                                <p className="text-center text-muted-foreground">
                                    No documents to show
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {intakeDocuments.length > 0 && (
                    <Card className="rounded-[10px]">
                        <CardContent className="p-5">
                            <p>Intake Application Documents</p>
                            <p className="text-sm text-muted-foreground">
                                Files submitted with the intake application
                            </p>

                            <div className="mt-10 grid grid-cols-1 gap-5">
                                {intakeDocuments.map((document) => (
                                    <div
                                        key={document.id}
                                        className="flex flex-row justify-between rounded-sm border p-3"
                                    >
                                        <div className="flex flex-row items-center gap-3">
                                            <div className="flex rounded-[5px] bg-secondary-orange/10 p-3 text-primary">
                                                <ReceiptText />
                                            </div>
                                            <div>
                                                <p>{document.name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {document.type} • Uploaded{' '}
                                                    {formatDate(
                                                        document.uploaded_at,
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        {!isPreview && (
                                            <div className="flex gap-3">
                                                <Button
                                                    variant="ghost"
                                                    className="rounded-[5px]"
                                                    onClick={() =>
                                                        setDocumentToView({
                                                            title: document.name,
                                                            url: `/files/intake-documents/${document.id}`,
                                                        })
                                                    }
                                                >
                                                    View
                                                </Button>
                                                <a
                                                    href={`/files/intake-documents/${document.id}?download=1`}
                                                    download
                                                >
                                                    <Button
                                                        variant="outline"
                                                        className="rounded-[5px]"
                                                    >
                                                        <Download />
                                                    </Button>
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <DocumentViewerModal
                document={documentToView}
                onClose={() => setDocumentToView(null)}
            />

            {!isPreview && (
                <>
                    <UploadDocumentModal
                        client={client}
                        isOpen={uploadOpen}
                        onClose={() => setUploadOpen(false)}
                    />
                    <DeleteDocumentModal
                        document={documentToDelete}
                        isOpen={documentToDelete !== null}
                        onClose={() => setDocumentToDelete(null)}
                    />
                </>
            )}
        </>
    );
}
