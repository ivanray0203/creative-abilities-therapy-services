import { Head, router } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
import { useState } from 'react';

import HourTrackingSheet from '@/components/hour-tracking/sheet';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type {
    HourTrackingFilters,
    HourTrackingReport,
    HourTrackingTherapistOption,
} from '@/types/hour-tracking';

const BASE_PATH = '/admin/hour-tracking';

/** Radix will not take an empty string as a value, so "everyone" needs a word. */
const ALL_THERAPISTS = 'all';

/**
 * Phase 21 — the clinic's hour-tracking sheet, across every therapist.
 *
 * The office's own spreadsheet, read off the contracts and the session ledger:
 * one section per service, one row per child's contract, a column per month.
 * Narrowing to one therapist gives exactly what that therapist sees on their
 * own page (resources/js/pages/hour-tracking/index.tsx).
 */
export default function AdminHourTracking({
    report,
    therapists,
    filters,
}: {
    report: HourTrackingReport;
    therapists: HourTrackingTherapistOption[];
    filters: HourTrackingFilters;
}) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const selected = filters.therapist_id
        ? String(filters.therapist_id)
        : ALL_THERAPISTS;

    const visit = (overrides: Record<string, string | number | undefined>) => {
        router.get(
            BASE_PATH,
            {
                from,
                to,
                therapist_id: filters.therapist_id ?? undefined,
                ...overrides,
            },
            { preserveState: true, replace: true },
        );
    };

    const pdfQuery = new URLSearchParams({
        from: filters.from,
        to: filters.to,
    });

    if (filters.therapist_id) {
        pdfQuery.set('therapist_id', String(filters.therapist_id));
    }

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
                            Every contract the clinic has authorized, and what
                            each month has drawn
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        asChild
                    >
                        <a
                            href={`${BASE_PATH}/pdf?${pdfQuery.toString()}`}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <FileDown /> Download PDF
                        </a>
                    </Button>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="sm:flex-1">
                            <Label htmlFor="hour-tracking-therapist">
                                Therapist
                            </Label>
                            <Select
                                value={selected}
                                onValueChange={(value) =>
                                    visit({
                                        therapist_id:
                                            value === ALL_THERAPISTS
                                                ? undefined
                                                : value,
                                    })
                                }
                            >
                                <SelectTrigger
                                    id="hour-tracking-therapist"
                                    className="mt-2 rounded-[10px] sm:w-64"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_THERAPISTS}>
                                        All therapists
                                    </SelectItem>
                                    {therapists.map((therapist) => (
                                        <SelectItem
                                            key={therapist.id}
                                            value={String(therapist.id)}
                                        >
                                            {therapist.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
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
                        <Button
                            className="rounded-[10px]"
                            onClick={() => visit({})}
                        >
                            Apply
                        </Button>
                    </div>
                </Card>

                <HourTrackingSheet
                    report={report}
                    showTherapist={filters.therapist_id == null}
                />
            </div>
        </>
    );
}

AdminHourTracking.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
