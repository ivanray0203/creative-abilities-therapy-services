import { Link } from '@inertiajs/react';
import { CheckCircle2, CircleCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface ApplicationSubmittedModalProps {
    isOpen: boolean;
    onClose: () => void;
    referenceNumber?: string | null;
}

/**
 * Confirmation shown after a successful submission, ported from
 * cats-frontend/src/modals/ApplicationSubmittedModal.tsx. Displays the
 * `reference_number` returned by CareerApplicationController@store instead
 * of the reference's toast-only confirmation.
 */
export default function ApplicationSubmittedModal({ isOpen, onClose, referenceNumber }: ApplicationSubmittedModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold text-primary">
                        <div className="mb-4 flex flex-col items-center justify-center gap-2">
                            <div className="rounded-full bg-primary p-2">
                                <CheckCircle2 className="h-10 w-10 text-white" />
                            </div>
                            <p>Application Submitted Successfully!</p>
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <p className="text-center text-muted-foreground">
                    Thank you for your application! We appreciate your interest in joining the Creative Abilities Therapy
                    Services team.
                </p>

                {referenceNumber && (
                    <p className="text-center text-sm font-medium text-primary">
                        Reference Number: <span className="font-semibold">{referenceNumber}</span>
                    </p>
                )}

                <div className="mt-4 space-y-4 rounded-sm bg-secondary-orange/10 p-5">
                    <h4 className="font-semibold text-primary">What's Next?</h4>
                    <ul className="list-disc space-y-1 pl-5">
                        <p className="flex flex-row gap-2">
                            <CircleCheck className="text-primary-orange" /> Our team will review your application within 5-7
                            business days.
                        </p>
                        <p className="flex flex-row gap-2">
                            <CircleCheck className="text-primary-orange" />
                            We'll contact you via email if your qualifications match our needs.
                        </p>
                        <p className="flex flex-row gap-2">
                            <CircleCheck className="text-primary-orange" /> Please check your spam folder if you don't hear from
                            us.
                        </p>
                    </ul>
                </div>

                <DialogFooter className="mt-6">
                    <Button asChild className="w-full rounded-[10px] bg-primary text-white hover:bg-primary/90">
                        <Link href="/careers" onClick={onClose}>
                            Close &amp; Return to Careers
                        </Link>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
