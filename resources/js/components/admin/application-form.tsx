import { Link, useForm } from '@inertiajs/react';
import { Plus, Save, Trash } from 'lucide-react';
import { useState } from 'react';

import { EnterInput } from '@/components/enter-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    EDUCATION_OPTIONS,
    LEAD_SOURCE_OPTIONS,
    PROFESSION_STATUS_OPTIONS,
    RESIDENT_STATUS,
    WEEK_DAYS,
} from '@/lib/content/careers-config';
import { ProvinceCities, Provinces } from '@/lib/content/intake-taxonomy';
import type { Application } from '@/types/application';

export interface CareerOption {
    id: number;
    position: string;
}

interface AvailabilityFormEntry {
    week_day: string;
    time_from: string;
    time_to: string;
}

interface ReferenceFormEntry {
    full_name: string;
    position: string;
    work: string;
    email: string;
    phone: string;
}

interface ApplicationFormData {
    first_name: string;
    middle_name: string;
    last_name: string;
    phone: string;
    email: string;
    resident_status: string;
    street_address: string;
    address_line_2: string;
    city: string;
    province: string;
    zip_code: string;
    position_applied: string;
    position_id: number | null;
    profession_status: string;
    preferred_start_date: string;
    is_working_with_other: boolean;
    availability: AvailabilityFormEntry[];
    availability_notes: string;
    drivers_license: boolean;
    has_vehicle: boolean;
    lead_source: string;
    reason_for_applying: string;
    other_notes: string;
    expected_salary: string;
    experience: string;
    education: string;
    skills: string[];
    references: ReferenceFormEntry[];
}

const EMPTY_REFERENCE: ReferenceFormEntry = {
    full_name: '',
    position: '',
    work: '',
    email: '',
    phone: '',
};

