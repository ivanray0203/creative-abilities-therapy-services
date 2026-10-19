import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { FscdConsentTerms } from '@/lib/content/fscd-consent-terms';

interface FscdConsentTermsModalProps {
    terms: FscdConsentTerms;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

/**
 * Presents the full terms behind the required FSCD consent for the
 * applicant to review. Reading them is encouraged, not enforced: the
 * consent checkbox on the form is available whether or not this is opened.
 */
export default function FscdConsentTermsModal({
    terms,
    open,
    onOpenChange,
}: FscdConsentTermsModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="w-full sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{terms.title}</DialogTitle>
                    <DialogDescription>
                        Please read these terms before giving your consent.
                    </DialogDescription>
                </DialogHeader>

                <div
                    id="fscd-terms-scroll"
                    className="mt-4 max-h-[50vh] space-y-4 overflow-y-auto pr-2"
                >
                    {terms.clauses.map((clause) => (
                        <div
                            key={clause.heading}
                            className="rounded-lg border bg-muted p-3"
                        >
                            <p className="mb-1 font-semibold">
                                {clause.heading}
                            </p>
                            <p className="text-sm">{clause.body}</p>
                        </div>
                    ))}

                    <p className="pb-1 text-center text-sm text-muted-foreground">
                        End of terms
                    </p>
                </div>

                <DialogFooter className="mt-4">
                    <Button
                        variant="default"
                        type="button"
                        onClick={() => onOpenChange(false)}
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
