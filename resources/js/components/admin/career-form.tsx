import { useForm } from '@inertiajs/react';
import { Plus, Save, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { Career, CareerOffer } from '@/types/career';

/**
 * Paragraph and bullet lists are edited as one-entry-per-line text, then
 * split back into arrays on submit. Keeping the raw text in form state means
 * a half-typed blank line is not swallowed mid-keystroke.
 */
function toLines(values?: string[] | null): string {
    return (values ?? []).join('\n');
}

function fromLines(value: string): string[] {
    return value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);
}

interface SectionInput {
    title: string;
    intro: string;
    lead_in: string;
    items: string;
    closing: string;
}

interface ExtraInput extends SectionInput {
    paragraphs: string;
}

const EMPTY_SECTION: SectionInput = {
    title: '',
    intro: '',
    lead_in: '',
    items: '',
    closing: '',
};

interface CareerFormData {
    position: string;
    location: string;
    schedule: string;
    contract: string;
    rate: string;
    short_description: string;
    about_description: string;
    responsibilities: string[];
    qualifications: string[];
    skills: string[];
    benefits: string[];
    highlights: string[];
    required_documents: string[];
    is_active: boolean;
    sort_order: string;
    due_date: string;
    level: string;
    hours: string;

    // Posting narrative — assembled into `detail` on submit.
    intro: string;
    role_summary: string;
    responsibilities_lead_in: string;
    qualifications_lead_in: string;
    qualifications_note: string;
    collaboration: SectionInput;
    offers: CareerOffer[];
    fscd_enabled: boolean;
    fscd: SectionInput;
    extras: ExtraInput[];
    contractor_title: string;
    contractor_paragraphs: string;
    closing_title: string;
    closing: string;
}

function sectionInput(
    section?: {
        title?: string;
        intro?: string;
        lead_in?: string;
        items?: string[];
        closing?: string;
    } | null,
): SectionInput {
    return {
        title: section?.title ?? '',
        intro: section?.intro ?? '',
        lead_in: section?.lead_in ?? '',
        items: toLines(section?.items),
        closing: section?.closing ?? '',
    };
}

function initialValues(career?: Career | null): CareerFormData {
    const detail = career?.detail;

    return {
        position: career?.position ?? '',
        location: career?.location ?? '',
        schedule: career?.schedule ?? '',
        contract: career?.contract ?? '',
        rate: career?.rate ?? '',
        short_description: career?.short_description ?? '',
        about_description: career?.about_description ?? '',
        responsibilities: career?.responsibilities ?? [],
        qualifications: career?.qualifications ?? [],
        skills: career?.skills ?? [],
        benefits: career?.benefits ?? [],
        highlights: career?.highlights ?? [],
        required_documents: career?.required_documents ?? [],
        is_active: career?.is_active ?? true,
        sort_order: String(career?.sort_order ?? 0),
        due_date: career?.due_date ?? '',
        level: career?.level ?? '',
        hours: career?.hours ?? '',

        intro: toLines(detail?.intro),
        role_summary: detail?.role_summary ?? '',
        responsibilities_lead_in: detail?.responsibilities_lead_in ?? '',
        qualifications_lead_in: detail?.qualifications_lead_in ?? '',
        qualifications_note: detail?.qualifications_note ?? '',
        collaboration: sectionInput(detail?.collaboration),
        offers: detail?.offers ?? [],
        fscd_enabled: detail?.fscd != null,
        fscd: sectionInput(detail?.fscd),
        extras: (detail?.extras ?? []).map((extra) => ({
            ...sectionInput(extra),
            paragraphs: toLines(extra.paragraphs),
        })),
        contractor_title: detail?.contractor?.title ?? '',
        contractor_paragraphs: toLines(detail?.contractor?.paragraphs),
        closing_title: detail?.closing_title ?? '',
        closing: detail?.closing ?? '',
    };
}

type ListField =
    | 'responsibilities'
    | 'qualifications'
    | 'skills'
    | 'benefits'
    | 'highlights'
    | 'required_documents';

