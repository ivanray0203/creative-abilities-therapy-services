import { useForm } from '@inertiajs/react';
import { Eye, EyeOff, Plus, Printer, Save, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import FormErrorSummary from '@/components/forms/form-error-summary';
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { RESIDENT_STATUS, WEEK_DAYS } from '@/lib/content/careers-config';
import { allServices } from '@/lib/content/intake-taxonomy';
import type { EmploymentStatus, TeamMember } from '@/types/team-member';

const EMPLOYMENT_STATUSES: EmploymentStatus[] = [
    'onboarding',
    'active',
    'inactive',
    'on_leave',
    'terminated',
    'archived',
];

interface TeamMemberFormData {
    email: string;
    first_name: string;
    last_name: string;
    phone: string;
    office_phone: string;
    secondary_email: string;
    birthdate: string;
    sin_number: string;
    street_address: string;
    address_line_2: string;
    city: string;
    province: string;
    zip_code: string;
    resident_status: string;
    position: string;
    title: string;
    description: string;
    employment_status: EmploymentStatus;
    hire_date: string;
    hourly_rate: string;
    maximum_caseload: string;
    license_number: string;
    years_of_experience: string;
    availability: { week_day: string; time_from: string; time_to: string }[];
    credentials: string[];
    specializations: string[];
    emergency_contact_name: string;
    emergency_contact_phone: string;
    can_access_finance: boolean;
    can_manage_team: boolean;
    can_manage_clients: boolean;
    additional_notes: string;
}

function initialValues(teamMember?: TeamMember | null): TeamMemberFormData {
    return {
        email: teamMember?.user?.email ?? '',
        first_name: teamMember?.user?.first_name ?? '',
        last_name: teamMember?.user?.last_name ?? '',
        phone: teamMember?.phone ?? '',
        office_phone: teamMember?.office_phone ?? '',
        secondary_email: teamMember?.secondary_email ?? '',
        // Both arrive as full ISO timestamps; a date input renders blank
        // on anything that is not YYYY-MM-DD.
        birthdate: teamMember?.birthdate?.slice(0, 10) ?? '',
        sin_number: '',
        street_address: teamMember?.street_address ?? '',
        address_line_2: teamMember?.address_line_2 ?? '',
        city: teamMember?.city ?? '',
        province: teamMember?.province ?? '',
        zip_code: teamMember?.zip_code ?? '',
        resident_status: teamMember?.resident_status ?? '',
        position: teamMember?.position ?? '',
        title: teamMember?.title ?? '',
        description: teamMember?.description ?? '',
        employment_status: teamMember?.employment_status ?? 'active',
        hire_date: teamMember?.hire_date?.slice(0, 10) ?? '',
        hourly_rate: teamMember?.hourly_rate ?? '',
        maximum_caseload: teamMember ? String(teamMember.maximum_caseload) : '',
        license_number: teamMember?.license_number ?? '',
        years_of_experience: teamMember?.years_of_experience ?? '',
        availability: WEEK_DAYS.map((day) => {
            const existing = teamMember?.availability?.find(
                (slot) => slot.week_day === day,
            );

            return {
                week_day: day,
                time_from: existing?.time_from ?? '',
                time_to: existing?.time_to ?? '',
            };
        }),
        credentials: teamMember?.credentials ?? [],
        specializations: teamMember?.specializations ?? [],
        emergency_contact_name: teamMember?.emergency_contact_name ?? '',
        emergency_contact_phone: teamMember?.emergency_contact_phone ?? '',
        can_access_finance: teamMember?.can_access_finance ?? false,
        can_manage_team: teamMember?.can_manage_team ?? false,
        can_manage_clients: teamMember?.can_manage_clients ?? true,
        additional_notes: teamMember?.additional_notes ?? '',
    };
}

/** Reference: cats-frontend/src/components/TeamMemberForm.tsx */
export default function TeamMemberForm({
    teamMember,
}: {
    teamMember?: TeamMember | null;
}) {
    const isEdit = teamMember != null;
    const [showSIN, setShowSIN] = useState(false);
    const [credentialInput, setCredentialInput] = useState('');

    const { data, setData, post, put, processing, errors } =
        useForm<TeamMemberFormData>(initialValues(teamMember));

    /*
     * A rejected save used to look like nothing happening: the fields that
     * failed can sit several cards below the button, so an inline message
     * under one of them is easy to never scroll to. Records created by the
     * hire flow arrive without an emergency contact, which this form requires,
     * so that was every hired therapist's first edit.
     */
    const reportErrors = (validationErrors: Record<string, string>) => {
        const [firstField] = Object.keys(validationErrors);

        if (!firstField) {
            return;
        }

        toast.error(validationErrors[firstField]);

        requestAnimationFrame(() => {
            document
                .getElementById('team-member-error-summary')
                ?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });
    };

    const submit = () => {
        if (isEdit && teamMember) {
            put(`/admin/team/${teamMember.id}`, { onError: reportErrors });

            return;
        }

        post('/admin/team', { onError: reportErrors });
    };

    const updateAvailability = (
        index: number,
        field: 'time_from' | 'time_to',
        value: string,
    ) => {
        setData(
            'availability',
            data.availability.map((slot, slotIndex) =>
                slotIndex === index ? { ...slot, [field]: value } : slot,
            ),
        );
    };

    const addCredential = () => {
        if (!credentialInput.trim()) {
            return;
        }

        setData('credentials', [...data.credentials, credentialInput.trim()]);
        setCredentialInput('');
    };

    const removeCredential = (credential: string) => {
        setData(
            'credentials',
            data.credentials.filter((entry) => entry !== credential),
        );
    };

    const toggleSpecialization = (specialization: string) => {
        setData(
            'specializations',
            data.specializations.includes(specialization)
                ? data.specializations.filter(
                      (entry) => entry !== specialization,
                  )
                : [...data.specializations, specialization],
        );
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6">
            <div id="team-member-error-summary">
                <FormErrorSummary
                    errors={errors as Record<string, string>}
                    title="This team member could not be saved"
                />
            </div>

            {/*
             * Personnel files still get kept on paper, so the whole record is
             * exportable. A plain link, not a router visit — the response is a
             * file download rather than an Inertia page.
             */}
            {isEdit && teamMember && (
                <div className="flex justify-end">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        asChild
                    >
                        <a href={`/admin/team/${teamMember.id}/pdf`}>
                            <Printer /> Export PDF
                        </a>
                    </Button>
                </div>
            )}

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <p className="font-bold text-primary md:col-span-2">
                        Personal Information
                    </p>

                    {!isEdit && (
                        <div>
                            <Label htmlFor="tm-email">Email *</Label>
                            <Input
                                id="tm-email"
                                type="email"
                                value={data.email}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                            />
                            {errors.email && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.email}
                                </p>
                            )}
                        </div>
                    )}

                    <div>
                        <Label htmlFor="tm-employee-id">Employee ID</Label>
                        <Input
                            id="tm-employee-id"
                            disabled
                            value={
                                teamMember
                                    ? `EMP-00${teamMember.id}`
                                    : 'Auto-assigned'
                            }
                            className="mt-2 rounded-[10px]"
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-first-name">First Name *</Label>
                        <Input
                            id="tm-first-name"
                            value={data.first_name}
                            disabled={isEdit}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('first_name', event.target.value)
                            }
                        />
                        {errors.first_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.first_name}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-last-name">Last Name *</Label>
                        <Input
                            id="tm-last-name"
                            value={data.last_name}
                            disabled={isEdit}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('last_name', event.target.value)
                            }
                        />
                        {errors.last_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.last_name}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-birthdate">Birthdate</Label>
                        <Input
                            id="tm-birthdate"
                            type="date"
                            value={data.birthdate}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('birthdate', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-sin">SIN</Label>
                        <div className="mt-2 flex gap-2">
                            <Input
                                id="tm-sin"
                                type={showSIN ? 'text' : 'password'}
                                value={data.sin_number}
                                placeholder={
                                    isEdit
                                        ? 'Leave blank to keep unchanged'
                                        : ''
                                }
                                className="rounded-[10px]"
                                onChange={(event) =>
                                    setData(
                                        'sin_number',
                                        event.target.value.replace(/\D/g, ''),
                                    )
                                }
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="rounded-[10px]"
                                onClick={() => setShowSIN((value) => !value)}
                            >
                                {showSIN ? <EyeOff /> : <Eye />}
                            </Button>
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="tm-phone">Phone</Label>
                        <Input
                            id="tm-phone"
                            value={data.phone}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-office-phone">Office Phone</Label>
                        <Input
                            id="tm-office-phone"
                            value={data.office_phone}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('office_phone', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-secondary-email">
                            Secondary Email
                        </Label>
                        <Input
                            id="tm-secondary-email"
                            type="email"
                            value={data.secondary_email}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('secondary_email', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-resident-status">
                            Resident Status *
                        </Label>
                        <Select
                            value={data.resident_status}
                            onValueChange={(value) =>
                                setData('resident_status', value)
                            }
                        >
                            <SelectTrigger
                                id="tm-resident-status"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                {RESIDENT_STATUS.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.resident_status && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.resident_status}
                            </p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="tm-street-address">
                            Street Address *
                        </Label>
                        <Input
                            id="tm-street-address"
                            value={data.street_address}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('street_address', event.target.value)
                            }
                        />
                        {errors.street_address && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.street_address}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-city">City *</Label>
                        <Input
                            id="tm-city"
                            value={data.city}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('city', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-province">Province *</Label>
                        <Input
                            id="tm-province"
                            value={data.province}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('province', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-zip">Postal Code *</Label>
                        <Input
                            id="tm-zip"
                            value={data.zip_code}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('zip_code', event.target.value)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <p className="font-bold text-primary md:col-span-2">
                        Employment Information
                    </p>

                    <div>
                        <Label htmlFor="tm-position">Position *</Label>
                        <Input
                            id="tm-position"
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
                        <Label htmlFor="tm-employment-status">
                            Employment Status *
                        </Label>
                        <Select
                            value={data.employment_status}
                            onValueChange={(value) =>
                                setData(
                                    'employment_status',
                                    value as EmploymentStatus,
                                )
                            }
                        >
                            <SelectTrigger
                                id="tm-employment-status"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {EMPLOYMENT_STATUSES.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label htmlFor="tm-hire-date">Hire Date *</Label>
                        <Input
                            id="tm-hire-date"
                            type="date"
                            value={data.hire_date}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('hire_date', event.target.value)
                            }
                        />
                        {errors.hire_date && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.hire_date}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-hourly-rate">
                            Hourly Rate ($) *
                        </Label>
                        <Input
                            id="tm-hourly-rate"
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.hourly_rate}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('hourly_rate', event.target.value)
                            }
                        />
                        {errors.hourly_rate && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.hourly_rate}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-max-caseload">
                            Maximum Caseload *
                        </Label>
                        <Input
                            id="tm-max-caseload"
                            type="number"
                            min={0}
                            value={data.maximum_caseload}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('maximum_caseload', event.target.value)
                            }
                        />
                        {errors.maximum_caseload && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.maximum_caseload}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-license-number">
                            License Number
                        </Label>
                        <Input
                            id="tm-license-number"
                            value={data.license_number}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('license_number', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-years-experience">
                            Years of Experience
                        </Label>
                        <Input
                            id="tm-years-experience"
                            value={data.years_of_experience}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'years_of_experience',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">
                        Weekly Availability
                    </p>

                    <div className="grid grid-cols-1 gap-3">
                        {data.availability.map((slot, index) => (
                            <div
                                key={slot.week_day}
                                className="grid grid-cols-1 items-center gap-3 md:grid-cols-3"
                            >
                                <p className="text-sm">{slot.week_day}</p>
                                <Input
                                    type="time"
                                    value={slot.time_from}
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
                                    value={slot.time_to}
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
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="mb-5 font-bold text-primary">Credentials</p>

                    <div className="flex gap-2">
                        <Input
                            value={credentialInput}
                            placeholder="e.g. OTRL"
                            className="rounded-[10px]"
                            onChange={(event) =>
                                setCredentialInput(event.target.value)
                            }
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    addCredential();
                                }
                            }}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-[10px]"
                            onClick={addCredential}
                        >
                            <Plus />
                        </Button>
                    </div>

                    <div className="mt-3 flex flex-wrap gap-2">
                        {data.credentials.map((credential) => (
                            <span
                                key={credential}
                                className="flex items-center gap-1 rounded-[5px] bg-gray-100 px-2 py-1 text-sm"
                            >
                                {credential}
                                <button
                                    type="button"
                                    onClick={() => removeCredential(credential)}
                                >
                                    <X className="h-3 w-3" />
                                </button>
                            </span>
                        ))}
                    </div>

                    <p className="mt-8 mb-3 font-bold text-primary">
                        Specializations
                    </p>
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                        {allServices.map((service) => (
                            <label
                                key={service}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={data.specializations.includes(
                                        service,
                                    )}
                                    onCheckedChange={() =>
                                        toggleSpecialization(service)
                                    }
                                />
                                {service}
                            </label>
                        ))}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <p className="font-bold text-primary md:col-span-2">
                        Emergency Contact
                    </p>

                    <div>
                        <Label htmlFor="tm-emergency-name">
                            Contact Name *
                        </Label>
                        <Input
                            id="tm-emergency-name"
                            value={data.emergency_contact_name}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'emergency_contact_name',
                                    event.target.value,
                                )
                            }
                        />
                        {errors.emergency_contact_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.emergency_contact_name}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="tm-emergency-phone">
                            Contact Phone *
                        </Label>
                        <Input
                            id="tm-emergency-phone"
                            value={data.emergency_contact_phone}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData(
                                    'emergency_contact_phone',
                                    event.target.value,
                                )
                            }
                        />
                        {errors.emergency_contact_phone && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.emergency_contact_phone}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <p className="font-bold text-primary">System Permissions</p>

                    <div className="flex items-center justify-between">
                        <Label htmlFor="tm-finance">Finance Access</Label>
                        <Switch
                            id="tm-finance"
                            checked={data.can_access_finance}
                            onCheckedChange={(checked) =>
                                setData('can_access_finance', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label htmlFor="tm-team-mgmt">Team Management</Label>
                        <Switch
                            id="tm-team-mgmt"
                            checked={data.can_manage_team}
                            onCheckedChange={(checked) =>
                                setData('can_manage_team', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label htmlFor="tm-client-mgmt">
                            Client Management
                        </Label>
                        <Switch
                            id="tm-client-mgmt"
                            checked={data.can_manage_clients}
                            onCheckedChange={(checked) =>
                                setData('can_manage_clients', checked)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="tm-notes">Additional Notes</Label>
                        <Textarea
                            id="tm-notes"
                            value={data.additional_notes}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('additional_notes', event.target.value)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button
                    className="rounded-[10px]"
                    onClick={submit}
                    disabled={processing}
                >
                    <Save /> {isEdit ? 'Save Changes' : 'Add Team Member'}
                </Button>
            </div>
        </div>
    );
}
