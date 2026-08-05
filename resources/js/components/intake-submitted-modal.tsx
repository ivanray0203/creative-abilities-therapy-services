import { Link } from '@inertiajs/react';
import { Calendar, CheckCircle2, Mail, Phone } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface IntakeSubmittedModalProps {
    isOpen: boolean;
    onClose: () => void;
    referenceNumber?: string | null;
}

/**
 * Confirmation shown after a successful intake submission, ported from
 * cats-frontend/src/modals/IntakeSubmittedModal.tsx. Displays the
 * `reference_number` returned by IntakeApplicationController@store instead
 * of the reference's toast-only confirmation, and uses an Inertia `Link`
 * instead of react-router's `useNavigate`.
 */
export default function IntakeSubmittedModal({
    isOpen,
    onClose,
    referenceNumber,
}: IntakeSubmittedModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] w-full max-w-md overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold text-primary">
                        <div className="mb-4 flex flex-col items-center justify-center gap-2">
                            <div className="rounded-full bg-primary p-2">
                                <CheckCircle2 className="h-10 w-10 text-white" />
                            </div>
                            <p>Thank You for Submitting!</p>
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <p className="text-center text-muted-foreground">
                    Your intake form has been successfully submitted.
                </p>

                {referenceNumber && (
                    <p className="text-center text-sm font-medium text-primary">
                        Reference Number:{' '}
                        <span className="font-semibold">{referenceNumber}</span>
                    </p>
                )}

                <div className="mt-4 space-y-4 rounded-sm bg-secondary-orange/10 p-5">
                    <div className="flex flex-row items-center gap-2">
                        <Mail className="text-primary-orange" />
                        <p>
                            <span className="font-bold">
                                Confirmation Email:{' '}
                            </span>
                            You will receive a confirmation email shortly with a
                            copy of your submission.
                        </p>
                    </div>
                    <div className="flex flex-row items-center gap-2">
                        <Phone className="text-primary-orange" />
                        <p>
                            <span className="font-bold">Next Steps: </span>
                            Your intake coordinator will review your information
                            and contact you within 1-2 business days.
                        </p>
                    </div>
                    <div className="flex flex-row items-center gap-2">
                        <Calendar className="text-primary-orange" />
                        <p>
                            <span className="font-bold">What to Expect: </span>
                            We'll discuss your child's needs, answer any
                            questions, and match you with the perfect therapist.
                        </p>
                    </div>
                </div>

                <p className="text-center text-sm">
                    If you don't receive a confirmation email within 24 hours,
                    please check your spam folder or contact us at{' '}
                    <span className="text-primary">
                        info@creativeabilitiestherapy.ca
                    </span>
                </p>

                <DialogFooter className="mt-6">
                    <Button
                        asChild
                        className="w-full rounded-[10px] bg-primary text-white hover:bg-primary/90"
                    >
                        <Link href="/" onClick={onClose}>
                            Close &amp; Return to Home
                        </Link>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
