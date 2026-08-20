import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface MissingFieldsModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    missingFields: string[];
}

export function formatLabel(field: string): string {
    return field
        .split('.')
        .map((segment) => {
            if (/^\d+$/.test(segment)) {
                return `#${Number(segment) + 1}`;
            }

            return segment
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase());
        })
        .join(' ');
}

/**
 * Lists the still-missing required fields before the applicant can preview
 * or submit, ported from cats-frontend/src/modals/MissingFieldsModal.tsx.
 * Generic/reusable — shared by the career application and intake forms.
 */
export default function MissingFieldsModal({
    open,
    onOpenChange,
    missingFields,
}: MissingFieldsModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Incomplete Application</DialogTitle>
                    <DialogDescription>
                        Please complete the following required fields before
                        previewing:
                    </DialogDescription>
                </DialogHeader>

                <div className="mt-4 max-h-60 space-y-2 overflow-y-auto">
                    {missingFields.map((field) => (
                        <p key={field} className="text-sm">
                            • {formatLabel(field)}
                        </p>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}
