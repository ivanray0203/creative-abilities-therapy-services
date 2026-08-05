import { useForm } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    Dot,
    Eye,
    HandHelping,
    Info,
    Lock,
    Luggage,
    Save,
    Shield,
    UploadCloud,
    User,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import ApplicationPreviewModal from '@/components/application-preview-modal';
import ApplicationSubmittedModal from '@/components/application-submitted-modal';
import { EnterInput } from '@/components/enter-input';
import MissingFieldsModal, {
    formatLabel,
} from '@/components/missing-fields-modal';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    EDUCATION_OPTIONS,
    LEAD_SOURCE_OPTIONS,
    RESIDENT_STATUS,
    WEEK_DAYS,
} from '@/lib/content/careers-config';
import { Provinces } from '@/lib/content/intake-taxonomy';
import { computeFormProgress } from '@/lib/form-progress';
import type { Career } from '@/types/career';

const DRAFT_STORAGE_KEY = 'CatsApplicationDraft';

const REQUIRED_FIELDS = [
    'first_name',
    'last_name',
    'email',
    'phone',
    'resident_status',
    'street_address',
    'city',
    'province',
    'zip_code',
    'position_applied',
    'profession_status',
    'preferred_start_date',
    'education',
    'experience',
    'resume_file',
    'references.0.full_name',
    'references.0.email',
    'references.0.position',
    'references.0.work',
    'references.0.phone',
    'lead_source',
    'reason_for_applying',
    'skills',
];

interface AvailabilityEntry {
    week_day: string;
    time_from: string;
    time_to: string;
}

interface ReferenceEntry {
    full_name: string;
    position: string;
    work: string;
    email: string;
    phone: string;
}

interface CareerApplicationFormData {
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
    availability: AvailabilityEntry[];
    resume_file: File | null;
    cover_letter_file: File | null;
    drivers_license: boolean;
    has_vehicle: boolean;
    lead_source: string;
    lead_source_select: string;
    reason_for_applying: string;
    other_notes: string;
    expected_salary: string;
    experience: string;
    education: string;
    skills: string[];
    references: ReferenceEntry[];
}

const DEFAULT_VALUES: CareerApplicationFormData = {
    first_name: '',
    middle_name: '',
    last_name: '',
    phone: '',
    email: '',
    resident_status: '',
    street_address: '',
    address_line_2: '',
    city: '',
    province: '',
    zip_code: '',
    position_applied: '',
    position_id: null,
    profession_status: '',
    preferred_start_date: '',
    is_working_with_other: false,
    availability: WEEK_DAYS.map((day) => ({
        week_day: day,
        time_from: '',
        time_to: '',
    })),
    resume_file: null,
    cover_letter_file: null,
    drivers_license: false,
    has_vehicle: false,
    lead_source: '',
    lead_source_select: '',
    reason_for_applying: '',
    other_notes: '',
    expected_salary: '',
    experience: '',
    education: '',
    skills: [],
    references: [
        { full_name: '', position: '', work: '', email: '', phone: '' },
    ],
};

const PROFESSION_STATUS_OPTIONS = [
    { value: 'licensed', label: 'Fully Licensed/Registered' },
    { value: 'provisional', label: 'Provisional Registration' },
    { value: 'inprogress', label: 'In Process' },
    { value: 'graduate', label: 'Recent Graduate' },
    { value: 'student', label: 'Student (Practicum)' },
];

interface FileUploadFieldProps {
    label: string;
    accept?: string;
    required?: boolean;
    value: File | null;
    onChange: (file: File | null) => void;
    error?: string;
}

function FileUploadField({
    label,
    accept = '.pdf,.doc,.docx',
    required = false,
    value,
    onChange,
    error,
}: FileUploadFieldProps) {
    const inputId = label.replace(/\s+/g, '').toLowerCase();

    return (
        <div className="flex flex-col gap-2">
            <Label>
                {label} {required && <span className="text-red-700">*</span>}
            </Label>
            <label
                htmlFor={inputId}
                className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-primary/40 p-10 text-center transition-colors hover:bg-primary/5"
            >
                <UploadCloud className="mb-2 h-10 w-10 text-primary" />
                <span className="text-muted-foreground">
                    {value ? value.name : 'Click to upload or drag and drop'}
                </span>
                <span className="mt-1 text-xs text-muted-foreground">
                    Accepted formats: {accept}
                </span>
                <input
                    id={inputId}
                    type="file"
                    className="hidden"
                    accept={accept}
                    onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                />
            </label>
            {error && <p className="text-sm text-red-600">{error}</p>}
        </div>
    );
}

interface CareerApplicationFormProps {
    careers: Career[];
    preselectedCareer: Career | null;
}