const REFERENCE_FIELDS: { key: keyof ReferenceFormEntry; label: string }[] = [
    { key: 'full_name', label: 'Full Name' },
    { key: 'position', label: 'Position' },
    { key: 'work', label: 'Workplace' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Phone' },
];

const OTHER_LEAD_SOURCE = 'other';

function initialValues(application: Application): ApplicationFormData {
    const availability = WEEK_DAYS.map((day) => {
        const saved = application.availability?.find(
            (entry) => entry.week_day === day,
        );

        return {
            week_day: day,
            time_from: saved?.time_from ?? '',
            time_to: saved?.time_to ?? '',
        };
    });

    const references = (application.references ?? []).map((reference) => ({
        full_name: String(reference.full_name ?? ''),
        position: String(reference.position ?? ''),
        work: String(reference.work ?? ''),
        email: String(reference.email ?? ''),
        phone: String(reference.phone ?? ''),
    }));

    return {
        first_name: application.first_name ?? '',
        middle_name: application.middle_name ?? '',
        last_name: application.last_name ?? '',
        phone: application.phone ?? '',
        email: application.email ?? '',
        resident_status: application.resident_status ?? '',
        street_address: application.street_address ?? '',
        address_line_2: application.address_line_2 ?? '',
        city: application.city ?? '',
        province: application.province ?? '',
        zip_code: application.zip_code ?? '',
        position_applied: application.position_applied ?? '',
        position_id: application.position_id,
        profession_status: application.profession_status ?? '',
        preferred_start_date:
            application.preferred_start_date?.slice(0, 10) ?? '',
        is_working_with_other: Boolean(application.is_working_with_other),
        availability,
        availability_notes: application.availability_notes ?? '',
        drivers_license: Boolean(application.drivers_license),
        has_vehicle: Boolean(application.has_vehicle),
        lead_source: application.lead_source ?? '',
        reason_for_applying: application.reason_for_applying ?? '',
        other_notes: application.other_notes ?? '',
        expected_salary: application.expected_salary ?? '',
        experience: application.experience ?? '',
        education: application.education ?? '',
        skills: application.skills ?? [],
        references:
            references.length > 0 ? references : [{ ...EMPTY_REFERENCE }],
    };
}

/**
 * Keeps a stored value that is not in a fixed option list selectable, so
 * editing an older application never silently blanks a field.
 */
function withCurrentValue(
    options: { value: string; label: string }[],
    current: string,
): { value: string; label: string }[] {
    if (current === '' || options.some((option) => option.value === current)) {
        return options;
    }

    return [...options, { value: current, label: current }];
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-destructive">{message}</p>;
}

interface SelectFieldProps {
    id: string;
    label: string;
    value: string;
    options: { value: string; label: string }[];
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
}

function SelectField({
    id,
    label,
    value,
    options,
    onChange,
    placeholder = 'Select an option',
    disabled = false,
    error,
}: SelectFieldProps) {
    return (
        <div>
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={onChange} disabled={disabled}>
                <SelectTrigger id={id} className="mt-2 rounded-[10px]">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <FieldError message={error} />
        </div>
    );
}

/**
 * Admin "Edit Application" form. Covers every detail the candidate entered
 * on the public career form except the resume and cover letter uploads;
 * status, interview and offer changes stay on the application page's actions.
 */
export default function ApplicationForm({
    application,
    careers,
}: {
    application: Application;
    careers: CareerOption[];
}) {
    const { data, setData, put, processing, errors } =
        useForm<ApplicationFormData>(initialValues(application));

    const errorFor = (field: string): string | undefined =>
        (errors as Record<string, string | undefined>)[field];

    const [leadSourceIsOther, setLeadSourceIsOther] = useState(
        data.lead_source !== '' &&
            !LEAD_SOURCE_OPTIONS.some(
                (option) => option.key === data.lead_source,
            ),
    );
    const leadSourceSelect = leadSourceIsOther
        ? OTHER_LEAD_SOURCE
        : data.lead_source;

    const positionOptions = withCurrentValue(
        careers.map((career) => ({
            value: career.position,
            label: career.position,
        })),
        data.position_applied,
    );

    const cityOptions = withCurrentValue(
        (ProvinceCities[data.province] ?? []).map((city) => ({
            value: city,
            label: city,
        })),
        data.city,
    );

    const updateAvailability = (
        index: number,
        field: 'time_from' | 'time_to',
        value: string,
    ) => {
        setData(
            'availability',
            data.availability.map((entry, position) =>
                position === index ? { ...entry, [field]: value } : entry,
            ),
        );
    };

    const updateReference = (
        index: number,
        field: keyof ReferenceFormEntry,
        value: string,
    ) => {
        setData(
            'references',
            data.references.map((reference, position) =>
                position === index
                    ? { ...reference, [field]: value }
                    : reference,
            ),
        );
    };

    const textField = (
        field: keyof ApplicationFormData,
        label: string,
        type: string = 'text',
    ) => (
        <div>
            <Label htmlFor={`application-${field}`}>{label}</Label>
            <Input
                id={`application-${field}`}
                type={type}
                value={data[field] as string}
                className="mt-2 rounded-[10px]"
                onChange={(event) => setData(field, event.target.value)}
            />
            <FieldError message={errorFor(field)} />
        </div>
    );

    const booleanField = (
        field: 'is_working_with_other' | 'drivers_license' | 'has_vehicle',
        label: string,
    ) => (
        <div className="flex items-center gap-2">
            <Checkbox
                id={`application-${field}`}
                checked={data[field]}
                onCheckedChange={(checked) => setData(field, checked === true)}
            />
            <Label htmlFor={`application-${field}`}>{label}</Label>
        </div>
    );

    const submit = () => {
        put(`/admin/applications/${application.id}`);
    };

    return (
        <div className="grid grid-cols-1 gap-5">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">
                        Personal Information
                    </p>
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-3">
                        {textField('first_name', 'First Name')}
                        {textField('middle_name', 'Middle Name')}
                        {textField('last_name', 'Last Name')}
                        {textField('email', 'Email', 'email')}
                        {textField('phone', 'Phone', 'tel')}
                        <SelectField
                            id="application-resident_status"
                            label="Resident Status"
                            value={data.resident_status}
                            options={withCurrentValue(
                                RESIDENT_STATUS.map((status) => ({
                                    value: status,
                                    label: status,
                                })),
                                data.resident_status,
                            )}
                            onChange={(value) =>
                                setData('resident_status', value)
                            }
                            error={errors.resident_status}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">Address</p>
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {textField('street_address', 'Street Address')}
                        {textField('address_line_2', 'Address Line 2')}
                    </div>
                    <div className="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <SelectField
                            id="application-province"
                            label="Province"
                            value={data.province}
                            options={withCurrentValue(
                                Provinces.map((province) => ({
                                    value: province,
                                    label: province,
                                })),
                                data.province,
                            )}
                            onChange={(value) => {
                                setData((previous) => ({
                                    ...previous,
                                    province: value,
                                    city: '',
                                }));
                            }}
                            error={errors.province}
                        />
                        <SelectField
                            id="application-city"
                            label="City"
                            value={data.city}
                            options={cityOptions}
                            onChange={(value) => setData('city', value)}
                            disabled={!data.province}
                            error={errors.city}
                        />
                        {textField('zip_code', 'Postal Code')}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">
                        Position & Qualifications
                    </p>
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <SelectField
                            id="application-position_applied"
                            label="Position Applied"
                            value={data.position_applied}
                            options={positionOptions}
                            onChange={(value) => {
                                const career = careers.find(
                                    (option) => option.position === value,
                                );

                                setData((previous) => ({
                                    ...previous,
                                    position_applied: value,
                                    position_id:
                                        career?.id ?? previous.position_id,
                                }));
                            }}
                            error={errors.position_applied}
                        />
                        <SelectField
                            id="application-profession_status"
                            label="Profession Status"
                            value={data.profession_status}
                            options={withCurrentValue(
                                PROFESSION_STATUS_OPTIONS,
                                data.profession_status,
                            )}
                            onChange={(value) =>
                                setData('profession_status', value)
                            }
                            error={errors.profession_status}
                        />
                        <SelectField
                            id="application-education"
                            label="Education"
                            value={data.education}
                            options={withCurrentValue(
                                EDUCATION_OPTIONS.map((option) => ({
                                    value: option.key,
                                    label: option.label,
                                })),
                                data.education,
                            )}
                            onChange={(value) => setData('education', value)}
                            error={errors.education}
                        />
                        {textField(
                            'preferred_start_date',
                            'Preferred Start Date',
                            'date',
                        )}
                        {textField('expected_salary', 'Expected Salary')}
                    </div>

                    <div className="mt-5">
                        <Label htmlFor="application-experience">
                            Experience
                        </Label>
                        <Textarea
                            id="application-experience"
                            value={data.experience}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('experience', event.target.value)
                            }
                        />
                        <FieldError message={errors.experience} />
                    </div>

                    <div className="mt-5">
                        <EnterInput
                            title="Skills"
                            inputs={data.skills}
                            setInputs={(skills) => setData('skills', skills)}
                            error={errorFor('skills')}
                        />
                    </div>

                    <div className="mt-2 flex flex-col gap-3 md:flex-row md:gap-8">
                        {booleanField(
                            'is_working_with_other',
                            'Currently working with another agency',
                        )}
                        {booleanField(
                            'drivers_license',
                            "Has a driver's licence",
                        )}
                        {booleanField('has_vehicle', 'Has a vehicle')}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">Availability</p>
                    <div className="grid grid-cols-1 gap-3">
                        {data.availability.map((entry, index) => (
                            <div
                                key={entry.week_day}
                                className="grid grid-cols-1 items-center gap-2 md:grid-cols-3"
                            >
                                <p className="text-sm font-medium">
                                    {entry.week_day}
                                </p>
                                <Input
                                    type="time"
                                    aria-label={`${entry.week_day} from`}
                                    value={entry.time_from}
                                    className="rounded-[10px]"
                                    onChange={(event) =>
                                        updateAvailability(
                                            index,
                                            'time_from',
                                            event.target.value,
                                        )
                                    }
                                />
                                <Input
                                    type="time"
                                    aria-label={`${entry.week_day} to`}
                                    value={entry.time_to}
                                    className="rounded-[10px]"
                                    onChange={(event) =>
                                        updateAvailability(
                                            index,
                                            'time_to',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        ))}
                    </div>

                    <div className="mt-5">
                        <Label htmlFor="application-availability_notes">
                            Additional scheduling details or restrictions
                        </Label>
                        <Textarea
                            id="application-availability_notes"
                            value={data.availability_notes}
                            className="mt-2 rounded-[10px]"
                            rows={3}
                            onChange={(event) =>
                                setData(
                                    'availability_notes',
                                    event.target.value,
                                )
                            }
                        />
                        <FieldError message={errors.availability_notes} />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <div className="mb-5 flex items-center justify-between">
                        <p className="font-bold text-primary">References</p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="rounded-[10px]"
                            onClick={() =>
                                setData('references', [
                                    ...data.references,
                                    { ...EMPTY_REFERENCE },
                                ])
                            }
                        >
                            <Plus /> Add Reference
                        </Button>
                    </div>

                    <div className="grid grid-cols-1 gap-5">
                        {data.references.map((reference, index) => (
                            <div
                                key={index}
                                className="rounded-[10px] border p-4"
                            >
                                <div className="mb-3 flex items-center justify-between">
                                    <p className="text-sm font-semibold">
                                        Reference {index + 1}
                                    </p>
                                    {data.references.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                setData(
                                                    'references',
                                                    data.references.filter(
                                                        (_, position) =>
                                                            position !== index,
                                                    ),
                                                )
                                            }
                                        >
                                            <Trash /> Remove
                                        </Button>
                                    )}
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    {REFERENCE_FIELDS.map(({ key, label }) => (
                                        <div key={key}>
                                            <Label
                                                htmlFor={`reference-${index}-${key}`}
                                            >
                                                {label}
                                            </Label>
                                            <Input
                                                id={`reference-${index}-${key}`}
                                                type={
                                                    key === 'email'
                                                        ? 'email'
                                                        : 'text'
                                                }
                                                value={reference[key]}
                                                className="mt-2 rounded-[10px]"
                                                onChange={(event) =>
                                                    updateReference(
                                                        index,
                                                        key,
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <FieldError
                                                message={errorFor(
                                                    `references.${index}.${key}`,
                                                )}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">
                        Additional Information
                    </p>
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <SelectField
                            id="application-lead_source"
                            label="How did they hear about us?"
                            value={leadSourceSelect}
                            options={LEAD_SOURCE_OPTIONS.map((option) => ({
                                value: option.key,
                                label: option.label,
                            }))}
                            onChange={(value) => {
                                setLeadSourceIsOther(
                                    value === OTHER_LEAD_SOURCE,
                                );
                                setData(
                                    'lead_source',
                                    value === OTHER_LEAD_SOURCE ? '' : value,
                                );
                            }}
                            error={errors.lead_source}
                        />
                        {leadSourceSelect === OTHER_LEAD_SOURCE && (
                            <div>
                                <Label htmlFor="application-lead_source_other">
                                    Please specify
                                </Label>
                                <Input
                                    id="application-lead_source_other"
                                    value={data.lead_source}
                                    className="mt-2 rounded-[10px]"
                                    onChange={(event) =>
                                        setData(
                                            'lead_source',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}
                    </div>

                    <div className="mt-5">
                        <Label htmlFor="application-reason_for_applying">
                            Reason for Applying
                        </Label>
                        <Textarea
                            id="application-reason_for_applying"
                            value={data.reason_for_applying}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'reason_for_applying',
                                    event.target.value,
                                )
                            }
                        />
                        <FieldError message={errors.reason_for_applying} />
                    </div>

                    <div className="mt-5">
                        <Label htmlFor="application-other_notes">
                            Other Notes
                        </Label>
                        <Textarea
                            id="application-other_notes"
                            value={data.other_notes}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('other_notes', event.target.value)
                            }
                        />
                        <FieldError message={errors.other_notes} />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-3">
                <Button variant="outline" className="rounded-[10px]" asChild>
                    <Link href={`/admin/applications/${application.id}`}>
                        Cancel
                    </Link>
                </Button>
                <Button
                    className="rounded-[10px]"
                    onClick={submit}
                    disabled={processing}
                >
                    <Save /> Save Changes
                </Button>
            </div>
        </div>
    );
}
