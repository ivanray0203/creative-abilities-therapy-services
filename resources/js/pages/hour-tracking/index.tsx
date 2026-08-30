import { Head, router } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
import { useState } from 'react';

import HourTrackingSheet from '@/components/hour-tracking/sheet';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TherapistLayout from '@/layouts/therapist-layout';
import type {
    HourTrackingFilters,
    HourTrackingReport,
} from '@/types/hour-tracking';

const BASE_PATH = '/therapist/hour-tracking';

/**
 * Phase 21 — the therapist's own hour-tracking sheet.
 *
 * The clinic keeps this in a spreadsheet: one section per service, one row per
 * contract, a column per month, and what is left at the end of the row. This
 * is the same sheet read off the contracts and the session ledger, so it
 * cannot drift from what was actually booked.
 *
 * Admin reads the same sheet across every therapist, on
 * resources/js/pages/admin/hour-tracking.tsx.
 */
export default function HourTrackingIndex({
    report,
    filters,
}: {
    report: HourTrackingReport;
    filters: HourTrackingFilters;
}) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const apply = () => {
        router.get(
            BASE_PATH,
            { from, to },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Hour Tracking" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Hour Tracking
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            Contracted hours you are authorized on, and what
                            each month has drawn
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        asChild
                    >
                        <a
                            href={`${BASE_PATH}/pdf?from=${filters.from}&to=${filters.to}`}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <FileDown /> Download PDF
                        </a>
                    </Button>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div>
                            <Label htmlFor="hour-tracking-from">From</Label>
                            <Input
                                id="hour-tracking-from"
                                type="date"
                                className="mt-2 rounded-[10px] sm:w-48"
                                value={from}
                                onChange={(event) =>
                                    setFrom(event.target.value)
                                }
                            />
                        </div>
                        <div>
                            <Label htmlFor="hour-tracking-to">To</Label>
                            <Input
                                id="hour-tracking-to"
                                type="date"
                                className="mt-2 rounded-[10px] sm:w-48"
                                value={to}
                                onChange={(event) => setTo(event.target.value)}
                            />
                        </div>
                        <Button className="rounded-[10px]" onClick={apply}>
                            Apply
                        </Button>
                    </div>
                </Card>

                <HourTrackingSheet report={report} />
            </div>
        </>
    );
}

HourTrackingIndex.layout = (page: React.ReactNode) => (
    <TherapistLayout>{page}</TherapistLayout>
);
