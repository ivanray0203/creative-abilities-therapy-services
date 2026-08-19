import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CheckCircle,
    Edit,
    FileCheck2,
    PenLine,
    Printer,
    Send,
    Trash,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';

import InvoiceDocumentModal from '@/components/invoices/invoice-document-modal';
import InvoicePdfViewModal from '@/components/invoices/invoice-pdf-view-modal';
import InvoicePrintable from '@/components/invoices/invoice-printable';
import LinkedInvoicePanel from '@/components/invoices/linked-invoice-panel';
import MarkAsPaidModal from '@/components/invoices/mark-as-paid-modal';
import SignInvoiceModal from '@/components/invoices/sign-invoice-modal';
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
    const [signOpen, setSignOpen] = useState(false);
    const basePath = BASE_PATHS[role];
    const canEdit = role !== 'client' && invoice.status !== 'paid';
    const canResend = role !== 'client';
    const canMarkPaid =
        role === 'admin' && !['paid', 'refunded'].includes(invoice.status);
    const canDelete = role === 'admin';
    // The parent signs their own copy once; afterwards everyone just sees
    // that the signed PDF is on file.
    const canSign = role === 'client' && !invoice.signed_invoice;
    /*
     * Both sides of the ledger are issued as a real PDF — the clinic's bill
     * to the family, and the therapist's to the clinic — so the viewer opens
     * the document itself rather than a browser-print rendering of the page.
     * An invoice raised before either was filed is rendered on request by the
     * controller, so there is always something to show.
     */
    const hasDocument = invoice.billed_by !== null;

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

                {canSign && (
                    <div className="flex flex-col gap-3 rounded-[10px] border border-primary/30 bg-primary/5 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-semibold">
                                This invoice needs your signature
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Sign it here and it goes straight back to the
                                clinic.
                            </p>
                        </div>
                        <Button
                            id="open-sign-invoice"
                            className="rounded-[10px]"
                            onClick={() => setSignOpen(true)}
                        >
                            <PenLine /> Sign Invoice
                        </Button>
                    </div>
                )}

                {invoice.signed_invoice && (
                    <div className="flex items-center gap-3 rounded-[10px] border bg-muted/40 p-4">
                        <FileCheck2 className="h-5 w-5 shrink-0 text-primary" />
                        <p className="text-sm">
                            Signed by the parent.{' '}
                            <a
                                href={invoice.signed_invoice}
                                target="_blank"
                                rel="noreferrer"
                                className="text-primary underline"
                            >
                                View the signed PDF
                            </a>
                        </p>
                    </div>
                )}

                {role === 'admin' && (
                    <LinkedInvoicePanel invoice={invoice} basePath={basePath} />
                )}

                <InvoicePrintable invoice={invoice} />
            </div>

            {hasDocument ? (
                <InvoiceDocumentModal
                    invoice={invoice}
                    basePath={basePath}
                    isOpen={pdfOpen}
                    onClose={() => setPdfOpen(false)}
                />
            ) : (
                <InvoicePdfViewModal
                    invoice={invoice}
                    isOpen={pdfOpen}
                    onClose={() => setPdfOpen(false)}
                />
            )}
            {canSign && (
                <SignInvoiceModal
                    invoiceId={invoice.id}
                    parentName={invoice.bill_to_name ?? ''}
                    isOpen={signOpen}
                    onClose={() => setSignOpen(false)}
                />
            )}
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
