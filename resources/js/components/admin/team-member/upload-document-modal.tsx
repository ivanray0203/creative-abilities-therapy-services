import { useForm } from '@inertiajs/react';
import { AlertCircleIcon, File as FileIcon, Plus } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TeamMember } from '@/types/team-member';

interface UploadDocumentForm {
    type: string;
    name: string;
    file: File | null;
}

/** The Select value standing for "a type that isn't on the list". */
const OTHER_TYPE = '__other__';

/**
 * Reference: cats-frontend's UploadDocumentModal, scoped to a team member's
 * own documents.
 *
 * The type is picked from the documents this person is required to hand in,
 * because an upload only counts towards a requirement when its type matches
 * the name exactly (see App\Support\OnboardingDocuments). "Other" takes a
 * free-text type for anything else.
 */
export default function UploadDocumentModal({
    teamMember,
    documentTypes,
    isOpen,
    onClose,
    uploadUrl,
}: {
    teamMember: TeamMember;
    /** The required documents, offered as the document type. */
    documentTypes: string[];
    isOpen: boolean;
    onClose: () => void;
    /** Defaults to the admin route; pass the therapist self-service route when used from the Profile page. */
    uploadUrl?: string;
}) {
    const [selection, setSelection] = useState('');

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<UploadDocumentForm>({
            type: '',
            name: '',
            file: null,
        });

    const closeAndReset = () => {
        reset();
        clearErrors();
        setSelection('');
        onClose();
    };

    const submit = () => {
        post(uploadUrl ?? `/admin/team/${teamMember.id}/documents`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="max-h-[90vh] w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle>
                        <span className="flex flex-row gap-3 text-xl font-bold text-charcoal-gray">
                            <FileIcon className="text-primary" /> Upload
                            Document
                        </span>
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-3">
                    <div>
                        <Label htmlFor="document-type">Document Type *</Label>
                        <Select
                            value={selection}
                            onValueChange={(value) => {
                                setSelection(value);
                                setData(
                                    'type',
                                    value === OTHER_TYPE ? '' : value,
                                );
                            }}
                        >
                            <SelectTrigger
                                id="document-type"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select document type" />
                            </SelectTrigger>
                            <SelectContent>
                                {documentTypes.map((documentType) => (
                                    <SelectItem
                                        key={documentType}
                                        value={documentType}
                                    >
                                        {documentType}
                                    </SelectItem>
                                ))}
                                <SelectItem value={OTHER_TYPE}>
                                    Other document
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {selection === OTHER_TYPE && (
                            <Input
                                id="document-type-other"
                                value={data.type}
                                placeholder="Type of document"
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('type', event.target.value)
                                }
                            />
                        )}
                        {errors.type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.type}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="document-file">
                            Upload Document (pdf, doc, docx, jpg, jpeg, png) *
                        </Label>
                        <Input
                            id="document-file"
                            type="file"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('file', event.target.files?.[0] ?? null)
                            }
                        />
                        {errors.file && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.file}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="document-title">
                            Document Title (optional)
                        </Label>
                        <Input
                            id="document-title"
                            value={data.name}
                            placeholder="Title"
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                    </div>

                    <div className="mt-4 rounded-[5px] border border-blue-600 bg-blue-100 p-5">
                        <p className="flex flex-row gap-3 text-xs text-blue-700">
                            <AlertCircleIcon /> This document is added to the
                            team member&apos;s compliance record.
                        </p>
                    </div>
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        className="w-full rounded-[10px]"
                        variant="outline"
                        onClick={closeAndReset}
                    >
                        Cancel
                    </Button>
                    <Button
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={processing}
                    >
                        <Plus />{' '}
                        {processing ? 'Uploading...' : 'Upload Document'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
