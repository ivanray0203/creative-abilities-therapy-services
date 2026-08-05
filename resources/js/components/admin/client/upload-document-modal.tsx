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
import type { Client } from '@/types/client';

/** Document purposes offered for clients (reference: modals/UploadDocumentModal.tsx). */
const DOCUMENT_PURPOSES = [
    'FSCD Approval Letter',
    'FSCD Service Contract',
    'Insurance Card Copy',
    'Explanation of Benefits (E.O.B)',
    'Medical Report',
    'Assesment Report',
    "Doctor's Prescription",
    'Diagnostic Report',
    'Treatment Plan',
    'Goal Setting Document',
    'Progress Report',
    'Consent Form',
    'Invoice/Receipt',
    'Service Contract',
    'Other',
];

interface UploadDocumentForm {
    type: string;
    name: string;
    file: File | null;
}

export default function UploadDocumentModal({
    client,
    isOpen,
    onClose,
}: {
    client: Client;
    isOpen: boolean;
    onClose: () => void;
}) {
    const [purpose, setPurpose] = useState('');
    const [otherPurpose, setOtherPurpose] = useState('');

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<UploadDocumentForm>({
            type: '',
            name: '',
            file: null,
        });

    const closeAndReset = () => {
        reset();
        clearErrors();
        setPurpose('');
        setOtherPurpose('');
        onClose();
    };

    const submit = () => {
        post(`/admin/clients/${client.id}/documents`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    const applyPurpose = (value: string) => {
        setPurpose(value);
        setData('type', value === 'Other' ? otherPurpose : value);
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
                    <p className="text-sm font-normal text-muted-foreground">
                        Upload a document related to this client and specify its
                        purpose.
                    </p>
                </DialogHeader>

                <div className="space-y-3">
                    <div>
                        <Label htmlFor="purpose">Document Purpose *</Label>
                        <Select value={purpose} onValueChange={applyPurpose}>
                            <SelectTrigger
                                id="purpose"
                                className="rounded-[5px]"
                            >
                                <SelectValue placeholder="Select Document Type" />
                            </SelectTrigger>
                            <SelectContent>
                                {DOCUMENT_PURPOSES.map((option) => (
                                    <SelectItem key={option} value={option}>
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.type}
                            </p>
                        )}
                    </div>

                    {purpose === 'Other' && (
                        <div>
                            <Label htmlFor="other-purpose">Other *</Label>
                            <Input
                                id="other-purpose"
                                value={otherPurpose}
                                placeholder="Please enter Details"
                                className="mt-2 rounded-[10px]"
                                onChange={(event) => {
                                    setOtherPurpose(event.target.value);
                                    setData('type', event.target.value);
                                }}
                            />
                        </div>
                    )}

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
                            <AlertCircleIcon /> This document will be added to
                            the client&apos;s document history and will be
                            accessible to assigned therapists.
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
