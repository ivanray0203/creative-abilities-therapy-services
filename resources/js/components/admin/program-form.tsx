import { useForm, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

export interface ProgramFormValues {
    id?: number;
    name: string;
    slug?: string;
    category: string | null;
    summary: string;
    description: string;
    age_range: string | null;
    schedule: string | null;
    location: string | null;
    highlights: string[];
    capacity: number | null;
    price: string | null;
    starts_on: string | null;
    ends_on: string | null;
    registration_closes_on: string | null;
    is_active: boolean;
}

interface ProgramFormData {
    name: string;
    category: string;
    summary: string;
    description: string;
    age_range: string;
    schedule: string;
    location: string;
    highlights: string[];
    capacity: string;
    price: string;
    starts_on: string;
    ends_on: string;
    registration_closes_on: string;
    is_active: boolean;
}

function initialValues(program?: ProgramFormValues | null): ProgramFormData {
    return {
        name: program?.name ?? '',
        category: program?.category ?? '',
        summary: program?.summary ?? '',
        description: program?.description ?? '',
        age_range: program?.age_range ?? '',
        schedule: program?.schedule ?? '',
        location: program?.location ?? '',
        highlights: program?.highlights?.length ? program.highlights : [''],
        capacity: program?.capacity != null ? String(program.capacity) : '',
        price: program?.price != null ? String(program.price) : '',
        starts_on: program?.starts_on ?? '',
        ends_on: program?.ends_on ?? '',
        registration_closes_on: program?.registration_closes_on ?? '',
        is_active: program?.is_active ?? true,
    };
}

/**
 * Shared admin "Add/Edit Program" form. The slug is not editable here — the
 * server derives it from the name, so the public URL can't drift from the
 * title or collide with another program.
 */
export default function ProgramForm({
    program,
}: {
    program?: ProgramFormValues | null;
}) {
    const isEdit = program != null;
    const { data, setData, post, put, processing, errors, transform } =
        useForm<ProgramFormData>(initialValues(program));

    /*
     * The slug is derived server-side and is not a field here, so useForm's
     * typed errors never carry it. A collision still has to surface — under
     * the name, which is what produced it.
     */
    const { props } = usePage<{ errors: Record<string, string> }>();
    const slugError = props.errors?.slug;

    // Blank rows are an artefact of the repeater, not something to store; the
    // empty strings would otherwise render as empty bullets on the public page.
    transform((values) => ({
        ...values,
        highlights: values.highlights.filter(
            (highlight) => highlight.trim() !== '',
        ),
    }));

    const updateHighlight = (index: number, value: string) => {
        setData(
            'highlights',
            data.highlights.map((highlight, position) =>
                position === index ? value : highlight,
            ),
        );
    };

    const submit = () => {
        if (isEdit && program?.id) {
            put(`/admin/programs/${program.id}`);

            return;
        }

        post('/admin/programs');
    };

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                    {isEdit ? 'Edit Program' : 'Add Program'}
                </h1>
                <p className="text-sm text-muted-foreground">
                    Shown on the public Programs page, where families register
                    directly
                </p>
            </div>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <div>
                        <Label htmlFor="program-name">Name *</Label>
                        <Input
                            id="program-name"
                            value={data.name}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                        {slugError && (
                            <p className="mt-1 text-sm text-destructive">
                                Another program already uses this name.
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="program-category">Category</Label>
                        <Input
                            id="program-category"
                            value={data.category}
                            placeholder="Camp, Social Skills, Parent Workshop..."
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('category', event.target.value)
                            }
                        />
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="program-summary">Summary *</Label>
                        <Textarea
                            id="program-summary"
                            value={data.summary}
                            placeholder="One or two sentences shown on the program card."
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('summary', event.target.value)
                            }
                        />
                        {errors.summary && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.summary}
                            </p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="program-description">
                            Description *
                        </Label>
                        <Textarea
                            id="program-description"
                            value={data.description}
                            rows={8}
                            placeholder="The full description. Leave a blank line between paragraphs."
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="program-age-range">Age Range</Label>
                        <Input
                            id="program-age-range"
                            value={data.age_range}
                            placeholder="6-9 years"
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('age_range', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="program-schedule">Schedule</Label>
                        <Input
                            id="program-schedule"
                            value={data.schedule}
                            placeholder="Saturdays, 10:00 AM - 11:30 AM"
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('schedule', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="program-location">Location</Label>
                        <Input
                            id="program-location"
                            value={data.location}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('location', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="program-capacity">Capacity</Label>
                        <Input
                            id="program-capacity"
                            type="number"
                            min={1}
                            value={data.capacity}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('capacity', event.target.value)
                            }
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Leave blank for an unlimited program.
                        </p>
                        {errors.capacity && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.capacity}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="program-price">Price ($)</Label>
                        <Input
                            id="program-price"
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.price}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('price', event.target.value)
                            }
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Leave blank or 0 to show as free.
                        </p>
                    </div>

                    <div>
                        <Label htmlFor="program-starts-on">Starts On</Label>
                        <Input
                            id="program-starts-on"
                            type="date"
                            value={data.starts_on}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('starts_on', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="program-ends-on">Ends On</Label>
                        <Input
                            id="program-ends-on"
                            type="date"
                            value={data.ends_on}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('ends_on', event.target.value)
                            }
                        />
                        {errors.ends_on && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.ends_on}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="program-closes-on">
                            Registration Closes On
                        </Label>
                        <Input
                            id="program-closes-on"
                            type="date"
                            value={data.registration_closes_on}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'registration_closes_on',
                                    event.target.value,
                                )
                            }
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Defaults to the start date when left blank.
                        </p>
                        {errors.registration_closes_on && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.registration_closes_on}
                            </p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <Label>What&apos;s Included</Label>
                        <div className="mt-2 space-y-2">
                            {data.highlights.map((highlight, index) => (
                                <div
                                    // The list is reorderable only by editing,
                                    // so the index is a stable enough key.
                                    key={index}
                                    className="flex items-center gap-2"
                                >
                                    <Input
                                        id={`program-highlight-${index}`}
                                        value={highlight}
                                        placeholder="Maximum of 8 children per group"
                                        className="rounded-[10px]"
                                        onChange={(event) =>
                                            updateHighlight(
                                                index,
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="shrink-0 rounded-[10px] text-destructive"
                                        onClick={() =>
                                            setData(
                                                'highlights',
                                                data.highlights.filter(
                                                    (_, position) =>
                                                        position !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="mt-2 rounded-[10px]"
                            onClick={() =>
                                setData('highlights', [...data.highlights, ''])
                            }
                        >
                            <Plus className="h-4 w-4" /> Add Item
                        </Button>
                    </div>

                    <div className="flex items-center gap-3 md:col-span-2">
                        <Switch
                            id="program-active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked)
                            }
                        />
                        <Label htmlFor="program-active">
                            Published on the public Programs page
                        </Label>
                    </div>

                    <div className="md:col-span-2">
                        <Button
                            id="program-submit"
                            type="button"
                            className="rounded-[10px]"
                            onClick={submit}
                            disabled={processing}
                        >
                            {isEdit ? 'Save Changes' : 'Add Program'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
