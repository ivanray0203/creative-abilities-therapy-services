import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle, Edit, Printer, Send, Trash } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';

import InvoicePdfViewModal from '@/components/invoices/invoice-pdf-view-modal';
import InvoicePrintable from '@/components/invoices/invoice-printable';
import MarkAsPaidModal from '@/components/invoices/mark-as-paid-modal';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import ClientLayout from '@/layouts/client-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Invoice } from '@/types/invoice';

interface InvoiceShowProps {
    invoice: Invoice;
    role: 'admin' | 'therapist' | 'client';
}

const BASE_PATHS: Record<InvoiceShowProps['role'], string> = {
    admin: '/admin/invoices',
    therapist: '/therapist/invoices',
    client: '/client/invoices',
};

/**
 * Shared invoice detail page, rendered for admin/therapist/client with
 * role-gated actions — reference: cats-frontend/src/pages/admin/InvoiceDetailPage.tsx.
 */
export default function InvoiceShow({ invoice, role }: InvoiceShowProps) {
    const [pdfOpen, setPdfOpen] = useState(false);
    const [markPaidOpen, setMarkPaidOpen] = useState(false);
    const basePath = BASE_PATHS[role];
    const canEdit = role !== 'client' && invoice.status !== 'paid';
    const canResend = role !== 'client';
    const canMarkPaid =
        role === 'admin' && !['paid', 'refunded'].includes(invoice.status);
    const canDelete = role === 'admin';

    const resend = () => {
        router.post(`${basePath}/${invoice.id}/resend`, undefined, {
            preserveScroll: true,
        });
    };

    const destroy = () => {
        router.delete(`${basePath}/${invoice.id}`);
    };

    return (
        <>
            <Head title={`Invoice ${invoice.invoice_id ?? invoice.id}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold sm:text-3xl">
                            Invoice {invoice.invoice_id ?? `#${invoice.id}`}
                        </h1>
                        <Link href={basePath} className="text-sm text-primary">
                            Back to Invoices
                        </Link>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() => setPdfOpen(true)}
                        >
                            <Printer /> Print / PDF
                        </Button>
                        {canResend && (
                            <Button
                                variant="outline"
                                className="rounded-[10px]"
                                onClick={resend}
                            >
                                <Send /> Resend
                            </Button>
                        )}
                        {canMarkPaid && (
                            <Button
                                className="rounded-[10px]"
                                onClick={() => setMarkPaidOpen(true)}
                            >
                                <CheckCircle /> Mark as Paid
                            </Button>
                        )}
                        {canEdit && (
                            <Button
                                variant="outline"
                                className="rounded-[10px]"
                                asChild
                            >
                                <Link href={`${basePath}/${invoice.id}/edit`}>
                                    <Edit /> Edit
                                </Link>
                            </Button>
                        )}
                        {canDelete && (
                            <Button
                                variant="destructive"
                                className="rounded-[10px]"
                                onClick={destroy}
                            >
                                <Trash /> Delete
                            </Button>
                        )}
                    </div>
                </div>

                <InvoicePrintable invoice={invoice} />
            </div>

            <InvoicePdfViewModal
                invoice={invoice}
                isOpen={pdfOpen}
                onClose={() => setPdfOpen(false)}
            />
            {canMarkPaid && (
                <MarkAsPaidModal
                    invoice={invoice}
                    isOpen={markPaidOpen}
                    onClose={() => setMarkPaidOpen(false)}
                />
            )}
        </>
    );
}

/**
 * Picks the layout via `usePage()` rather than the `page.props` argument
 * Inertia passes to `.layout()` — that argument comes back `undefined`
 * during client-side page swaps, which crashed navigation entirely when
 * read synchronously here.
 */
function InvoiceShowLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: InvoiceShowProps['role'] }>().props;

    if (role === 'admin') {
        return <AdminLayout>{children}</AdminLayout>;
    }

    if (role === 'therapist') {
        return <TherapistLayout>{children}</TherapistLayout>;
    }

    return <ClientLayout>{children}</ClientLayout>;
}

InvoiceShow.layout = (page: React.ReactNode) => (
    <InvoiceShowLayout>{page}</InvoiceShowLayout>
);
