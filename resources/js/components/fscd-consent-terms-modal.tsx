import { CheckCircle2, ChevronDown } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';

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
    /**
     * Fired once the applicant has scrolled to the end of the clauses. The
     * parent keeps the flag, so re-opening a document already read doesn't
     * re-lock its checkbox.
     */
    onRead: () => void;
    /** Whether this document was already read in an earlier visit. */
    alreadyRead: boolean;
}

/** Treat "within 16px of the end" as the bottom, so sub-pixel scroll heights
 * and browser zoom rounding don't leave the checkbox permanently locked. */
const BOTTOM_THRESHOLD_PX = 16;

/**
 * Presents the full terms behind one required FSCD consent and unlocks its
 * checkbox only once the applicant has read to the bottom. Consent to these
 * terms is a funding condition, so the form should not let it be given
 * sight-unseen.
 */
export default function FscdConsentTermsModal({
    terms,
    open,
    onOpenChange,
    onRead,
    alreadyRead,
}: FscdConsentTermsModalProps) {
    /*
     * The parent mounts this only while a document is open, and keys it by
     * document id, so initialising from `alreadyRead` is enough: re-opening
     * an unread document restarts the requirement, and one already read
     * stays unlocked.
     */
    const [reachedBottom, setReachedBottom] = useState(alreadyRead);

    /*
     * Both triggers below can fire repeatedly — scroll events stream, and the
     * callback ref re-attaches whenever the parent re-renders. Report once per
     * mount so neither can drive a render loop through the parent's state.
     */
    const hasReported = useRef(alreadyRead);

    const markRead = useCallback(() => {
        if (hasReported.current) {
            return;
        }

        hasReported.current = true;
        setReachedBottom(true);
        onRead();
    }, [onRead]);

    const handleScroll = (event: React.UIEvent<HTMLDivElement>) => {
        const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;

        if (scrollTop + clientHeight >= scrollHeight - BOTTOM_THRESHOLD_PX) {
            markRead();
        }
    };

    /*
     * On a tall viewport the clauses can fit without scrolling, which would
     * leave the checkbox locked with nothing left to scroll. Measure the
     * element as it mounts and count that as read.
     */
    const measureOnMount = useCallback(
        (node: HTMLDivElement | null) => {
            if (node && node.scrollHeight <= node.clientHeight) {
                markRead();
            }
        },
        [markRead],
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="w-full sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{terms.title}</DialogTitle>
                    <DialogDescription>
                        Please read these terms in full. The consent checkbox
                        unlocks once you reach the end.
                    </DialogDescription>
                </DialogHeader>

                <div
                    id="fscd-terms-scroll"
                    ref={measureOnMount}
                    onScroll={handleScroll}
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

                <DialogFooter className="mt-4 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    {reachedBottom ? (
                        <p className="flex items-center gap-2 text-sm text-primary">
                            <CheckCircle2 className="h-4 w-4" />
                            You can now check this consent.
                        </p>
                    ) : (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ChevronDown className="h-4 w-4" />
                            Scroll to the end to unlock this consent.
                        </p>
                    )}

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