/**
 * Career application form, ported from cats-frontend's
 * src/forms/CareerApplicationFrom.tsx. Replaces react-hook-form + zod +
 * axios with Inertia's useForm + Laravel validation, but preserves every
 * field, accordion section, the progress bar, draft autosave, and the
 * preview/submit/missing-fields modal flow.
 */
export default function CareerApplicationForm({
    careers,
    preselectedCareer,
}: CareerApplicationFormProps) {
    const { data, setData, post, processing, errors, reset, transform } =
        useForm<CareerApplicationFormData>(DEFAULT_VALUES);

    const { progress, missingFields } = useMemo(
        () => computeFormProgress(data, REQUIRED_FIELDS),
        [data],
    );
    const [consentGiven, setConsentGiven] = useState(false);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [isPreview, setIsPreview] = useState(false);
    const [submittedOpen, setSubmittedOpen] = useState(false);
    const [referenceNumber, setReferenceNumber] = useState<string | null>(null);
    const [missingDialogOpen, setMissingDialogOpen] = useState(false);
    const [lastSaved, setLastSaved] = useState<string | null>(null);
    const [showLoadDraftModal, setShowLoadDraftModal] = useState(false);
    const [savedDraft, setSavedDraft] =
        useState<Partial<CareerApplicationFormData> | null>(null);
    const [emailWarning, setEmailWarning] = useState<string | null>(null);
    const [checkingEmail, setCheckingEmail] = useState(false);

    const cookieConsentAllowed = () =>
        localStorage.getItem('cookieConsent') !== 'declined';

    // Strip the UI-only `lead_source_select` helper field before it's ever sent to the server.
    useEffect(() => {
        transform((formData) => {
            const { lead_source_select: _leadSourceSelect, ...rest } = formData;

            return rest;
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Preselect the position from the optional {career} route param.
    useEffect(() => {
        if (!preselectedCareer) {
            return;
        }

        setData((current) => ({
            ...current,
            position_applied: preselectedCareer.position,
            position_id: preselectedCareer.id,
        }));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [preselectedCareer]);

    // Debounced duplicate-email check (non-blocking warning, matches the reference's checkEmail() helper).
    useEffect(() => {
        if (!data.email) {
            return;
        }

        const timer = setTimeout(() => {
            setCheckingEmail(true);

            fetch(`/check-email?email=${encodeURIComponent(data.email)}`)
                .then((res) => res.json())
                .then((json: { exists?: boolean }) =>
                    setEmailWarning(
                        json.exists ? 'Email is already in use' : null,
                    ),
                )
                .catch(() => setEmailWarning(null))
                .finally(() => setCheckingEmail(false));
        }, 500);

        return () => clearTimeout(timer);
    }, [data.email]);

    // Autosave a draft every minute (gated behind cookie consent). Files are excluded — they aren't
    // JSON-serializable and must be re-selected by the applicant after loading a draft.
    useEffect(() => {
        if (!cookieConsentAllowed()) {
            return;
        }

        const interval = setInterval(() => {
            const {
                resume_file: _resumeFile,
                cover_letter_file: _coverLetterFile,
                ...rest
            } = data;
            localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(rest));
            setLastSaved(new Date().toLocaleTimeString());
        }, 60 * 1000);

        return () => clearInterval(interval);
    }, [data]);

    // Offer to restore a saved draft on mount.
    useEffect(() => {
        if (!cookieConsentAllowed()) {
            return;
        }

        const draft = localStorage.getItem(DRAFT_STORAGE_KEY);

        if (!draft) {
            return;
        }

        try {
            // localStorage only exists client-side, so this can't be computed during render (SSR) —
            // an effect is the correct place to read it.
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setSavedDraft(JSON.parse(draft));
            setShowLoadDraftModal(true);
        } catch {
            localStorage.removeItem(DRAFT_STORAGE_KEY);
        }
    }, []);

    const handleLoadDraft = () => {
        if (!savedDraft) {
            return;
        }

        setData((current) => ({
            ...current,
            ...savedDraft,
            resume_file: null,
            cover_letter_file: null,
        }));
        setShowLoadDraftModal(false);
    };

    const handleDiscardDraft = () => {
        localStorage.removeItem(DRAFT_STORAGE_KEY);
        setShowLoadDraftModal(false);
    };

    const updateAvailability = (
        idx: number,
        field: 'time_from' | 'time_to',
        value: string,
    ) => {
        setData(
            'availability',
            data.availability.map((slot, i) =>
                i === idx ? { ...slot, [field]: value } : slot,
            ),
        );
    };

    const updateReference = (
        idx: number,
        field: keyof ReferenceEntry,
        value: string,
    ) => {
        setData(
            'references',
            data.references.map((ref, i) =>
                i === idx ? { ...ref, [field]: value } : ref,
            ),
        );
    };

    const addReference = () => {
        setData('references', [
            ...data.references,
            { full_name: '', position: '', work: '', email: '', phone: '' },
        ]);
    };

    const removeReference = (idx: number) => {
        setData(
            'references',
            data.references.filter((_, i) => i !== idx),
        );
    };

    const submitApplication = () => {
        post('/careers/apply', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = (
                    page.props as {
                        flash?: { application?: { reference_number?: string } };
                    }
                ).flash;
                setReferenceNumber(
                    flash?.application?.reference_number ?? null,
                );
                localStorage.removeItem(DRAFT_STORAGE_KEY);
                setSubmittedOpen(true);
                reset();
            },
        });
    };

    return (
        <>
            <div className="sticky top-16 my-10 rounded-lg bg-white p-6 shadow-2xl">
                <div className="mb-4 flex flex-col items-start justify-between gap-2 sm:flex-row sm:items-center sm:gap-0">
                    <p className="flex items-center gap-2 text-sm font-medium text-primary sm:text-base">
                        <CheckCircle2 className="h-5 w-5" /> Application
                        Progress
                    </p>
                    <p className="text-sm font-semibold text-primary sm:text-base">
                        {progress}%
                    </p>
                </div>

                <div className="mb-4 h-2 w-full overflow-hidden rounded-full bg-primary/20">
                    <div
                        className="h-2 rounded-full bg-primary transition-all duration-500"
                        style={{ width: `${progress}%` }}
                    />
                </div>

                {missingFields.length > 0 && (
                    <div className="mb-4">
                        <p className="mb-2 text-xs font-medium text-muted-foreground">
                            Still missing ({missingFields.length}):
                        </p>
                        <div className="flex flex-wrap gap-1.5">
                            {missingFields.map((field) => (
                                <span
                                    key={field}
                                    className="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700"
                                >
                                    {formatLabel(field)}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                <p className="flex items-center gap-2 text-sm text-gray-500">
                    <Save className="h-4 w-4" /> Last Saved: {lastSaved ?? '—'}
                </p>
            </div>

            {/* Applicant Info */}
            <Accordion
                type="single"
                collapsible
                className="my-10 rounded-lg bg-white shadow-2xl"
            >
                <AccordionItem value="applicant-info">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <User className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Applicant Information
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div className="flex flex-col">
                                <Label htmlFor="firstName">
                                    First Name{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="firstName"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData('first_name', e.target.value)
                                    }
                                    placeholder="John"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.first_name && (
                                    <p className="text-sm text-red-600">
                                        {errors.first_name}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="middleName">Middle Name</Label>
                                <Input
                                    id="middleName"
                                    value={data.middle_name}
                                    onChange={(e) =>
                                        setData('middle_name', e.target.value)
                                    }
                                    placeholder="A."
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.middle_name && (
                                    <p className="text-sm text-red-600">
                                        {errors.middle_name}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="lastName">
                                    Last Name{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="lastName"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData('last_name', e.target.value)
                                    }
                                    placeholder="Doe"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.last_name && (
                                    <p className="text-sm text-red-600">
                                        {errors.last_name}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="flex flex-col">
                                <Label htmlFor="phone">
                                    Phone Number{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData(
                                            'phone',
                                            e.target.value.replace(
                                                /[^0-9()+-\s]/g,
                                                '',
                                            ),
                                        )
                                    }
                                    placeholder="+1 123-456-7890"
                                    className="mt-2 rounded-[10px]"
                                    maxLength={12}
                                />
                                {errors.phone && (
                                    <p className="text-sm text-red-600">
                                        {errors.phone}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="email">
                                    Email{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    placeholder="john.doe@example.com"
                                    className="mt-2 rounded-[10px]"
                                />
                                {checkingEmail && (
                                    <p className="text-sm text-gray-500">
                                        Checking...
                                    </p>
                                )}
                                {data.email && emailWarning && (
                                    <p className="text-sm text-amber-600">
                                        {emailWarning}
                                    </p>
                                )}
                                {errors.email && (
                                    <p className="text-sm text-red-600">
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="mt-2">
                            <Label>
                                Residential Status{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Select
                                value={data.resident_status}
                                onValueChange={(value) =>
                                    setData('resident_status', value)
                                }
                            >
                                <SelectTrigger className="mt-2 rounded-[10px]">
                                    <SelectValue placeholder="Select a Status" />
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
                                <p className="text-sm text-red-600">
                                    {errors.resident_status}
                                </p>
                            )}
                        </div>

                        <div className="mt-6 flex flex-col">
                            <Label htmlFor="street">
                                Street Address{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                id="street"
                                value={data.street_address}
                                onChange={(e) =>
                                    setData('street_address', e.target.value)
                                }
                                placeholder="123 Main St"
                                className="mt-2 rounded-[10px]"
                            />
                            {errors.street_address && (
                                <p className="text-sm text-red-600">
                                    {errors.street_address}
                                </p>
                            )}
                        </div>

                        <div className="mt-4 flex flex-col">
                            <Label htmlFor="address2">
                                Address Line 2 (Optional)
                            </Label>
                            <Input
                                id="address2"
                                value={data.address_line_2}
                                onChange={(e) =>
                                    setData('address_line_2', e.target.value)
                                }
                                placeholder="Apartment, suite, etc."
                                className="mt-2 rounded-[10px]"
                            />
                            {errors.address_line_2 && (
                                <p className="text-sm text-red-600">
                                    {errors.address_line_2}
                                </p>
                            )}
                        </div>

                        <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div className="flex flex-col">
                                <Label htmlFor="city">
                                    City <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="city"
                                    value={data.city}
                                    onChange={(e) =>
                                        setData('city', e.target.value)
                                    }
                                    placeholder="Calgary"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.city && (
                                    <p className="text-sm text-red-600">
                                        {errors.city}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>
                                    State / Province{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.province}
                                    onValueChange={(value) => {
                                        setData('province', value);
                                        setData('city', '');
                                    }}
                                >
                                    <SelectTrigger className="rounded-[5px]">
                                        <SelectValue placeholder="Select Province" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Provinces.map((province) => (
                                            <SelectItem
                                                key={province}
                                                value={province}
                                            >
                                                {province}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.province && (
                                    <p className="text-sm text-red-600">
                                        {errors.province}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="zip">
                                    ZIP / Postal Code{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="zip"
                                    value={data.zip_code}
                                    onChange={(e) =>
                                        setData('zip_code', e.target.value)
                                    }
                                    placeholder="T2N 1N4"
                                    className="mt-2 rounded-[10px]"
                                    maxLength={7}
                                />
                                {errors.zip_code && (
                                    <p className="text-sm text-red-600">
                                        {errors.zip_code}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Position Details */}
            <Accordion
                type="single"
                collapsible
                className="my-10 rounded-lg bg-white shadow-2xl"
            >
                <AccordionItem value="position-details">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Luggage className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Position Details
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label>
                                    Position{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.position_applied}
                                    onValueChange={(value) => {
                                        setData('position_applied', value);
                                        const selected = careers.find(
                                            (c) => c.position === value,
                                        );

                                        if (selected) {
                                            setData('position_id', selected.id);
                                        }
                                    }}
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select a position" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {careers.map((career) => (
                                            <SelectItem
                                                key={career.id}
                                                value={career.position}
                                            >
                                                {career.position}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.position_applied && (
                                    <p className="text-sm text-red-600">
                                        {errors.position_applied}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>
                                    Profession status{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.profession_status}
                                    onValueChange={(value) =>
                                        setData('profession_status', value)
                                    }
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {PROFESSION_STATUS_OPTIONS.map(
                                            (option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.profession_status && (
                                    <p className="text-sm text-red-600">
                                        {errors.profession_status}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Availability */}
            <Accordion
                type="single"
                collapsible
                className="my-10 rounded-lg bg-white shadow-2xl"
            >
                <AccordionItem value="availability">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Calendar className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Availability
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <p className="mb-4">
                            Please indicate your available hours for each day of
                            the week.
                        </p>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {data.availability.map((slot, idx) => (
                                <div
                                    key={slot.week_day}
                                    className="flex flex-col gap-2"
                                >
                                    <Label>{slot.week_day}</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            type="time"
                                            value={slot.time_from}
                                            onChange={(e) =>
                                                updateAvailability(
                                                    idx,
                                                    'time_from',
                                                    e.target.value,
                                                )
                                            }
                                            className="rounded-[10px]"
                                        />
                                        <Input
                                            type="time"
                                            value={slot.time_to}
                                            onChange={(e) =>
                                                updateAvailability(
                                                    idx,
                                                    'time_to',
                                                    e.target.value,
                                                )
                                            }
                                            className="rounded-[10px]"
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-4">
                            <Label>
                                Preferred Start Date{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                type="date"
                                value={data.preferred_start_date}
                                onChange={(e) =>
                                    setData(
                                        'preferred_start_date',
                                        e.target.value,
                                    )
                                }
                                className="mt-2 rounded-[10px]"
                                min={new Date().toISOString().split('T')[0]}
                            />
                            {errors.preferred_start_date && (
                                <p className="text-sm text-red-600">
                                    {errors.preferred_start_date}
                                </p>
                            )}
                        </div>

                        <div className="mt-4">
                            <Label>
                                Are you currently working with other contractors
                                or companies?{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <RadioGroup
                                value={
                                    data.is_working_with_other ? 'yes' : 'no'
                                }
                                onValueChange={(v) =>
                                    setData(
                                        'is_working_with_other',
                                        v === 'yes',
                                    )
                                }
                            >
                                <div className="mt-2 flex flex-row gap-6">
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="yes"
                                            id="working-yes"
                                        />
                                        <Label htmlFor="working-yes">Yes</Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="no"
                                            id="working-no"
                                        />
                                        <Label htmlFor="working-no">No</Label>
                                    </div>
                                </div>
                            </RadioGroup>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Personal Info */}
            <Accordion
                type="single"
                collapsible
                className="my-10 rounded-lg bg-white shadow-2xl"
            >
                <AccordionItem value="personal-info">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <User className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Personal Information
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div className="mb-4">
                            <Label>
                                Highest Level of Education{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Select
                                value={data.education}
                                onValueChange={(value) =>
                                    setData('education', value)
                                }
                            >
                                <SelectTrigger className="mt-2 rounded-[10px]">
                                    <SelectValue placeholder="Select highest level of education" />
                                </SelectTrigger>
                                <SelectContent>
                                    {EDUCATION_OPTIONS.map((option) => (
                                        <SelectItem
                                            key={option.key}
                                            value={option.key}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.education && (
                                <p className="text-sm text-red-600">
                                    {errors.education}
                                </p>
                            )}
                        </div>

                        <div className="mb-4">
                            <Label>
                                Years of Experience?{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                value={data.experience}
                                onChange={(e) =>
                                    setData('experience', e.target.value)
                                }
                                className="mt-2 rounded-[10px]"
                                placeholder="e.g., 5 Years"
                            />
                            {errors.experience && (
                                <p className="text-sm text-red-600">
                                    {errors.experience}
                                </p>
                            )}
                        </div>

                        <div className="mb-4">
                            <EnterInput
                                inputs={data.skills}
                                setInputs={(skills) =>
                                    setData('skills', skills)
                                }
                                error={errors.skills}
                                title="Skills"
                            />
                        </div>

                        <div className="mb-4">
                            <FileUploadField
                                label="Upload Resume (pdf, doc, docx)"
                                accept=".pdf,.doc,.docx"
                                required
                                value={data.resume_file}
                                onChange={(file) =>
                                    setData('resume_file', file)
                                }
                                error={errors.resume_file}
                            />
                        </div>

                        <div className="mb-4">
                            <FileUploadField
                                label="Upload Cover Letter (pdf, doc, docx)"
                                accept=".pdf,.doc,.docx"
                                value={data.cover_letter_file}
                                onChange={(file) =>
                                    setData('cover_letter_file', file)
                                }
                                error={errors.cover_letter_file}
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label>
                                    Do you have a valid driver's license?
                                </Label>
                                <RadioGroup
                                    value={data.drivers_license ? 'yes' : 'no'}
                                    onValueChange={(v) =>
                                        setData('drivers_license', v === 'yes')
                                    }
                                >
                                    <div className="mt-2 flex flex-row gap-6">
                                        <div className="flex items-center gap-2">
                                            <RadioGroupItem
                                                value="yes"
                                                id="license-yes"
                                            />
                                            <Label htmlFor="license-yes">
                                                Yes
                                            </Label>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <RadioGroupItem
                                                value="no"
                                                id="license-no"
                                            />
                                            <Label htmlFor="license-no">
                                                No
                                            </Label>
                                        </div>
                                    </div>
                                </RadioGroup>
                            </div>

                            <div>
                                <Label>Do you have access to a vehicle?</Label>
                                <RadioGroup
                                    value={data.has_vehicle ? 'yes' : 'no'}
                                    onValueChange={(v) =>
                                        setData('has_vehicle', v === 'yes')
                                    }
                                >
                                    <div className="mt-2 flex flex-row gap-6">
                                        <div className="flex items-center gap-2">
                                            <RadioGroupItem
                                                value="yes"
                                                id="vehicle-yes"
                                            />
                                            <Label htmlFor="vehicle-yes">
                                                Yes
                                            </Label>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <RadioGroupItem
                                                value="no"
                                                id="vehicle-no"
                                            />
                                            <Label htmlFor="vehicle-no">
                                                No
                                            </Label>
                                        </div>
                                    </div>
                                </RadioGroup>
                            </div>
                        </div>

                        {/* References */}
                        <div className="mt-6">
                            <h3 className="mb-4 font-medium text-primary">
                                References{' '}
                                <span className="text-xs text-muted-foreground">
                                    (at least 1 reference is required)
                                </span>
                            </h3>

                            {data.references.map((reference, idx) => (
                                <div
                                    key={idx}
                                    className="relative mb-4 rounded-lg border border-gray-200 p-4"
                                >
                                    <p className="mb-2 font-semibold">
                                        Reference {idx + 1}
                                    </p>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <Label>
                                                Full Name{' '}
                                                {idx === 0 ? '*' : ' '}
                                            </Label>
                                            <Input
                                                value={reference.full_name}
                                                onChange={(e) =>
                                                    updateReference(
                                                        idx,
                                                        'full_name',
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-2 rounded-[10px]"
                                            />
                                            {errors[
                                                `references.${idx}.full_name`
                                            ] && (
                                                <p className="text-sm text-red-600">
                                                    {
                                                        errors[
                                                            `references.${idx}.full_name`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label>
                                                Position {idx === 0 ? '*' : ' '}
                                            </Label>
                                            <Input
                                                value={reference.position}
                                                onChange={(e) =>
                                                    updateReference(
                                                        idx,
                                                        'position',
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-2 rounded-[10px]"
                                            />
                                            {errors[
                                                `references.${idx}.position`
                                            ] && (
                                                <p className="text-sm text-red-600">
                                                    {
                                                        errors[
                                                            `references.${idx}.position`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label>
                                                Work {idx === 0 ? '*' : ' '}
                                            </Label>
                                            <Input
                                                value={reference.work}
                                                onChange={(e) =>
                                                    updateReference(
                                                        idx,
                                                        'work',
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-2 rounded-[10px]"
                                            />
                                            {errors[
                                                `references.${idx}.work`
                                            ] && (
                                                <p className="text-sm text-red-600">
                                                    {
                                                        errors[
                                                            `references.${idx}.work`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label>
                                                Email {idx === 0 ? '*' : ' '}
                                            </Label>
                                            <Input
                                                value={reference.email}
                                                onChange={(e) =>
                                                    updateReference(
                                                        idx,
                                                        'email',
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-2 rounded-[10px]"
                                            />
                                            {errors[
                                                `references.${idx}.email`
                                            ] && (
                                                <p className="text-sm text-red-600">
                                                    {
                                                        errors[
                                                            `references.${idx}.email`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label>
                                                Phone {idx === 0 ? '*' : ' '}
                                            </Label>
                                            <Input
                                                value={reference.phone}
                                                onChange={(e) =>
                                                    updateReference(
                                                        idx,
                                                        'phone',
                                                        e.target.value.replace(
                                                            /[^0-9()+-\s]/g,
                                                            '',
                                                        ),
                                                    )
                                                }
                                                className="mt-2 rounded-[10px]"
                                                maxLength={12}
                                            />
                                            {errors[
                                                `references.${idx}.phone`
                                            ] && (
                                                <p className="text-sm text-red-600">
                                                    {
                                                        errors[
                                                            `references.${idx}.phone`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {data.references.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={() => removeReference(idx)}
                                            className="absolute top-2 right-2 text-sm text-red-600 hover:underline"
                                        >
                                            Remove
                                        </button>
                                    )}
                                </div>
                            ))}

                            <button
                                type="button"
                                onClick={addReference}
                                className="mt-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                            >
                                Add Reference
                            </button>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Additional Information */}
            <Accordion
                type="single"
                collapsible
                className="my-10 rounded-lg bg-white shadow-2xl"
            >
                <AccordionItem value="additional-info">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Info className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Additional Information
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div>
                            <Label>Expected Salary?</Label>
                            <Input
                                value={data.expected_salary}
                                onChange={(e) =>
                                    setData('expected_salary', e.target.value)
                                }
                                className="mt-2 rounded-[10px]"
                                placeholder="e.g. 20/hr , $25,000"
                                maxLength={15}
                            />
                        </div>

                        <div className="mt-1">
                            <Label>
                                How did you hear about us?{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Select
                                value={data.lead_source_select}
                                onValueChange={(value) => {
                                    setData('lead_source_select', value);
                                    setData(
                                        'lead_source',
                                        value !== 'other' ? value : '',
                                    );
                                }}
                            >
                                <SelectTrigger className="mt-2 rounded-[10px]">
                                    <SelectValue placeholder="Select an option" />
                                </SelectTrigger>
                                <SelectContent>
                                    {LEAD_SOURCE_OPTIONS.map((option) => (
                                        <SelectItem
                                            key={option.key}
                                            value={option.key}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.lead_source && (
                                <p className="text-sm text-red-600">
                                    {errors.lead_source}
                                </p>
                            )}
                        </div>

                        {data.lead_source_select === 'other' && (
                            <div className="mt-3">
                                <Label>
                                    Please specify{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    value={data.lead_source}
                                    onChange={(e) =>
                                        setData('lead_source', e.target.value)
                                    }
                                    className="mt-2 rounded-[10px]"
                                    placeholder="Please specify"
                                />
                            </div>
                        )}

                        <div className="mt-1">
                            <Label>
                                Why would you like to work with Creative
                                Abilities Therapy Services?{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <textarea
                                value={data.reason_for_applying}
                                onChange={(e) =>
                                    setData(
                                        'reason_for_applying',
                                        e.target.value,
                                    )
                                }
                                className="mt-2 w-full rounded-[10px] border px-2 py-1"
                                placeholder="Tell us about your interest in joining out team..."
                                rows={4}
                            />
                            {errors.reason_for_applying && (
                                <p className="text-sm text-red-600">
                                    {errors.reason_for_applying}
                                </p>
                            )}
                        </div>

                        <div className="mt-1">
                            <Label>Any other comments or questions</Label>
                            <textarea
                                value={data.other_notes}
                                onChange={(e) =>
                                    setData('other_notes', e.target.value)
                                }
                                className="mt-2 w-full rounded-[10px] border px-2 py-1"
                                placeholder="Optional - Share any additional information..."
                                rows={4}
                            />
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Privacy & Consent */}
            <Accordion
                type="single"
                collapsible
                className="my-10 rounded-lg bg-white shadow-2xl"
            >
                <AccordionItem value="privacy-consent">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Shield className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Privacy &amp; Consent
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <p className="mb-4">
                            Your privacy is important to us. Please review and
                            accept our data handling practices.
                        </p>

                        <div className="m-5 rounded-sm border bg-white p-5 shadow-sm">
                            <p className="flex flex-row items-center gap-2 text-base font-medium">
                                <Info className="h-5 w-5 text-primary" />{' '}
                                Information We Collect
                            </p>
                            <div className="mt-4 space-y-2">
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Personal Information:
                                    </span>{' '}
                                    Name, contact details, and address
                                </p>
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Professional Documents:
                                    </span>{' '}
                                    Resume, cover letter, and certifications
                                </p>
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Application Details:
                                    </span>{' '}
                                    Position preferences, availability, and
                                    qualifications
                                </p>
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Additional Information:
                                    </span>{' '}
                                    References and other supporting materials
                                    you provide
                                </p>
                            </div>
                        </div>

                        <div className="m-5 rounded-sm border p-5">
                            <p className="flex flex-row items-center gap-2 text-base">
                                <HandHelping /> How We Use Your Information
                            </p>
                            <p className="mt-2 flex flex-row items-center gap-2 text-sm">
                                <Dot className="text-primary" /> To evaluate
                                your qualifications for the position
                            </p>
                            <p className="mt-2 flex flex-row items-center gap-2 text-sm">
                                <Dot className="text-primary" /> To communicate
                                with you about your application status
                            </p>
                            <p className="mt-2 flex flex-row items-center gap-2 text-sm">
                                <Dot className="text-primary" /> To conduct
                                reference and background checks (if applicable)
                            </p>
                            <p className="mt-2 flex flex-row items-center gap-2 text-sm">
                                <Dot className="text-primary" /> To comply with
                                legal and regulatory requirements
                            </p>
                        </div>

                        <div className="m-5 rounded-sm border bg-white p-5 shadow-sm">
                            <p className="flex flex-row items-center gap-2 text-base font-medium">
                                <Lock className="h-5 w-5 text-primary" />
                                Data Retention &amp; Security
                            </p>
                            <div className="mt-4 space-y-2">
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Retention Period:
                                    </span>{' '}
                                    Applications are kept for 12 months for
                                    future opportunities
                                </p>
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Access:
                                    </span>{' '}
                                    Only authorized HR personnel and hiring
                                    managers have access
                                </p>
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Security:
                                    </span>{' '}
                                    All data is encrypted and stored securely
                                </p>
                                <p className="flex flex-row flex-wrap items-center gap-2 text-sm">
                                    <Dot className="mt-1 text-primary" />
                                    <span className="font-bold">
                                        Your Rights:
                                    </span>{' '}
                                    You may request deletion of your data at any
                                    time
                                </p>
                            </div>
                        </div>

                        <div className="m-5 flex flex-col gap-5 rounded-sm border-2 border-primary/50 bg-secondary-orange/10 p-5 sm:flex-row">
                            <div className="flex flex-shrink-0 items-start gap-2 sm:items-center">
                                <input
                                    type="checkbox"
                                    id="consent"
                                    checked={consentGiven}
                                    disabled={progress !== 100}
                                    className={
                                        progress !== 100
                                            ? 'mt-1 h-5 w-5 cursor-not-allowed accent-primary opacity-50'
                                            : 'mt-1 h-5 w-5 accent-primary'
                                    }
                                    onChange={(e) =>
                                        setConsentGiven(e.target.checked)
                                    }
                                />
                                <label
                                    htmlFor="consent"
                                    className="font-bold text-primary"
                                >
                                    I consent and acknowledge that
                                </label>
                            </div>

                            <div className="flex flex-1 flex-col gap-2 text-sm">
                                <p className="flex gap-2">
                                    <Dot className="mt-1 flex-shrink-0 text-primary" />
                                    I have read and understood how my
                                    information will be used
                                </p>
                                <p className="flex gap-2">
                                    <Dot className="mt-1 flex-shrink-0 text-primary" />
                                    I consent to Creative Abilities Therapy
                                    Services collecting, using, and storing my
                                    personal information as described above
                                </p>
                                <p className="flex gap-2">
                                    <Dot className="mt-1 flex-shrink-0 text-primary" />
                                    I understand my application will be retained
                                    for 12 months
                                </p>
                                <p className="flex gap-2">
                                    <Dot className="mt-1 flex-shrink-0 text-primary" />
                                    I have read and agree to the{' '}
                                    <a
                                        href="/termsandconditions"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-primary"
                                    >
                                        Terms and Conditions
                                    </a>{' '}
                                    and{' '}
                                    <a
                                        href="/privacypolicy"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-primary"
                                    >
                                        Privacy Policy
                                    </a>
                                </p>
                                <p className="flex gap-2">
                                    <Dot className="mt-1 flex-shrink-0 text-primary" />
                                    I certify that all information provided is
                                    true and accurate
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-col items-center justify-center">
                            <p className="text-center text-sm text-muted-foreground">
                                For complete details about how we handle your
                                data, please review our{' '}
                                <a
                                    href="/privacypolicy"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary"
                                >
                                    Privacy Policy
                                </a>{' '}
                                and{' '}
                                <a
                                    href="/termsandconditions"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary"
                                >
                                    Terms and Conditions
                                </a>
                                .
                            </p>
                            <p className="mt-3 text-center text-sm text-muted-foreground">
                                Questions about our data practices? Contact us
                                at{' '}
                                <a
                                    href="mailto:privacy@creativeabilitiestherapy.com"
                                    className="text-primary"
                                >
                                    privacy@creativeabilitiestherapy.com
                                </a>
                            </p>
                        </div>

                        <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                            <Button
                                className="flex-1 rounded-[10px] border-2 border-primary py-5 text-xl text-primary hover:bg-primary hover:text-white"
                                variant="outline"
                                type="button"
                                onClick={() => {
                                    if (missingFields.length > 0) {
                                        setMissingDialogOpen(true);
                                    } else {
                                        setIsPreview(true);
                                        setPreviewOpen(true);
                                    }
                                }}
                            >
                                <Eye className="mr-2 inline-block h-6 w-6 sm:h-10 sm:w-10" />
                                Preview Application
                            </Button>

                            <Button
                                className="flex-1 rounded-[10px] border-2 border-primary py-5 text-xl text-white"
                                disabled={!consentGiven || processing}
                                type="button"
                                onClick={() => {
                                    setIsPreview(false);
                                    setPreviewOpen(true);
                                }}
                            >
                                {processing
                                    ? 'Submitting...'
                                    : 'Submit Application'}
                            </Button>
                        </div>

                        <p className="mt-10 text-center text-sm text-muted-foreground">
                            Please accept the consent agreement above to enable
                            submission
                        </p>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            <ApplicationPreviewModal
                isOpen={previewOpen}
                onClose={() => setPreviewOpen(false)}
                applicationData={data}
                isPreview={isPreview}
                submitApplication={submitApplication}
            />

            <ApplicationSubmittedModal
                isOpen={submittedOpen}
                onClose={() => setSubmittedOpen(false)}
                referenceNumber={referenceNumber}
            />

            <MissingFieldsModal
                open={missingDialogOpen}
                onOpenChange={setMissingDialogOpen}
                missingFields={missingFields}
            />

            <Dialog
                open={showLoadDraftModal}
                onOpenChange={(open) => !open && setShowLoadDraftModal(false)}
            >
                <DialogContent className="max-w-md p-6">
                    <DialogHeader>
                        <DialogTitle>Unfinished Application Found</DialogTitle>
                    </DialogHeader>
                    <div className="my-4">
                        You have a saved draft of an application. Do you want to
                        load it?
                    </div>
                    <DialogFooter className="flex justify-end gap-4">
                        <Button variant="outline" onClick={handleDiscardDraft}>
                            Discard
                        </Button>
                        <Button onClick={handleLoadDraft}>Load Draft</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
