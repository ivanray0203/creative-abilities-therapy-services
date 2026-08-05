import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { ConsentDocument } from '@/types/consent';

interface ConsentModalProps {
    consent: ConsentDocument;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    replacements?: {
        primaryParentlegalName?: string;
        childsFullName?: string;
        childsDateOfBirth?: string;
        todaysDate?: string;
    };
}

/**
 * Shows a single consent document's clauses for review, ported from
 * cats-frontend/src/modals/ConsentModal.tsx. Placeholder tokens in each
 * clause's text_template are replaced with the applicant's in-progress
 * form values instead of a client-side fetch.
 */
export function ConsentModal({
    consent,
    open,
    onOpenChange,
    replacements = {},
}: ConsentModalProps) {
    const formatClause = (text: string) =>
        text
            .replace(
                '{primaryParentlegalName}',
                replacements.primaryParentlegalName || '',
            )
            .replace(
                '{parentguardianFull}',
                replacements.primaryParentlegalName || '',
            )
            .replace('{childsFullName}', replacements.childsFullName || '')
            .replace(
                '{childsDateOfBirth}',
                replacements.childsDateOfBirth || '',
            )
            .replace(
                '{todaysDate}',
                replacements.todaysDate || new Date().toLocaleDateString(),
            );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="w-full sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center justify-between">
                        {consent.title}
                        <Badge variant="outline" className="ml-2">
                            v{consent.version}
                        </Badge>
                    </DialogTitle>
                    <DialogDescription>
                        Purpose: {consent.purpose} • Effective Date:{' '}
                        {new Date(consent.effective_date).toLocaleDateString()}
                    </DialogDescription>
                </DialogHeader>

                <ScrollArea className="mt-4 h-[400px] pr-2">
                    <div className="space-y-4">
                        {[...consent.clauses]
                            .sort((a, b) => a.order - b.order)
                            .map((clause) => (
                                <div
                                    key={clause.id}
                                    className="rounded-lg border bg-muted p-3"
                                >
                                    <p className="mb-1 font-semibold">
                                        Clause {clause.order}
                                    </p>
                                    <p className="text-sm">
                                        {formatClause(clause.text_template)}
                                    </p>
                                </div>
                            ))}
                    </div>
                </ScrollArea>

                <DialogFooter className="mt-4">
                    <Button
                        variant="default"
                        onClick={() => onOpenChange(false)}
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default ConsentModal;