function TagListField({
    label,
    placeholder,
    values,
    onAdd,
    onRemove,
}: {
    label: string;
    placeholder?: string;
    values: string[];
    onAdd: (value: string) => void;
    onRemove: (value: string) => void;
}) {
    const [input, setInput] = useState('');

    const submit = () => {
        if (!input.trim()) {
            return;
        }

        onAdd(input.trim());
        setInput('');
    };

    return (
        <div>
            <Label>{label}</Label>
            <div className="mt-2 flex gap-2">
                <Input
                    value={input}
                    placeholder={placeholder}
                    className="rounded-[10px]"
                    onChange={(event) => setInput(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            submit();
                        }
                    }}
                />
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-[10px]"
                    onClick={submit}
                >
                    <Plus />
                </Button>
            </div>

            <div className="mt-3 flex flex-wrap gap-2">
                {values.map((value) => (
                    <span
                        key={value}
                        className="flex items-center gap-1 rounded-[5px] bg-gray-100 px-2 py-1 text-sm"
                    >
                        {value}
                        <button type="button" onClick={() => onRemove(value)}>
                            <X className="h-3 w-3" />
                        </button>
                    </span>
                ))}
            </div>
        </div>
    );
}

/** Textarea whose value is a list, one entry per line. */
function LinesField({
    id,
    label,
    hint,
    value,
    rows = 4,
    onChange,
}: {
    id: string;
    label: string;
    hint?: string;
    value: string;
    rows?: number;
    onChange: (value: string) => void;
}) {
    return (
        <div>
            <Label htmlFor={id}>{label}</Label>
            {hint && (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            )}
            <Textarea
                id={id}
                rows={rows}
                value={value}
                className="mt-2 rounded-[10px]"
                onChange={(event) => onChange(event.target.value)}
            />
        </div>
    );
}

/** The prose-around-a-list shape used by the collaboration and FSCD blocks. */
function SectionFields({
    idPrefix,
    section,
    onChange,
}: {
    idPrefix: string;
    section: SectionInput;
    onChange: (section: SectionInput) => void;
}) {
    return (
        <div className="grid grid-cols-1 gap-4">
            <div>
                <Label htmlFor={`${idPrefix}-title`}>Heading</Label>
                <Input
                    id={`${idPrefix}-title`}
                    value={section.title}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        onChange({ ...section, title: event.target.value })
                    }
                />
            </div>

            <div>
                <Label htmlFor={`${idPrefix}-intro`}>Intro</Label>
                <Textarea
                    id={`${idPrefix}-intro`}
                    value={section.intro}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        onChange({ ...section, intro: event.target.value })
                    }
                />
            </div>

            <div>
                <Label htmlFor={`${idPrefix}-lead-in`}>
                    Lead-in above the list
                </Label>
                <Input
                    id={`${idPrefix}-lead-in`}
                    value={section.lead_in}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        onChange({ ...section, lead_in: event.target.value })
                    }
                />
            </div>

            <LinesField
                id={`${idPrefix}-items`}
                label="List items"
                hint="One per line."
                value={section.items}
                onChange={(items) => onChange({ ...section, items })}
            />

            <div>
                <Label htmlFor={`${idPrefix}-closing`}>Closing paragraph</Label>
                <Textarea
                    id={`${idPrefix}-closing`}
                    value={section.closing}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        onChange({ ...section, closing: event.target.value })
                    }
                />
            </div>
        </div>
    );
}

