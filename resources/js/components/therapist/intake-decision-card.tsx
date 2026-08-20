import { router } from '@inertiajs/react';
import { CheckCircle2, CircleX } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

/**
 * Reference: cats-frontend/src/pages/therapist/intakeTabs/IntakeDecision.tsx.
 * Unlike the reference, the decline reason is actually sent with the
 * request — the reference captures it in local state but never includes it
 * in the API call.
 */
export default function IntakeDecisionCard({
    intakeId,
    service,
}: {
    intakeId: number;
    service: string | null;
}) {
    const [decision, setDecision] = useState<'accept' | 'decline' | null>(null);
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const submit = () => {
        if (!decision) {
            return;
        }

        setSubmitting(true);

        if (decision === 'accept') {
            router.post(
                `/therapist/intake/${intakeId}/therapist-approve`,
                { service },
                {
                    preserveScroll: true,
                    onFinish: () => setSubmitting(false),
                },
            );
        } else {
            router.post(
                `/therapist/intake/${intakeId}/therapist-reject`,
                { service, notes: reason },
                {
                    preserveScroll: true,
                    onFinish: () => setSubmitting(false),
                },
            );
        }
    };

    return (
        <Card className="rounded-[10px] border-cyan-600 bg-gradient-to-br from-cyan-50 to-transparent">
            <CardContent className="space-y-5 p-5">
                <div>
                    <p className="flex flex-row items-center gap-3">
                        Your Decision{service ? ` — ${service}` : ''}
                    </p>
                    <p className="text-muted-foreground">
                        Please review all information and make your decision
                        regarding this intake
                    </p>
                </div>

                <div className="border-b pb-4">
                    <div
                        className={`flex cursor-pointer flex-row items-start gap-3 rounded-lg border p-3 transition ${decision === 'accept' ? 'border-green-600 bg-green-50' : ''}`}
                        onClick={() => setDecision('accept')}
                    >
                        <CheckCircle2
                            className={`h-5 w-5 ${decision === 'accept' ? 'text-green-700' : 'text-green-500'}`}
                        />
                        <div>
                            <p className="font-bold">Accept this Intake</p>
                            <p className="text-sm text-muted-foreground">
                                I can provide the requested services and will
                                add this client to my caseload.
                            </p>
                        </div>
                    </div>

                    <div
                        className={`mt-3 flex cursor-pointer flex-row items-start gap-3 rounded-lg border p-3 transition ${decision === 'decline' ? 'border-red-600 bg-red-50' : ''}`}
                        onClick={() => setDecision('decline')}
                    >
                        <CircleX
                            className={`h-5 w-5 ${decision === 'decline' ? 'text-red-700' : 'text-red-500'}`}
                        />
                        <div>
                            <p className="font-bold">Decline this Intake</p>
                            <p className="text-sm text-muted-foreground">
                                Unable to take this case at this time. Please
                                provide a reason below.
                            </p>
                        </div>
                    </div>

                    {decision === 'decline' && (
                        <div className="mt-4">
                            <Label>Reason for Declining</Label>
                            <Textarea
                                rows={4}
                                value={reason}
                                onChange={(event) =>
                                    setReason(event.target.value)
                                }
                                placeholder="Please provide a clear reason (e.g., at capacity, schedule conflict, outside specialty area, etc.)"
                            />
                            <p className="mt-3 text-sm text-muted-foreground">
                                This will be shared with the admin team for
                                reassignment purposes.
                            </p>
                        </div>
                    )}
                </div>

                <div className="flex justify-end">
                    <Button
                        className="rounded-[5px]"
                        disabled={!decision || submitting}
                        onClick={submit}
                    >
                        {submitting ? 'Submitting...' : 'Submit Decision'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
