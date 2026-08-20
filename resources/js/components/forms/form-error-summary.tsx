import { AlertCircle } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';

/**
 * Every validation error the server sent, in one place at the top of the
 * form.
 *
 * Long forms scatter their fields across collapsed sections, so an inline
 * message under the offending input can sit somewhere the applicant never
 * scrolls to — the submission just appears to do nothing. This lists them
 * all, so what went wrong is visible without hunting.
 */
export default function FormErrorSummary({
    errors,
    title = 'Please fix the following before submitting',
}: {
    errors: Record<string, string>;
    title?: string;
}) {
    const messages = Object.entries(errors).filter(([, message]) =>
        Boolean(message),
    );

    if (messages.length === 0) {
        return null;
    }

    return (
        <Card
            role="alert"
            className="rounded-[10px] border-destructive bg-destructive/5"
        >
            <CardContent className="p-5">
                <div className="flex items-center gap-2 text-destructive">
                    <AlertCircle className="h-5 w-5 shrink-0" />
                    <p className="font-semibold">{title}</p>
                </div>
                <ul className="mt-3 list-disc space-y-1 pl-9 text-sm text-destructive">
                    {messages.map(([field, message]) => (
                        <li key={field}>{message}</li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}
