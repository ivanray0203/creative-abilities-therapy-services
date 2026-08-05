import { Download, Plus, ReceiptText } from 'lucide-react';
import { useState } from 'react';

import DeleteDocumentModal from '@/components/admin/delete-document-modal';
import UploadDocumentModal from '@/components/admin/upload-document-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Intake, IntakeDocument } from '@/types/intake';

/** Reference: cats-frontend/src/pages/admin/intake/Documents.tsx */
export default function DocumentsTab({
    intake,
    isPreview,
}: {
    intake: Intake;
    isPreview?: boolean;
}) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const [documentToDelete, setDocumentToDelete] =
        useState<IntakeDocument | null>(null);

    const documents = intake.documents ?? [];

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
                                    Files attached to this intake application
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
                                                <p>{document.name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {document.type} • Uploaded{' '}
                                                    {
                                                        document.uploaded_at?.split(
                                                            'T',
                                                        )[0]
                                                    }
                                                </p>
                                            </div>
                                        </div>

                                        {!isPreview && (
                                            <div className="flex gap-3">
                                                <Button
                                                    variant="ghost"
                                                    className="rounded-[5px]"
                                                    onClick={() =>
                                                        window.open(
                                                            document.drive_web_view ??
                                                                undefined,
                                                            '_blank',
                                                        )
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
                                                    href={
                                                        document.drive_web_view ??
                                                        undefined
                                                    }
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
            </div>

            {!isPreview && (
                <>
                    <UploadDocumentModal
                        intake={intake}
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
