import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileCheck2, PenLine, Printer, Trash } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';

import SignTimesheetModal from '@/components/timesheets/sign-timesheet-modal';
import TimesheetDocumentModal from '@/components/timesheets/timesheet-document-modal';
import TimesheetPrintable from '@/components/timesheets/timesheet-printable';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import ClientLayout from '@/layouts/client-layout';
import TherapistLayout from '@/layouts/therapist-layout';
import type { Timesheet } from '@/types/timesheet';

interface TimesheetShowProps {
    timesheet: Timesheet;
    role: 'admin' | 'therapist' | 'client';
}

const BASE_PATHS: Record<TimesheetShowProps['role'], string> = {
    admin: '/admin/timesheets',
    therapist: '/therapist/timesheets',
    client: '/client/timesheets',
};

/** Shared timesheet detail page, with role-gated actions. */
export default function TimesheetShow({ timesheet, role }: TimesheetShowProps) {
    const [pdfOpen, setPdfOpen] = useState(false);
    const [signOpen, setSignOpen] = useState(false);
    const basePath = BASE_PATHS[role];
    // The parent signs their own copy once; afterwards everyone just sees
    // that the signed PDF is on file.
    const canSign = role === 'client' && !timesheet.parent_signature;
    const canDelete = role === 'admin';
    /*
     * Staff only. Telling the parent their own signature is on the form is
     * news to nobody, and the link behind it is the raw Drive copy the
     * clinic files — their own reading of it is the form below and the
     * Print / PDF button above.
     */
    const showsFiledCopy = role !== 'client';

    const destroy = () => {
        if (!confirm(`Remove timesheet ${timesheet.timesheet_number}?`)) {
            return;
        }

        router.delete(`${basePath}/${timesheet.id}`);
    };

    return (
        <>
            <Head title={`Timesheet ${timesheet.timesheet_number}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold sm:text-3xl">
                            Timesheet {timesheet.timesheet_number}
                        </h1>
                        <Link href={basePath} className="text-sm text-primary">
                            Back to Timesheets
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
                                This timesheet needs your signature
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Check the hours below, then sign — it goes
                                straight back to the clinic.
                            </p>
                        </div>
                        <Button
                            id="open-sign-timesheet"
                            className="rounded-[10px]"
                            onClick={() => setSignOpen(true)}
                        >
                            <PenLine /> Sign Timesheet
                        </Button>
                    </div>
                )}

                {showsFiledCopy && timesheet.signed_timesheet && (
                    <div className="flex items-center gap-3 rounded-[10px] border bg-muted/40 p-4">
                        <FileCheck2 className="h-5 w-5 shrink-0 text-primary" />
                        <p className="text-sm">
                            Signed by the parent.{' '}
                            <a
                                href={timesheet.signed_timesheet}
                                target="_blank"
                                rel="noreferrer"
                                className="text-primary underline"
                            >
                                View the signed PDF
                            </a>
                        </p>
                    </div>
                )}

                <TimesheetPrintable timesheet={timesheet} />
            </div>

            <TimesheetDocumentModal
                timesheet={timesheet}
                basePath={basePath}
                isOpen={pdfOpen}
                onClose={() => setPdfOpen(false)}
            />
            {canSign && (
                <SignTimesheetModal
                    timesheetId={timesheet.id}
                    parentName={
                        timesheet.client?.original_intake
                            ?.primary_parent_name ?? ''
                    }
                    isOpen={signOpen}
                    onClose={() => setSignOpen(false)}
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
function TimesheetShowLayout({ children }: PropsWithChildren) {
    const { role } = usePage<{ role: TimesheetShowProps['role'] }>().props;

    if (role === 'admin') {
        return <AdminLayout>{children}</AdminLayout>;
    }

    if (role === 'therapist') {
        return <TherapistLayout>{children}</TherapistLayout>;
    }

    return <ClientLayout>{children}</ClientLayout>;
}

TimesheetShow.layout = (page: React.ReactNode) => (
    <TimesheetShowLayout>{page}</TimesheetShowLayout>
);
