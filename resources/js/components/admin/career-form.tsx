import { useForm } from '@inertiajs/react';
import { Plus, Save, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { Career } from '@/types/career';

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
    due_date: string;
    level: string;
    hours: string;
}

function initialValues(career?: Career | null): CareerFormData {
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
        due_date: career?.due_date ?? '',
        level: career?.level ?? '',
        hours: career?.hours ?? '',
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

/** Admin "Add/Edit Position" form, backing the `careers` table. */
export default function CareerForm({ career }: { career?: Career | null }) {
    const isEdit = career != null;

    const { data, setData, post, put, processing, errors } =
        useForm<CareerFormData>(initialValues(career));

    const submit = () => {
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
                            placeholder="e.g. Contract"
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
                            placeholder="e.g. $40-$60/hr"
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
                        <Label htmlFor="career-level">Level</Label>
                        <Input
                            id="career-level"
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
                                setData(
                                    'short_description',
                                    event.target.value,
                                )
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
                        <Textarea
                            id="career-about-desc"
                            value={data.about_description}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'about_description',
                                    event.target.value,
                                )
                            }
                        />
                        {errors.about_description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.about_description}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-6 p-5">
                    <p className="font-bold text-primary">
                        Responsibilities, Qualifications &amp; Skills
                    </p>

                    <TagListField
                        label="Responsibilities"
                        placeholder="Add a responsibility and press Enter"
                        values={data.responsibilities}
                        onAdd={(value) =>
                            addToList('responsibilities', value)
                        }
                        onRemove={(value) =>
                            removeFromList('responsibilities', value)
                        }
                    />

                    <TagListField
                        label="Qualifications"
                        placeholder="Add a qualification and press Enter"
                        values={data.qualifications}
                        onAdd={(value) => addToList('qualifications', value)}
                        onRemove={(value) =>
                            removeFromList('qualifications', value)
                        }
                    />

                    <TagListField
                        label="Skills"
                        placeholder="Add a skill and press Enter"
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

            <div className="flex justify-end">
                <Button
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
