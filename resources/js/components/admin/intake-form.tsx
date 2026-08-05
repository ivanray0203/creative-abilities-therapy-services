import { useForm } from '@inertiajs/react';
import { Save, Trash } from 'lucide-react';
import { useState } from 'react';

import DeleteIntakeModal from '@/components/admin/delete-intake-modal';
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
import {
    days,
    leadSource,
    medicalConditionsOptions,
    ProvinceCities,
    Provinces,
    servicesMap,
    times,
} from '@/lib/content/intake-taxonomy';
import type { Intake } from '@/types/intake';

const FSCD_FUNDING_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

const FUNDING_SOURCES = [...FSCD_FUNDING_SOURCES, 'Insurance', 'private'];

const STATUSES = ['pending', 'under_review', 'waitlist', 'approved', 'denied'];

interface FscdInfo {
    FSCD_case_worker_name: string;
    FSCD_case_worker_email: string;
    FSCD_approval_start_date: string;
    FSCD_approval_end_date: string;
}

interface InsuranceInfo {
    insurance_provider: string;
    policy_holder_name: string;
    policy_number: string;
    certificate_number: string;
    policy_holder_date_of_birth: string;
    pre_authorization_obtained: string;
    used_annual_maximum: string;
    authorization_start_date: string;
    authorization_end_date: string;
}

interface AdminIntakeFormData {
    child_first_name: string;
    child_middle_name: string;
    child_last_name: string;
    date_of_birth: string;
    gender: string;
    street_address: string;
    address_line_2: string;
    city: string;
    state_province: string;
    postal_code: string;
    grade_level: string;
    school_name: string;
    services_needed: string[];
    currently_receiving_services: boolean;
    receiving_services_desc: string;
    diagnosis: string[];
    has_medical_conditions: boolean;
    languages_spoken_at_home: string;
    require_interpreter: boolean;
    interpreter_needed: string;
    medical_conditions: string;
    theraphy_goals: string;
    funding_source: string;
    available_days: string[];
    preferred_times: string[];
    primary_parent_name: string;
    primary_parent_phone: string;
    primary_parent_email: string;
    primary_relationship_to_child: string;
    primary_contact_method: string;
    secondary_parent_name: string;
    secondary_parent_phone: string;
    secondary_parent_email: string;
    secondary_relationship_to_child: string;
    secondary_contact_method: string;
    additional_information: string;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    emergency_contact_relationship: string;
    referral_source: string;
    referral_source_other: string;
    status: string;
    completed: boolean;
    fscd_info: FscdInfo;
    insurance_info: InsuranceInfo;
}

function toggleArrayValue(list: string[], value: string): string[] {
    return list.includes(value)
        ? list.filter((entry) => entry !== value)
        : [...list, value];
}

