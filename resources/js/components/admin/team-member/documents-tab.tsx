import {
    AlertTriangle,
    CheckCircle,
    Download,
    Plus,
    ReceiptText,
} from 'lucide-react';
import { useState } from 'react';

import DeleteDocumentModal from '@/components/admin/team-member/delete-document-modal';
import UploadDocumentModal from '@/components/admin/team-member/upload-document-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/helpers';
import type { TeamMember, TeamMemberDocument } from '@/types/team-member';

/** Reference: cats-frontend/src/pages/admin/teamMemberTabs/DocumentsTab.tsx */
export default function DocumentsTab({
    teamMember,
    documents,
    missingDocuments,
    uploadUrl,
    deleteUrl,
}: {
    teamMember: TeamMember;
    documents: TeamMemberDocument[];
    missingDocuments: string[];
    /** Defaults to the admin routes; pass therapist self-service routes when used from the Profile page. */
    uploadUrl?: string;
    deleteUrl?: (documentId: number) => string;
}) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const [documentToDelete, setDocumentToDelete] =
        useState<TeamMemberDocument | null>(null);

    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            {missingDocuments.length > 0 ? (
                <div className="flex flex-col gap-2 rounded-[5px] border border-red-400 bg-red-50 p-4 text-red-800">
                    <p className="flex items-center gap-2 font-medium">
                        <AlertTriangle className="h-4 w-4" /> Missing Required
                        Documents
                    </p>
                    <ul className="ml-6 list-disc text-sm">
                        {missingDocuments.map((doc) => (
                            <li key={doc}>{doc}</li>
                        ))}
                    </ul>
                    {missingDocuments.includes(
                        'Police Information Check with Vulnerable Sector Check',
                    ) && (
                        <p className="text-sm">
                            A current Police Information Check with Vulnerable
                            Sector Check is required for all staff working with
                            clients.
                        </p>
                    )}
                </div>
            ) : (
                <div className="flex items-center gap-2 rounded-[5px] border border-green-400 bg-green-50 p-4 text-green-800">
                    <CheckCircle className="h-4 w-4" /> All required documents
                    have been submitted.
                </div>
            )}

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <div className="flex flex-col justify-between md:flex-row">
                        <div>
                            <p>Uploaded Documents</p>
                            <p className="text-sm text-muted-foreground">
                                Compliance and onboarding documents
                            </p>
                        </div>

                        <Button
                            variant="outline"
                            className="mt-5 rounded-[5px] bg-gray-100 md:mt-0"
                            onClick={() => setUploadOpen(true)}
                        >
                            <Plus />
                            Upload Document
                        </Button>
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
                                                {document.doc_type} • Uploaded{' '}
                                                {formatDate(
                                                    document.uploaded_at,
                                                )}
                                            </p>
                                        </div>
                                    </div>

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
                                                setDocumentToDelete(document)
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

            <UploadDocumentModal
                teamMember={teamMember}
                isOpen={uploadOpen}
                onClose={() => setUploadOpen(false)}
                uploadUrl={uploadUrl}
            />
            <DeleteDocumentModal
                document={documentToDelete}
                isOpen={documentToDelete !== null}
                onClose={() => setDocumentToDelete(null)}
                deleteUrl={
                    documentToDelete && deleteUrl
                        ? deleteUrl(documentToDelete.id)
                        : undefined
                }
            />
        </div>
    );
}
