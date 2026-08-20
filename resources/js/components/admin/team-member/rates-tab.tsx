import { router } from '@inertiajs/react';
import { RotateCcw, Save, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
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
import { Switch } from '@/components/ui/switch';
import type { InvoiceService, InvoiceServiceRate } from '@/types/invoice';
import type { TeamMember } from '@/types/team-member';

/** Sheet disciplines, in the order the rate card groups them. */
const DISCIPLINE_LABELS: Record<string, string> = {
    slp: 'Speech-Language Pathology',
    psych: 'Psychology',
    ot: 'Occupational Therapy',
    pt: 'Physiotherapy',
    bc: 'Behavioural Consulting',
    aide: 'Aide Services',
    other: 'Other',
};

/** The two rate columns a line can be billed under. */
type RateDraft = { rate_fscd: string; rate_private: string };

const toDraft = (rate: InvoiceServiceRate | undefined): RateDraft => ({
    rate_fscd: rate?.rate_fscd ?? '',
    rate_private: rate?.rate_private ?? '',
});

const formatRate = (rate: string | null): string =>
    rate === null ? '—' : `$${rate}`;

/**
 * Per-therapist overrides of the invoice rate card.
 *
 * Every input starts blank; the published rate is shown beside the line
 * rather than inside the field, so an empty box reads as "no override" and
 * never as a value that was typed. Blank means "bill this line at the clinic
 * rate", so clearing a field is how an override is removed.
 */
export default function RatesTab({
    teamMember,
    invoiceServices,
    invoiceServiceRates,
}: {
    teamMember: TeamMember;
    invoiceServices: InvoiceService[];
    invoiceServiceRates: Record<number, InvoiceServiceRate>;
}) {
    const [drafts, setDrafts] = useState<Record<number, RateDraft>>(() =>
        Object.fromEntries(
            invoiceServices.map((service) => [
                service.id,
                toDraft(invoiceServiceRates[service.id]),
            ]),
        ),
    );
    const [search, setSearch] = useState('');
    const [discipline, setDiscipline] = useState('all');
    const [customOnly, setCustomOnly] = useState(false);
    const [processing, setProcessing] = useState(false);

    const setRate = (
        serviceId: number,
        column: keyof RateDraft,
        value: string,
    ) => {
        setDrafts((current) => ({
            ...current,
            [serviceId]: { ...current[serviceId], [column]: value },
        }));
    };

    const hasCustomRate = (serviceId: number): boolean => {
        const draft = drafts[serviceId];

        return draft.rate_fscd !== '' || draft.rate_private !== '';
    };

    const customCount = invoiceServices.filter((service) =>
        hasCustomRate(service.id),
    ).length;

    const disciplines = useMemo(
        () => [
            ...new Set(invoiceServices.map((service) => service.discipline)),
        ],
        [invoiceServices],
    );

    const visibleServices = invoiceServices.filter((service) => {
        const matchesSearch =
            search === '' ||
            service.name.toLowerCase().includes(search.toLowerCase());
        const matchesDiscipline =
            discipline === 'all' || service.discipline === discipline;
        const matchesCustom = !customOnly || hasCustomRate(service.id);

        return matchesSearch && matchesDiscipline && matchesCustom;
    });

    const groupedServices = visibleServices.reduce<
        Record<string, InvoiceService[]>
    >((groups, service) => {
        groups[service.discipline] = [
            ...(groups[service.discipline] ?? []),
            service,
        ];

        return groups;
    }, {});

    const resetAll = () => {
        setDrafts((current) =>
            Object.fromEntries(
                Object.keys(current).map((serviceId) => [
                    serviceId,
                    { rate_fscd: '', rate_private: '' },
                ]),
            ),
        );
    };

    const submit = () => {
        setProcessing(true);

        router.patch(
            `/admin/team/${teamMember.id}/rates`,
            {
                rates: invoiceServices.map((service) => ({
                    invoice_service_id: service.id,
                    rate_fscd: drafts[service.id].rate_fscd || null,
                    rate_private: drafts[service.id].rate_private || null,
                })),
            },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <div className="flex flex-col justify-between gap-3 md:flex-row md:items-start">
                        <div>
                            <p>Invoice Service Rates</p>
                            <p className="text-sm text-muted-foreground">
                                Set what this team member bills per service.
                                Leave a field blank to use the standard rate.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                className="rounded-[5px]"
                                onClick={resetAll}
                                disabled={processing || customCount === 0}
                            >
                                <RotateCcw /> Clear All Custom Rates
                            </Button>
                            <Button
                                className="rounded-[5px]"
                                onClick={submit}
                                disabled={processing}
                            >
                                <Save /> Save Rates
                            </Button>
                        </div>
                    </div>

                    <div className="mt-5 flex flex-col gap-3 md:flex-row md:items-center">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search services"
                                className="rounded-[10px] pl-9"
                            />
                        </div>

                        <Select
                            value={discipline}
                            onValueChange={setDiscipline}
                        >
                            <SelectTrigger className="rounded-[10px] md:w-64">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All disciplines
                                </SelectItem>
                                {disciplines.map((value) => (
                                    <SelectItem key={value} value={value}>
                                        {DISCIPLINE_LABELS[value] ?? value}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <div className="flex items-center gap-2">
                            <Switch
                                id="custom-rates-only"
                                checked={customOnly}
                                onCheckedChange={setCustomOnly}
                            />
                            <Label
                                htmlFor="custom-rates-only"
                                className="text-sm whitespace-nowrap"
                            >
                                Custom only ({customCount})
                            </Label>
                        </div>
                    </div>

                    <div className="mt-8 grid grid-cols-1 gap-8">
                        {Object.entries(groupedServices).map(
                            ([group, services]) => (
                                <div key={group}>
                                    <p className="mb-3 text-sm font-medium">
                                        {DISCIPLINE_LABELS[group] ?? group}
                                    </p>

                                    <div className="grid grid-cols-1 gap-2">
                                        {services.map((service) => (
                                            <div
                                                key={service.id}
                                                className="flex flex-col gap-3 rounded-sm border p-3 lg:flex-row lg:items-center lg:justify-between"
                                            >
                                                <div className="lg:max-w-md">
                                                    <p className="flex flex-wrap items-center gap-2 text-sm">
                                                        {service.name}
                                                        {hasCustomRate(
                                                            service.id,
                                                        ) && (
                                                            <Badge variant="secondary">
                                                                Custom
                                                            </Badge>
                                                        )}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        Standard: FSCD{' '}
                                                        {formatRate(
                                                            service.rate_fscd,
                                                        )}{' '}
                                                        • Private/Insurance{' '}
                                                        {formatRate(
                                                            service.rate_private,
                                                        )}
                                                    </p>
                                                </div>

                                                <div className="flex gap-3">
                                                    <div>
                                                        <Label
                                                            htmlFor={`fscd-${service.id}`}
                                                            className="text-xs text-muted-foreground"
                                                        >
                                                            FSCD Rate
                                                        </Label>
                                                        <Input
                                                            id={`fscd-${service.id}`}
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            inputMode="decimal"
                                                            value={
                                                                drafts[
                                                                    service.id
                                                                ].rate_fscd
                                                            }
                                                            onChange={(event) =>
                                                                setRate(
                                                                    service.id,
                                                                    'rate_fscd',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="mt-1 w-32 rounded-[10px]"
                                                        />
                                                    </div>

                                                    <div>
                                                        <Label
                                                            htmlFor={`private-${service.id}`}
                                                            className="text-xs whitespace-nowrap text-muted-foreground"
                                                        >
                                                            Private/Insurance
                                                            Rate
                                                        </Label>
                                                        <Input
                                                            id={`private-${service.id}`}
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            inputMode="decimal"
                                                            value={
                                                                drafts[
                                                                    service.id
                                                                ].rate_private
                                                            }
                                                            onChange={(event) =>
                                                                setRate(
                                                                    service.id,
                                                                    'rate_private',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="mt-1 w-32 rounded-[10px]"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ),
                        )}

                        {visibleServices.length === 0 && (
                            <p className="text-center text-muted-foreground">
                                No services to show
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