function initialValues(intake?: Intake): AdminIntakeFormData {
    const funding = intake?.funding_source_info ?? {};

    return {
        child_first_name: intake?.child_first_name ?? '',
        child_middle_name: intake?.child_middle_name ?? '',
        child_last_name: intake?.child_last_name ?? '',
        date_of_birth: intake?.date_of_birth?.split('T')[0] ?? '',
        gender: intake?.gender ?? '',
        street_address: intake?.street_address ?? '',
        address_line_2: intake?.address_line_2 ?? '',
        city: intake?.city ?? '',
        state_province: intake?.state_province ?? '',
        postal_code: intake?.postal_code ?? '',
        grade_level: intake?.grade_level ?? '',
        school_name: intake?.school_name ?? '',
        services_needed: intake?.services_needed ?? [],
        currently_receiving_services:
            intake?.currently_receiving_services ?? false,
        receiving_services_desc: intake?.receiving_services_desc ?? '',
        diagnosis: intake?.diagnosis ?? [],
        has_medical_conditions: intake?.has_medical_conditions ?? false,
        languages_spoken_at_home: intake?.languages_spoken_at_home ?? '',
        require_interpreter: intake?.require_interpreter ?? false,
        interpreter_needed: intake?.interpreter_needed ?? '',
        medical_conditions: intake?.medical_conditions ?? '',
        theraphy_goals: intake?.theraphy_goals ?? '',
        funding_source: intake?.funding_source ?? '',
        available_days: intake?.available_days ?? [],
        preferred_times: intake?.preferred_times ?? [],
        primary_parent_name: intake?.primary_parent_name ?? '',
        primary_parent_phone: intake?.primary_parent_phone ?? '',
        primary_parent_email: intake?.primary_parent_email ?? '',
        primary_relationship_to_child:
            intake?.primary_relationship_to_child ?? '',
        primary_contact_method: intake?.primary_contact_method ?? '',
        secondary_parent_name: intake?.secondary_parent_name ?? '',
        secondary_parent_phone: intake?.secondary_parent_phone ?? '',
        secondary_parent_email: intake?.secondary_parent_email ?? '',
        secondary_relationship_to_child:
            intake?.secondary_relationship_to_child ?? '',
        secondary_contact_method: intake?.secondary_contact_method ?? '',
        additional_information: intake?.additional_information ?? '',
        emergency_contact_name: intake?.emergency_contact_name ?? '',
        emergency_contact_phone: intake?.emergency_contact_phone ?? '',
        emergency_contact_relationship:
            intake?.emergency_contact_relationship ?? '',
        referral_source: intake?.referral_source ?? '',
        referral_source_other: '',
        status: intake?.status ?? 'pending',
        completed: intake?.completed ?? false,
        fscd_info: {
            FSCD_case_worker_name: funding.FSCD_case_worker_name ?? '',
            FSCD_case_worker_email: funding.FSCD_case_worker_email ?? '',
            FSCD_approval_start_date: funding.FSCD_approval_start_date ?? '',
            FSCD_approval_end_date: funding.FSCD_approval_end_date ?? '',
        },
        insurance_info: {
            insurance_provider: funding.insurance_provider ?? '',
            policy_holder_name: funding.policy_holder_name ?? '',
            policy_number: funding.policy_number ?? '',
            certificate_number: funding.certificate_number ?? '',
            policy_holder_date_of_birth:
                funding.policy_holder_date_of_birth ?? '',
            pre_authorization_obtained:
                funding.pre_authorization_obtained ?? '',
            used_annual_maximum: funding.used_annual_maximum ?? '',
            authorization_start_date: funding.authorization_start_date ?? '',
            authorization_end_date: funding.authorization_end_date ?? '',
        },
    };
}

/**
 * Shared admin create/edit intake form, ported from
 * cats-frontend/src/components/IntakeForm.tsx. Uses Inertia's `useForm` +
 * Laravel validation (this project's convention) instead of react-hook-form
 * + zod, and reuses the Phase 5 intake taxonomy for the cascading
 * province/city selects and funding-source service lists.
 */
