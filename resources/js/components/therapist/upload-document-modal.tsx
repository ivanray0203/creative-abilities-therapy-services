import { useForm } from '@inertiajs/react';
import { File as FileIcon, Plus } from 'lucide-react';
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

interface UploadDocumentForm {
    type: string;
    name: string;
    file: File | null;
}

export default function UploadDocumentModal({
    isOpen,
    onClose,
}: {
    isOpen: boolean;
    onClose: () => void;
}) {
    const [type, setType] = useState('');

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<UploadDocumentForm>({
            type: '',
            name: '',
            file: null,
        });

    const closeAndReset = () => {
        reset();
        clearErrors();
        setType('');
        onClose();
    };

    const submit = () => {
        post('/therapist/profile/documents', {
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
                        <Input
                            id="document-type"
                            value={type}
                            placeholder="e.g. Police Information Check"
                            className="mt-2 rounded-[10px]"
                            onChange={(event) => {
                                setType(event.target.value);
                                setData('type', event.target.value);
                            }}
                        />
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