/** Admin "Add/Edit Position" form, backing the `careers` table. */
export default function CareerForm({ career }: { career?: Career | null }) {
    const isEdit = career != null;

    const { data, setData, transform, post, put, processing, errors } =
        useForm<CareerFormData>(initialValues(career));

    const submit = () => {
        transform((form) => {
            const {
                intro,
                role_summary,
                responsibilities_lead_in,
                qualifications_lead_in,
                qualifications_note,
                collaboration,
                offers,
                fscd_enabled,
                fscd,
                extras,
                contractor_title,
                contractor_paragraphs,
                closing_title,
                closing,
                sort_order,
                ...rest
            } = form;

            const section = (input: SectionInput) => ({
                title: input.title,
                intro: input.intro,
                lead_in: input.lead_in,
                items: fromLines(input.items),
                closing: input.closing,
            });

            return {
                ...rest,
                sort_order: Number(sort_order) || 0,
                detail: {
                    intro: fromLines(intro),
                    role_summary,
                    responsibilities_lead_in,
                    qualifications_lead_in,
                    qualifications_note,
                    collaboration: section(collaboration),
                    offers,
                    fscd: fscd_enabled ? section(fscd) : null,
                    extras: extras.map((extra) => ({
                        ...section(extra),
                        paragraphs: fromLines(extra.paragraphs),
                    })),
                    contractor: {
                        title: contractor_title,
                        paragraphs: fromLines(contractor_paragraphs),
                    },
                    closing_title,
                    closing,
                },
            };
        });

        if (isEdit && career) {
            put(`/admin/careers/${career.id}`);

            return;
        }

        post('/admin/careers');
    };

    const addToList = (field: ListField, value: string) => {
        setData(field, [...data[field], value]);
    };

    const removeFromList = (field: ListField, value: string) => {
        setData(
            field,
            data[field].filter((entry) => entry !== value),
        );
    };

    const updateOffer = (index: number, offer: CareerOffer) => {
        setData(
            'offers',
            data.offers.map((entry, i) => (i === index ? offer : entry)),
        );
    };

    const updateExtra = (index: number, extra: ExtraInput) => {
        setData(
            'extras',
            data.extras.map((entry, i) => (i === index ? extra : entry)),
        );
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6">
            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <p className="font-bold text-primary md:col-span-2">
                        Position Details
                    </p>

                    <div>
                        <Label htmlFor="career-position">Position *</Label>
                        <Input
                            id="career-position"
                            value={data.position}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('position', event.target.value)
                            }
                        />
                        {errors.position && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.position}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-location">Location *</Label>
                        <Input
                            id="career-location"
                            value={data.location}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('location', event.target.value)
                            }
                        />
                        {errors.location && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.location}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-schedule">Schedule *</Label>
                        <Input
                            id="career-schedule"
                            placeholder="e.g. Part-time"
                            value={data.schedule}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('schedule', event.target.value)
                            }
                        />
                        {errors.schedule && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.schedule}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-contract">Contract *</Label>
                        <Input
                            id="career-contract"
                            placeholder="e.g. Independent Contractor"
                            value={data.contract}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('contract', event.target.value)
                            }
                        />
                        {errors.contract && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.contract}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-rate">Rate *</Label>
                        <Input
                            id="career-rate"
                            placeholder="e.g. Starting at $65.44/hour"
                            value={data.rate}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('rate', event.target.value)
                            }
                        />
                        {errors.rate && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.rate}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-level">
                            Level (shown on the listing card)
                        </Label>
                        <Input
                            id="career-level"
                            placeholder="e.g. Contract Position"
                            value={data.level}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('level', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="career-hours">Hours</Label>
                        <Input
                            id="career-hours"
                            value={data.hours}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('hours', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="career-sort-order">Listing order</Label>
                        <Input
                            id="career-sort-order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('sort_order', event.target.value)
                            }
                        />
                        {errors.sort_order && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.sort_order}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-due-date">
                            Application Due Date
                        </Label>
                        <Input
                            id="career-due-date"
                            type="date"
                            value={data.due_date}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('due_date', event.target.value)
                            }
                        />
                        {errors.due_date && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.due_date}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-between md:col-span-2">
                        <Label htmlFor="career-active">
                            Visible on public Careers page
                        </Label>
                        <Switch
                            id="career-active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <p className="font-bold text-primary">Descriptions</p>

                    <div>
                        <Label htmlFor="career-short-desc">
                            Short Description *
                        </Label>
                        <Textarea
                            id="career-short-desc"
                            value={data.short_description}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('short_description', event.target.value)
                            }
                        />
                        {errors.short_description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.short_description}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="career-about-desc">
                            About Description *
                        </Label>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Opens the &ldquo;Join Creative Abilities Therapy
                            Services&rdquo; section on the posting.
                        </p>
                        <Textarea
                            id="career-about-desc"
                            value={data.about_description}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('about_description', event.target.value)
                            }
                        />
                        {errors.about_description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.about_description}
                            </p>
                        )}
                    </div>

                    <LinesField
                        id="career-intro"
                        label="Additional intro paragraphs"
                        hint="One paragraph per line, shown after the About Description."
                        value={data.intro}
                        onChange={(value) => setData('intro', value)}
                    />

                    <div>
                        <Label htmlFor="career-role-summary">
                            About the Role
                        </Label>
                        <Textarea
                            id="career-role-summary"
                            value={data.role_summary}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('role_summary', event.target.value)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-6 p-5">
                    <p className="font-bold text-primary">
                        Responsibilities, Qualifications &amp; Skills
                    </p>

                    <div>
                        <Label htmlFor="career-responsibilities-lead-in">
                            Responsibilities lead-in
                        </Label>
                        <Input
                            id="career-responsibilities-lead-in"
                            placeholder="e.g. As a Psychologist with CATS, responsibilities may include:"
                            value={data.responsibilities_lead_in}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'responsibilities_lead_in',
                                    event.target.value,
                                )
                            }
                        />
                    </div>

                    <TagListField
                        label="Responsibilities"
                        placeholder="Add a responsibility and press Enter"
                        values={data.responsibilities}
                        onAdd={(value) => addToList('responsibilities', value)}
                        onRemove={(value) =>
                            removeFromList('responsibilities', value)
                        }
                    />

                    <div>
                        <Label htmlFor="career-qualifications-lead-in">
                            Qualifications lead-in
                        </Label>
                        <Input
                            id="career-qualifications-lead-in"
                            placeholder="e.g. Applicants should have:"
                            value={data.qualifications_lead_in}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'qualifications_lead_in',
                                    event.target.value,
                                )
                            }
                        />
                    </div>

                    <TagListField
                        label="Qualifications"
                        placeholder="Add a qualification and press Enter"
                        values={data.qualifications}
                        onAdd={(value) => addToList('qualifications', value)}
                        onRemove={(value) =>
                            removeFromList('qualifications', value)
                        }
                    />

                    <div>
                        <Label htmlFor="career-qualifications-note">
                            Qualifications closing note
                        </Label>
                        <Textarea
                            id="career-qualifications-note"
                            placeholder="e.g. Experience supporting children with … is considered an asset."
                            value={data.qualifications_note}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'qualifications_note',
                                    event.target.value,
                                )
                            }
                        />
                    </div>

                    <TagListField
                        label="Skills (areas of support)"
                        placeholder="Add an area of support and press Enter"
                        values={data.skills}
                        onAdd={(value) => addToList('skills', value)}
                        onRemove={(value) => removeFromList('skills', value)}
                    />

                    <TagListField
                        label="Benefits"
                        placeholder="Add a benefit and press Enter"
                        values={data.benefits}
                        onAdd={(value) => addToList('benefits', value)}
                        onRemove={(value) => removeFromList('benefits', value)}
                    />

                    <TagListField
                        label="Highlights"
                        placeholder="Add a highlight and press Enter"
                        values={data.highlights}
                        onAdd={(value) => addToList('highlights', value)}
                        onRemove={(value) =>
                            removeFromList('highlights', value)
                        }
                    />

                    <TagListField
                        label="Required Documents"
                        placeholder="Add a required document and press Enter"
                        values={data.required_documents}
                        onAdd={(value) =>
                            addToList('required_documents', value)
                        }
                        onRemove={(value) =>
                            removeFromList('required_documents', value)
                        }
                    />
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <p className="font-bold text-primary">
                        Multidisciplinary Collaboration
                    </p>

                    <SectionFields
                        idPrefix="career-collaboration"
                        section={data.collaboration}
                        onChange={(section) =>
                            setData('collaboration', section)
                        }
                    />
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div className="flex items-center justify-between">
                        <p className="font-bold text-primary">What We Offer</p>
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() =>
                                setData('offers', [
                                    ...data.offers,
                                    { title: '', description: '' },
                                ])
                            }
                        >
                            <Plus /> Add
                        </Button>
                    </div>

                    {data.offers.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No offer points yet.
                        </p>
                    )}

                    {data.offers.map((offer, index) => (
                        <div
                            key={index}
                            className="grid grid-cols-1 gap-3 rounded-[10px] border p-4"
                        >
                            <div className="flex items-center gap-2">
                                <Input
                                    aria-label={`Offer ${index + 1} title`}
                                    placeholder="Title"
                                    value={offer.title}
                                    className="rounded-[10px]"
                                    onChange={(event) =>
                                        updateOffer(index, {
                                            ...offer,
                                            title: event.target.value,
                                        })
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-[10px]"
                                    onClick={() =>
                                        setData(
                                            'offers',
                                            data.offers.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                            <Textarea
                                aria-label={`Offer ${index + 1} description`}
                                placeholder="Description"
                                value={offer.description}
                                className="rounded-[10px]"
                                onChange={(event) =>
                                    updateOffer(index, {
                                        ...offer,
                                        description: event.target.value,
                                    })
                                }
                            />
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div className="flex items-center justify-between">
                        <p className="font-bold text-primary">FSCD Services</p>
                        <div className="flex items-center gap-3">
                            <Label htmlFor="career-fscd-enabled">
                                Include this section
                            </Label>
                            <Switch
                                id="career-fscd-enabled"
                                checked={data.fscd_enabled}
                                onCheckedChange={(checked) =>
                                    setData('fscd_enabled', checked)
                                }
                            />
                        </div>
                    </div>

                    {data.fscd_enabled && (
                        <SectionFields
                            idPrefix="career-fscd"
                            section={data.fscd}
                            onChange={(section) => setData('fscd', section)}
                        />
                    )}
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="font-bold text-primary">
                                Additional Sections
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Anything specific to this posting, e.g.
                                documentation expectations.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={() =>
                                setData('extras', [
                                    ...data.extras,
                                    { ...EMPTY_SECTION, paragraphs: '' },
                                ])
                            }
                        >
                            <Plus /> Add
                        </Button>
                    </div>

                    {data.extras.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No additional sections.
                        </p>
                    )}

                    {data.extras.map((extra, index) => (
                        <div
                            key={index}
                            className="grid grid-cols-1 gap-4 rounded-[10px] border p-4"
                        >
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium">
                                    Section {index + 1}
                                </p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-[10px]"
                                    onClick={() =>
                                        setData(
                                            'extras',
                                            data.extras.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            </div>

                            <SectionFields
                                idPrefix={`career-extra-${index}`}
                                section={extra}
                                onChange={(section) =>
                                    updateExtra(index, {
                                        ...section,
                                        paragraphs: extra.paragraphs,
                                    })
                                }
                            />

                            <LinesField
                                id={`career-extra-${index}-paragraphs`}
                                label="Paragraphs"
                                hint="One paragraph per line, shown above the list."
                                value={extra.paragraphs}
                                onChange={(paragraphs) =>
                                    updateExtra(index, { ...extra, paragraphs })
                                }
                            />
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <p className="font-bold text-primary">
                        Contractor Terms &amp; Closing
                    </p>

                    <div>
                        <Label htmlFor="career-contractor-title">
                            Contractor section heading
                        </Label>
                        <Input
                            id="career-contractor-title"
                            placeholder="e.g. Independent Contractor Opportunity"
                            value={data.contractor_title}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('contractor_title', event.target.value)
                            }
                        />
                    </div>

                    <LinesField
                        id="career-contractor-paragraphs"
                        label="Contractor paragraphs"
                        hint="One paragraph per line."
                        value={data.contractor_paragraphs}
                        onChange={(value) =>
                            setData('contractor_paragraphs', value)
                        }
                    />

                    <div>
                        <Label htmlFor="career-closing-title">
                            Closing heading
                        </Label>
                        <Input
                            id="career-closing-title"
                            placeholder="e.g. Ready to Join Our Team?"
                            value={data.closing_title}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('closing_title', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="career-closing">
                            Closing paragraph
                        </Label>
                        <Textarea
                            id="career-closing"
                            value={data.closing}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('closing', event.target.value)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button
                    id="career-submit"
                    className="rounded-[10px]"
                    onClick={submit}
                    disabled={processing}
                >
                    <Save /> {isEdit ? 'Save Changes' : 'Add Position'}
                </Button>
            </div>
        </div>
    );
}
