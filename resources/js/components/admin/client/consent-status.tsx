import { router } from '@inertiajs/react';
import { CheckCircle2, Clock, FileSignature, Send } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/helpers';
import type { Client } from '@/types/client';

/**
 * Where the client is in the consent flow: not sent, sent and awaiting the
 * parent's signed upload, or signed. The link goes out on its own once every
 * service is approved; the button covers a lost email or an expired link.
 */
export default function ConsentStatus({ client }: { client: Client }) {
    const [sending, setSending] = useState(false);

    const send = () => {
        router.post(
            `/admin/clients/${client.id}/consent`,
            {},
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
            },
        );
    };

    if (client.consent_signed_at) {
        return (
            <div className="flex flex-col gap-3 rounded-[10px] border border-green-300 bg-green-50 p-4 text-sm md:flex-row md:items-center md:justify-between">
                <div className="flex items-center gap-3">
                    <CheckCircle2 className="h-5 w-5 shrink-0 text-green-600" />
                    <p>
                        <span className="font-semibold">Consent signed</span> on{' '}
                        {formatDate(client.consent_signed_at)}. The signed copy
                        is on the Documents tab.
                    </p>
                </div>
                {/*
                 * For a wrong upload spotted after the parent's link has
                 * lapsed: a fresh link lets them replace it.
                 */}
                <Button
                    variant="outline"
                    className="rounded-[10px] bg-white"
                    onClick={send}
                    disabled={sending}
                >
                    <Send /> Send Re-upload Link
                </Button>
            </div>
        );
    }

    const sent = client.consent_sent_at !== null;

    return (
        <div className="flex flex-col gap-3 rounded-[10px] border border-yellow-400 bg-yellow-50 p-4 text-sm md:flex-row md:items-center md:justify-between">
            <div className="flex items-center gap-3">
                {sent ? (
                    <Clock className="h-5 w-5 shrink-0 text-yellow-600" />
                ) : (
                    <FileSignature className="h-5 w-5 shrink-0 text-yellow-600" />
                )}
                <p>
                    {sent ? (
                        <>
                            <span className="font-semibold">
                                Awaiting signed consent.
                            </span>{' '}
                            Link sent to the parent on{' '}
                            {formatDate(client.consent_sent_at)}.
                        </>
                    ) : (
                        <>
                            <span className="font-semibold">
                                Consent not sent yet.
                            </span>{' '}
                            It goes out automatically once every service is
                            approved.
                        </>
                    )}
                </p>
            </div>
            <Button
                variant="outline"
                className="rounded-[10px] bg-white"
                onClick={send}
                disabled={sending}
            >
                <Send /> {sent ? 'Resend Consent' : 'Send Consent Now'}
            </Button>
        </div>
    );
}
