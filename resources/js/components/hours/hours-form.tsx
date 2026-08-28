import { useForm } from '@inertiajs/react';
import { Plus, Trash } from 'lucide-react';
import { useMemo } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Client } from '@/types/client';
import { HOUR_COLUMNS } from '@/types/timesheet';

interface DayForm {
    entry_date: string;
    hourly_respite_hours: string;
    community_support_hours: string;
    bda_direct_hours: string;
    bda_indirect_hours: string;
    notes: string;
}

interface HoursFormData {
    client_id: string;
    entries: DayForm[];
}

const EMPTY_DAY: DayForm = {
    entry_date: '',
    hourly_respite_hours: '',
    community_support_hours: '',
    bda_direct_hours: '',
    bda_indirect_hours: '',
    notes: '',
};

/**
 * The "Log Hours" form: one child, and a row of hours for each day worked.
 *
 * Deliberately not built on the billing form — an aide's record carries no
 * rate card and no funding stream, so the only thing the two forms share is
 * the client picker and the add/remove-row affordances.
 *
 * A day already logged for this child is corrected rather than duplicated;
 * the server upserts on (aide, client, date), which is what the printed
 * grid's one-row-per-day layout assumes.
 */
export default function HoursForm({ clients }: { clients: Client[] }) {
    const { data, setData, post, processing, errors, transform } =
        useForm<HoursFormData>({
            client_id: '',
            entries: [{ ...EMPTY_DAY }],
        });

    /** Every column of one day added together, as the form's total reads. */
    const dayTotal = (day: DayForm): number =>
        HOUR_COLUMNS.reduce(
            (sum, column) => sum + (parseFloat(day[column.entryKey]) || 0),
            0,
        );

    const total = useMemo(
        () => data.entries.reduce((sum, day) => sum + dayTotal(day), 0),
        [data.entries],
    );

    const updateDay = (index: number, field: keyof DayForm, value: string) => {
        setData(
            'entries',
            data.entries.map((day, dayIndex) =>
                dayIndex === index ? { ...day, [field]: value } : day,
            ),
        );
    };

    const addDay = () => {
        setData('entries', [...data.entries, { ...EMPTY_DAY }]);
    };

    const removeDay = (index: number) => {
        setData(
            'entries',
            data.entries.filter((_, dayIndex) => dayIndex !== index),
        );
    };

    const submit = () => {
        transform((form) => ({
            ...form,
            entries: form.entries.map((day) => ({
                ...day,
                // A blank column is nought hours, not a missing value.
                hourly_respite_hours: day.hourly_respite_hours || 0,
                community_support_hours: day.community_support_hours || 0,
                bda_direct_hours: day.bda_direct_hours || 0,
                bda_indirect_hours: day.bda_indirect_hours || 0,
                notes: day.notes || null,
            })),
        }));

        post('/therapist/hours');
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6 lg:grid-cols-3">
            <Card className="rounded-[10px] lg:col-span-2">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div className="md:max-w-sm">
                        <Label htmlFor="hours-client">Client *</Label>
                        <Select
                            value={data.client_id}
                            onValueChange={(value) =>
                                setData('client_id', value)
                            }
                        >
                            <SelectTrigger
                                id="hours-client"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select client" />
                            </SelectTrigger>
                            <SelectContent>
                                {clients.map((client) => (
                                    <SelectItem
                                        key={client.id}
                                        id={`hours-client-${client.id}`}
                                        value={String(client.id)}
                                    >
                                        {client.original_intake
                                            ? `${client.original_intake.child_first_name} ${client.original_intake.child_last_name}`
                                            : `Client #${client.id}`}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="mt-1 text-xs text-muted-foreground">
                            One time sheet covers one child, so hours are logged
                            a child at a time.
                        </p>
                        {errors.client_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.client_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <div className="mb-2 flex items-center justify-between">
                            <Label>Days *</Label>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="rounded-[10px]"
                                onClick={addDay}
                            >
                                <Plus className="h-4 w-4" /> Add Day
                            </Button>
                        </div>
                        <p className="mb-3 text-xs text-muted-foreground">
                            One row per day. Logging a day you have already
                            logged for this child corrects it.
                        </p>

                        <div className="grid grid-cols-1 gap-3">
                            {data.entries.map((day, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-1 gap-3 rounded-[10px] border p-3 md:grid-cols-[1.4fr_repeat(4,1fr)_auto]"
                                >
                                    <div>
                                        <Label htmlFor={`hours-date-${index}`}>
                                            Date
                                        </Label>
                                        <Input
                                            id={`hours-date-${index}`}
                                            type="date"
                                            value={day.entry_date}
                                            className="mt-1 rounded-[10px]"
                                            onChange={(event) =>
                                                updateDay(
                                                    index,
                                                    'entry_date',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {errors[
                                            `entries.${index}.entry_date` as keyof typeof errors
                                        ] && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {
                                                    errors[
                                                        `entries.${index}.entry_date` as keyof typeof errors
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    {HOUR_COLUMNS.map((column) => (
                                        <div key={column.entryKey}>
                                            <Label
                                                htmlFor={`hours-${column.rowKey}-${index}`}
                                            >
                                                {column.label}
                                            </Label>
                                            <Input
                                                id={`hours-${column.rowKey}-${index}`}
                                                type="number"
                                                // Hours are fractional:
                                                // 0.25 and 1.5 are real.
                                                min={0}
                                                max={24}
                                                step="0.25"
                                                inputMode="decimal"
                                                placeholder="0"
                                                value={day[column.entryKey]}
                                                className="mt-1 rounded-[10px]"
                                                onChange={(event) =>
                                                    updateDay(
                                                        index,
                                                        column.entryKey,
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    ))}

                                    <div className="flex items-end gap-2">
                                        <div className="hidden md:block">
                                            <Label>Total</Label>
                                            <p className="mt-1 flex h-9 items-center font-medium">
                                                {dayTotal(day).toFixed(2)}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="rounded-[10px] text-red-700 hover:bg-red-600 hover:text-white"
                                            onClick={() => removeDay(index)}
                                            disabled={data.entries.length === 1}
                                        >
                                            <Trash className="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div className="md:col-span-6">
                                        <Label htmlFor={`hours-notes-${index}`}>
                                            Notes (Optional)
                                        </Label>
                                        <Textarea
                                            id={`hours-notes-${index}`}
                                            rows={2}
                                            value={day.notes}
                                            className="mt-1 rounded-[10px]"
                                            onChange={(event) =>
                                                updateDay(
                                                    index,
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    {errors[
                                        `entries.${index}.hourly_respite_hours` as keyof typeof errors
                                    ] && (
                                        <p className="text-sm text-destructive md:col-span-6">
                                            {
                                                errors[
                                                    `entries.${index}.hourly_respite_hours` as keyof typeof errors
                                                ]
                                            }
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                        {errors.entries && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.entries}
                            </p>
                        )}
                    </div>

                    <div className="flex gap-3">
                        <Button
                            type="button"
                            className="rounded-[10px]"
                            onClick={submit}
                            disabled={processing}
                        >
                            Save
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="font-bold text-primary">Summary</p>

                    <div className="mt-5 space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Days</span>
                            <span>{data.entries.length}</span>
                        </div>
                        {HOUR_COLUMNS.map((column) => (
                            <div
                                key={column.rowKey}
                                className="flex justify-between"
                            >
                                <span className="text-muted-foreground">
                                    {column.label}
                                </span>
                                <span>
                                    {data.entries
                                        .reduce(
                                            (sum, day) =>
                                                sum +
                                                (parseFloat(
                                                    day[column.entryKey],
                                                ) || 0),
                                            0,
                                        )
                                        .toFixed(2)}
                                </span>
                            </div>
                        ))}
                        <div className="flex justify-between border-t pt-2 font-bold">
                            <span>Total Hours</span>
                            <span>{total.toFixed(2)}</span>
                        </div>
                    </div>

                    <p className="mt-4 text-xs text-muted-foreground">
                        These hours stay loose until you generate a timesheet
                        for a period and the parent signs it.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