export default function AdminIntakeForm({ intake }: { intake?: Intake }) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const isEdit = intake !== undefined;

    const { data, setData, post, put, processing, errors } =
        useForm<AdminIntakeFormData>(initialValues(intake));

    const currentServices = servicesMap[data.funding_source] ?? [];
    const isFscd = FSCD_FUNDING_SOURCES.includes(data.funding_source);
    const isInsurance = data.funding_source === 'Insurance';
    const emailLocked = isEdit && Boolean(intake?.primary_parent_email);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(`/admin/intake/${intake.id}`);
        } else {
            post('/admin/intake');
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Section title="Child Information">
                <Field label="First Name *" error={errors.child_first_name}>
                    <Input
                        value={data.child_first_name}
                        onChange={(event) =>
                            setData('child_first_name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Middle Name" error={errors.child_middle_name}>
                    <Input
                        value={data.child_middle_name}
                        onChange={(event) =>
                            setData('child_middle_name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Last Name *" error={errors.child_last_name}>
                    <Input
                        value={data.child_last_name}
                        onChange={(event) =>
                            setData('child_last_name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Date of Birth *" error={errors.date_of_birth}>
                    <Input
                        type="date"
                        value={data.date_of_birth}
                        onChange={(event) =>
                            setData('date_of_birth', event.target.value)
                        }
                    />
                </Field>
                <Field label="Gender" error={errors.gender}>
                    <Select
                        value={data.gender}
                        onValueChange={(value) => setData('gender', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select gender" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="male">Male</SelectItem>
                            <SelectItem value="female">Female</SelectItem>
                            <SelectItem value="other">Other</SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
                <Field label="Status" error={errors.status}>
                    <Select
                        value={data.status}
                        onValueChange={(value) => setData('status', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            {STATUSES.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {status}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>
            </Section>

            <Section title="Address">
                <Field label="Street Address *" error={errors.street_address}>
                    <Input
                        value={data.street_address}
                        onChange={(event) =>
                            setData('street_address', event.target.value)
                        }
                    />
                </Field>
                <Field label="Address Line 2" error={errors.address_line_2}>
                    <Input
                        value={data.address_line_2}
                        onChange={(event) =>
                            setData('address_line_2', event.target.value)
                        }
                    />
                </Field>
                <Field label="Province *" error={errors.state_province}>
                    <Select
                        value={data.state_province}
                        onValueChange={(value) => {
                            setData((current) => ({
                                ...current,
                                state_province: value,
                                city: '',
                            }));
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select province" />
                        </SelectTrigger>
                        <SelectContent>
                            {Provinces.map((province) => (
                                <SelectItem key={province} value={province}>
                                    {province}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>
                <Field label="City *" error={errors.city}>
                    <Select
                        value={data.city}
                        onValueChange={(value) => setData('city', value)}
                        disabled={!data.state_province}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select city" />
                        </SelectTrigger>
                        <SelectContent>
                            {(ProvinceCities[data.state_province] ?? []).map(
                                (city) => (
                                    <SelectItem key={city} value={city}>
                                        {city}
                                    </SelectItem>
                                ),
                            )}
                        </SelectContent>
                    </Select>
                </Field>
                <Field label="Postal Code *" error={errors.postal_code}>
                    <Input
                        value={data.postal_code}
                        onChange={(event) =>
                            setData('postal_code', event.target.value)
                        }
                    />
                </Field>
            </Section>

            <Section title="Education">
                <Field label="Grade Level" error={errors.grade_level}>
                    <Input
                        value={data.grade_level}
                        onChange={(event) =>
                            setData('grade_level', event.target.value)
                        }
                    />
                </Field>
                <Field label="School Name" error={errors.school_name}>
                    <Input
                        value={data.school_name}
                        onChange={(event) =>
                            setData('school_name', event.target.value)
                        }
                    />
                </Field>
            </Section>

            <Section title="Funding &amp; Services">
                <Field label="Funding Source *" error={errors.funding_source}>
                    <Select
                        value={data.funding_source}
                        onValueChange={(value) => {
                            setData((current) => ({
                                ...current,
                                funding_source: value,
                                services_needed: [],
                            }));
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select funding source" />
                        </SelectTrigger>
                        <SelectContent>
                            {FUNDING_SOURCES.map((source) => (
                                <SelectItem key={source} value={source}>
                                    {source}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <CheckboxGroup
                    label="Services Needed"
                    options={currentServices}
                    selected={data.services_needed}
                    onToggle={(value) =>
                        setData(
                            'services_needed',
                            toggleArrayValue(data.services_needed, value),
                        )
                    }
                />
            </Section>

            {isFscd && (
                <Section title="FSCD Details">
                    <Field
                        label="FSCD Case Worker Name *"
                        error={errors['fscd_info.FSCD_case_worker_name']}
                    >
                        <Input
                            value={data.fscd_info.FSCD_case_worker_name}
                            onChange={(event) =>
                                setData('fscd_info', {
                                    ...data.fscd_info,
                                    FSCD_case_worker_name: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="FSCD Case Worker Email *"
                        error={errors['fscd_info.FSCD_case_worker_email']}
                    >
                        <Input
                            type="email"
                            value={data.fscd_info.FSCD_case_worker_email}
                            onChange={(event) =>
                                setData('fscd_info', {
                                    ...data.fscd_info,
                                    FSCD_case_worker_email: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Approval Start Date *"
                        error={errors['fscd_info.FSCD_approval_start_date']}
                    >
                        <Input
                            type="date"
                            value={data.fscd_info.FSCD_approval_start_date}
                            onChange={(event) =>
                                setData('fscd_info', {
                                    ...data.fscd_info,
                                    FSCD_approval_start_date:
                                        event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Approval End Date"
                        error={errors['fscd_info.FSCD_approval_end_date']}
                    >
                        <Input
                            type="date"
                            value={data.fscd_info.FSCD_approval_end_date}
                            onChange={(event) =>
                                setData('fscd_info', {
                                    ...data.fscd_info,
                                    FSCD_approval_end_date: event.target.value,
                                })
                            }
                        />
                    </Field>
                </Section>
            )}

            {isInsurance && (
                <Section title="Insurance Details">
                    <Field
                        label="Insurance Provider *"
                        error={errors['insurance_info.insurance_provider']}
                    >
                        <Input
                            value={data.insurance_info.insurance_provider}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    insurance_provider: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Policy / Group Number *"
                        error={errors['insurance_info.policy_number']}
                    >
                        <Input
                            value={data.insurance_info.policy_number}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    policy_number: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Certificate / ID Number *"
                        error={errors['insurance_info.certificate_number']}
                    >
                        <Input
                            value={data.insurance_info.certificate_number}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    certificate_number: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Policy Holder Name *"
                        error={errors['insurance_info.policy_holder_name']}
                    >
                        <Input
                            value={data.insurance_info.policy_holder_name}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    policy_holder_name: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Policy Holder Date of Birth *"
                        error={
                            errors['insurance_info.policy_holder_date_of_birth']
                        }
                    >
                        <Input
                            type="date"
                            value={
                                data.insurance_info.policy_holder_date_of_birth
                            }
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    policy_holder_date_of_birth:
                                        event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Pre-Authorization Obtained *"
                        error={
                            errors['insurance_info.pre_authorization_obtained']
                        }
                    >
                        <Input
                            value={
                                data.insurance_info.pre_authorization_obtained
                            }
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    pre_authorization_obtained:
                                        event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Used Annual Maximum"
                        error={errors['insurance_info.used_annual_maximum']}
                    >
                        <Input
                            value={data.insurance_info.used_annual_maximum}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    used_annual_maximum: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Authorization Start Date"
                        error={
                            errors['insurance_info.authorization_start_date']
                        }
                    >
                        <Input
                            type="date"
                            value={data.insurance_info.authorization_start_date}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    authorization_start_date:
                                        event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field
                        label="Authorization End Date"
                        error={errors['insurance_info.authorization_end_date']}
                    >
                        <Input
                            type="date"
                            value={data.insurance_info.authorization_end_date}
                            onChange={(event) =>
                                setData('insurance_info', {
                                    ...data.insurance_info,
                                    authorization_end_date: event.target.value,
                                })
                            }
                        />
                    </Field>
                </Section>
            )}

            <Section title="Medical &amp; Developmental">
                <CheckboxGroup
                    label="Diagnosis"
                    options={medicalConditionsOptions}
                    selected={data.diagnosis}
                    onToggle={(value) =>
                        setData(
                            'diagnosis',
                            toggleArrayValue(data.diagnosis, value),
                        )
                    }
                />
                <Field
                    label="Medical Conditions"
                    error={errors.medical_conditions}
                >
                    <Textarea
                        value={data.medical_conditions}
                        onChange={(event) =>
                            setData('medical_conditions', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Languages Spoken at Home"
                    error={errors.languages_spoken_at_home}
                >
                    <Input
                        value={data.languages_spoken_at_home}
                        onChange={(event) =>
                            setData(
                                'languages_spoken_at_home',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field
                    label="Interpreter Needed"
                    error={errors.interpreter_needed}
                >
                    <Input
                        value={data.interpreter_needed}
                        onChange={(event) =>
                            setData('interpreter_needed', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Currently Receiving Services"
                    error={errors.receiving_services_desc}
                >
                    <Textarea
                        value={data.receiving_services_desc}
                        onChange={(event) =>
                            setData(
                                'receiving_services_desc',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field label="Therapy Goals" error={errors.theraphy_goals}>
                    <Textarea
                        value={data.theraphy_goals}
                        onChange={(event) =>
                            setData('theraphy_goals', event.target.value)
                        }
                    />
                </Field>
            </Section>

            <Section title="Availability">
                <CheckboxGroup
                    label="Available Days"
                    options={days}
                    selected={data.available_days}
                    onToggle={(value) =>
                        setData(
                            'available_days',
                            toggleArrayValue(data.available_days, value),
                        )
                    }
                />
                <CheckboxGroup
                    label="Preferred Times"
                    options={times}
                    selected={data.preferred_times}
                    onToggle={(value) =>
                        setData(
                            'preferred_times',
                            toggleArrayValue(data.preferred_times, value),
                        )
                    }
                />
            </Section>

            <Section title="Primary Parent/Guardian">
                <Field label="Full Name *" error={errors.primary_parent_name}>
                    <Input
                        value={data.primary_parent_name}
                        onChange={(event) =>
                            setData('primary_parent_name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Phone *" error={errors.primary_parent_phone}>
                    <Input
                        value={data.primary_parent_phone}
                        onChange={(event) =>
                            setData('primary_parent_phone', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Email *"
                    error={errors.primary_parent_email}
                    hint={
                        emailLocked
                            ? 'The primary parent email cannot be changed once set.'
                            : undefined
                    }
                >
                    <Input
                        type="email"
                        value={data.primary_parent_email}
                        disabled={emailLocked}
                        onChange={(event) =>
                            setData('primary_parent_email', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Relationship to Child *"
                    error={errors.primary_relationship_to_child}
                >
                    <Input
                        value={data.primary_relationship_to_child}
                        onChange={(event) =>
                            setData(
                                'primary_relationship_to_child',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field
                    label="Preferred Contact Method *"
                    error={errors.primary_contact_method}
                >
                    <Select
                        value={data.primary_contact_method}
                        onValueChange={(value) =>
                            setData('primary_contact_method', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select contact method" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="email">Email</SelectItem>
                            <SelectItem value="phone">Phone</SelectItem>
                            <SelectItem value="text">Text</SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
            </Section>

            <Section title="Secondary Parent/Guardian">
                <Field label="Full Name" error={errors.secondary_parent_name}>
                    <Input
                        value={data.secondary_parent_name}
                        onChange={(event) =>
                            setData('secondary_parent_name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Phone" error={errors.secondary_parent_phone}>
                    <Input
                        value={data.secondary_parent_phone}
                        onChange={(event) =>
                            setData(
                                'secondary_parent_phone',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field label="Email" error={errors.secondary_parent_email}>
                    <Input
                        type="email"
                        value={data.secondary_parent_email}
                        onChange={(event) =>
                            setData(
                                'secondary_parent_email',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field
                    label="Relationship to Child"
                    error={errors.secondary_relationship_to_child}
                >
                    <Input
                        value={data.secondary_relationship_to_child}
                        onChange={(event) =>
                            setData(
                                'secondary_relationship_to_child',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field
                    label="Preferred Contact Method"
                    error={errors.secondary_contact_method}
                >
                    <Input
                        value={data.secondary_contact_method}
                        onChange={(event) =>
                            setData(
                                'secondary_contact_method',
                                event.target.value,
                            )
                        }
                    />
                </Field>
            </Section>

            <Section title="Emergency Contact">
                <Field
                    label="Contact Name *"
                    error={errors.emergency_contact_name}
                >
                    <Input
                        value={data.emergency_contact_name}
                        onChange={(event) =>
                            setData(
                                'emergency_contact_name',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field
                    label="Contact Phone *"
                    error={errors.emergency_contact_phone}
                >
                    <Input
                        value={data.emergency_contact_phone}
                        onChange={(event) =>
                            setData(
                                'emergency_contact_phone',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field
                    label="Relationship *"
                    error={errors.emergency_contact_relationship}
                >
                    <Input
                        value={data.emergency_contact_relationship}
                        onChange={(event) =>
                            setData(
                                'emergency_contact_relationship',
                                event.target.value,
                            )
                        }
                    />
                </Field>
            </Section>

            <Section title="Additional Information">
                <Field label="Referral Source *" error={errors.referral_source}>
                    <Select
                        value={data.referral_source}
                        onValueChange={(value) =>
                            setData('referral_source', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select referral source" />
                        </SelectTrigger>
                        <SelectContent>
                            {leadSource.map((source) => (
                                <SelectItem key={source} value={source}>
                                    {source}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>
                {data.referral_source === 'Other' && (
                    <Field
                        label="Referral Source (Other)"
                        error={errors.referral_source_other}
                    >
                        <Input
                            value={data.referral_source_other}
                            onChange={(event) =>
                                setData(
                                    'referral_source_other',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                )}
                <Field
                    label="Additional Information"
                    error={errors.additional_information}
                >
                    <Textarea
                        value={data.additional_information}
                        onChange={(event) =>
                            setData(
                                'additional_information',
                                event.target.value,
                            )
                        }
                    />
                </Field>
            </Section>

            {isEdit && (intake?.documents ?? []).length > 0 && (
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="font-medium">Documents</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            {(intake?.documents ?? []).map((document) => (
                                <li key={document.id}>
                                    {document.name} — {document.type}
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            )}

            <div className="flex justify-between gap-3">
                {isEdit ? (
                    <Button
                        type="button"
                        variant="outline"
                        className="rounded-[5px] text-red-700 hover:bg-red-600 hover:text-white"
                        onClick={() => setDeleteOpen(true)}
                    >
                        <Trash /> Delete Intake
                    </Button>
                ) : (
                    <span />
                )}

                <Button
                    type="submit"
                    className="rounded-[5px]"
                    disabled={processing}
                >
                    <Save /> {processing ? 'Saving...' : 'Save Intake'}
                </Button>
            </div>

            {isEdit && intake && (
                <DeleteIntakeModal
                    intakeId={intake.id}
                    isOpen={deleteOpen}
                    onClose={() => setDeleteOpen(false)}
                />
            )}
        </form>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <Card className="rounded-[10px]">
            <CardContent className="space-y-4 p-5">
                <p className="font-semibold text-primary">{title}</p>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {children}
                </div>
            </CardContent>
        </Card>
    );
}

function Field({
    label,
    error,
    hint,
    children,
}: {
    label: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1">
            <Label>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

function CheckboxGroup({
    label,
    options,
    selected,
    onToggle,
}: {
    label: string;
    options: string[];
    selected: string[];
    onToggle: (value: string) => void;
}) {
    return (
        <div className="space-y-1 md:col-span-2">
            <Label>{label}</Label>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => (
                    <button
                        key={option}
                        type="button"
                        onClick={() => onToggle(option)}
                        className={`rounded-[5px] border px-2 py-1 text-sm ${
                            selected.includes(option)
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border'
                        }`}
                    >
                        {option}
                    </button>
                ))}
            </div>
        </div>
    );
}
