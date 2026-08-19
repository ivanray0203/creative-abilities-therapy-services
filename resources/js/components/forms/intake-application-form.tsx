import { useForm } from '@inertiajs/react';
import {
    Building2,
    Calendar,
    CheckCheck,
    CheckCircle2,
    Clock,
    Copy,
    CreditCard,
    Eye,
    GraduationCap,
    Hospital,
    InfoIcon,
    Lock,
    Phone,
    Save,
    ServerCog,
    Timer,
    User,
    Users,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import AvailabilityGrid from '@/components/availability-grid';
import ConsentModal from '@/components/consent-modal';
import FormErrorSummary from '@/components/forms/form-error-summary';
import FscdConsentTermsModal from '@/components/fscd-consent-terms-modal';
import IntakePreviewModal from '@/components/intake-preview-modal';
import IntakeSubmittedModal from '@/components/intake-submitted-modal';
import MissingFieldsModal from '@/components/missing-fields-modal';
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
import { fscdConsentTerms } from '@/lib/content/fscd-consent-terms';
import type { AvailabilitySlots } from '@/lib/content/intake-taxonomy';
import {
    FUNDING_SOURCE_LABELS,
    intakeServicesFor,
    leadSource,
    medicalConditionsOptions,
    ProvinceCities,
    Provinces,
    SS_ONLY_SERVICE,
    toggleAvailabilitySlot,
} from '@/lib/content/intake-taxonomy';
import { computeIntakeProgress } from '@/lib/form-progress';
import type { ConsentDocument } from '@/types/consent';

const DRAFT_STORAGE_KEY = 'CatsIntakeDraft';

const FSCD_FUNDING_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

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

interface IntakeFormData {
    child_first_name: string;
    child_middle_name: string;
    child_last_name: string;
    date_of_birth: string;
    age: number;
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
    diagnosis_other: string;
    has_medical_conditions: boolean;
    languages_spoken_at_home: string;
    require_interpreter: boolean;
    funding_source: string;
    availability_slots: AvailabilitySlots;
    primary_parent_name: string;
    primary_parent_phone: string;
    primary_parent_email: string;
    primary_parent_email_confirm: string;
    primary_relationship_to_child: string;
    primary_contact_method: string;
    secondary_parent_name: string;
    secondary_parent_phone: string;
    secondary_parent_email: string;
    secondary_parent_email_confirm: string;
    secondary_relationship_to_child: string;
    secondary_contact_method: string;
    additional_information: string;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    emergency_contact_relationship: string;
    interpreter_needed: string;
    medical_conditions: string;
    fscd_info: FscdInfo;
    insurance_info: InsuranceInfo;
    referral_source: string;
    referral_source_other: string;
    terms_accepted: boolean;
    privacy_accepted: boolean;
    consent_ids: number[];
}

const DEFAULT_VALUES: IntakeFormData = {
    child_first_name: '',
    child_middle_name: '',
    child_last_name: '',
    date_of_birth: '',
    age: 0,
    gender: '',
    street_address: '',
    address_line_2: '',
    city: '',
    state_province: 'Alberta',
    postal_code: '',
    grade_level: '',
    school_name: '',
    services_needed: [],
    currently_receiving_services: false,
    receiving_services_desc: '',
    diagnosis: [],
    diagnosis_other: '',
    has_medical_conditions: false,
    languages_spoken_at_home: '',
    require_interpreter: false,
    funding_source: '',
    availability_slots: {},
    primary_parent_name: '',
    primary_parent_phone: '',
    primary_parent_email: '',
    primary_parent_email_confirm: '',
    primary_relationship_to_child: '',
    primary_contact_method: '',
    secondary_parent_name: '',
    secondary_parent_phone: '',
    secondary_parent_email: '',
    secondary_parent_email_confirm: '',
    secondary_relationship_to_child: '',
    secondary_contact_method: '',
    additional_information: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relationship: '',
    interpreter_needed: '',
    medical_conditions: '',
    fscd_info: {
        FSCD_case_worker_name: '',
        FSCD_case_worker_email: '',
        FSCD_approval_start_date: '',
        FSCD_approval_end_date: '',
    },
    insurance_info: {
        insurance_provider: '',
        policy_holder_name: '',
        policy_number: '',
        certificate_number: '',
        policy_holder_date_of_birth: '',
        pre_authorization_obtained: '',
        used_annual_maximum: '',
        authorization_start_date: '',
        authorization_end_date: '',
    },
    referral_source: '',
    referral_source_other: '',
    terms_accepted: false,
    privacy_accepted: false,
    consent_ids: [],
};

function toggleArrayValue(list: string[], value: string): string[] {
    return list.includes(value)
        ? list.filter((v) => v !== value)
        : [...list, value];
}

interface IntakeApplicationFormProps {
    requiredConsents: ConsentDocument[];
    /** Where the completed form posts. Defaults to the public endpoint. */
    submitUrl?: string;
    /**
     * Values carried over from a child already in care, used when a
     * signed-in parent registers another child (Phase 17).
     */
    prefill?: Partial<IntakeFormData>;
    /**
     * Namespaces the autosaved draft so a portal draft and a public draft
     * can't overwrite each other in localStorage.
     */
    draftKey?: string;
    /**
     * Fields to render read-only, used by the portal's "Register Another
     * Child" form to lock the parent's own details to their account.
     *
     * Callers must only lock fields they actually pre-filled — a locked
     * required field left empty can never be completed. Values still post,
     * since useForm submits from state rather than serializing the DOM.
     */
    lockedFields?: readonly (keyof IntakeFormData)[];
}

/**
 * Intake application form, ported from cats-frontend's
 * src/forms/IntakeApplicationForm.tsx. Replaces react-hook-form + zod +
 * react-query + axios with Inertia's useForm + Laravel validation, and the
 * client-side consent-document fetch with the `requiredConsents` Inertia
 * prop. Preserves every field, accordion section, the funding-source
 * conditional branches, the progress bar, draft autosave, and the
 * preview/submit/missing-fields modal flow.
 */
export default function IntakeApplicationForm({
    requiredConsents,
    submitUrl = '/intake/apply',
    prefill,
    draftKey = DRAFT_STORAGE_KEY,
    lockedFields = [],
}: IntakeApplicationFormProps) {
    const { data, setData, post, processing, errors, reset, transform } =
        useForm<IntakeFormData>({ ...DEFAULT_VALUES, ...prefill });

    const isLocked = (field: keyof IntakeFormData) =>
        lockedFields.includes(field);

    const [lastSaved, setLastSaved] = useState<string | null>(null);
    const [consentGivenTerms, setConsentGivenTerms] = useState(false);
    const [consentGivenPrivacy, setConsentGivenPrivacy] = useState(false);
    const [consentGivenFSCD1, setConsentGivenFSCD1] = useState(false);
    const [consentGivenFSCD2, setConsentGivenFSCD2] = useState(false);
    const [consentGivenFSCD3, setConsentGivenFSCD3] = useState(false);
    // Which FSCD consents the applicant has read to the end, keyed by
    // consent id. A consent can't be checked until its terms are read.
    const [fscdTermsRead, setFscdTermsRead] = useState<Record<string, boolean>>(
        {},
    );
    const [openFscdTermsId, setOpenFscdTermsId] = useState<string | null>(null);
    const { progress, missingFields } = useMemo(
        () =>
            computeIntakeProgress(data, [
                consentGivenFSCD1,
                consentGivenFSCD2,
                consentGivenFSCD3,
            ]),
        [data, consentGivenFSCD1, consentGivenFSCD2, consentGivenFSCD3],
    );
    const [previewOpen, setPreviewOpen] = useState(false);
    const [submittedOpen, setSubmittedOpen] = useState(false);
    const [referenceNumber, setReferenceNumber] = useState<string | null>(null);
    const [missingDialogOpen, setMissingDialogOpen] = useState(false);
    const [selectedConsent, setSelectedConsent] =
        useState<ConsentDocument | null>(null);
    const [consentDialogOpen, setConsentDialogOpen] = useState(false);
    const [consentStates, setConsentStates] = useState<Record<number, boolean>>(
        {},
    );
    const [showLoadDraftModal, setShowLoadDraftModal] = useState(false);
    const [savedDraft, setSavedDraft] =
        useState<Partial<IntakeFormData> | null>(null);

    /** Binds each content-defined FSCD consent to its checkbox state. */
    const fscdConsentState: Record<
        string,
        { checked: boolean; setChecked: (checked: boolean) => void }
    > = {
        consentFSCD1: {
            checked: consentGivenFSCD1,
            setChecked: setConsentGivenFSCD1,
        },
        consentFSCD2: {
            checked: consentGivenFSCD2,
            setChecked: setConsentGivenFSCD2,
        },
        consentFSCD3: {
            checked: consentGivenFSCD3,
            setChecked: setConsentGivenFSCD3,
        },
    };

    const openFscdTerms =
        fscdConsentTerms.find((terms) => terms.id === openFscdTermsId) ?? null;

    const allConsentsGiven = requiredConsents.every((c) => consentStates[c.id]);
    const isFscdFunding = FSCD_FUNDING_SOURCES.includes(data.funding_source);

    const cookieConsentAllowed = () =>
        localStorage.getItem('cookieConsent') !== 'declined';

    const primaryEmailMismatch =
        Boolean(data.primary_parent_email_confirm) &&
        data.primary_parent_email !== data.primary_parent_email_confirm;
    const secondaryEmailMismatch =
        Boolean(data.secondary_parent_email) &&
        data.secondary_parent_email !== data.secondary_parent_email_confirm;

    // Auto-calculate age from date_of_birth.
    useEffect(() => {
        if (!data.date_of_birth) {
            return;
        }

        const dob = new Date(data.date_of_birth);

        if (Number.isNaN(dob.getTime())) {
            return;
        }

        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();

        if (
            monthDiff < 0 ||
            (monthDiff === 0 && today.getDate() < dob.getDate())
        ) {
            age--;
        }

        if (age !== data.age) {
            setData('age', age);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.date_of_birth]);

    // Reset fscd_info/insurance_info when funding_source no longer matches.
    useEffect(() => {
        if (!isFscdFunding && Object.values(data.fscd_info).some(Boolean)) {
            setData('fscd_info', DEFAULT_VALUES.fscd_info);
        }

        if (
            data.funding_source !== 'Insurance' &&
            Object.values(data.insurance_info).some(Boolean)
        ) {
            setData('insurance_info', DEFAULT_VALUES.insurance_info);
        }

        // Clinical Coordinator is only offered under SS-FSCD, so switching
        // away would otherwise post a service the applicant can no longer see.
        if (
            data.funding_source !== 'SS-FSCD' &&
            data.services_needed.includes(SS_ONLY_SERVICE)
        ) {
            setData(
                'services_needed',
                data.services_needed.filter(
                    (service) => service !== SS_ONLY_SERVICE,
                ),
            );
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.funding_source]);

    // Autosave a draft every minute (gated behind cookie consent).
    useEffect(() => {
        if (!cookieConsentAllowed()) {
            return;
        }

        const interval = setInterval(() => {
            localStorage.setItem(draftKey, JSON.stringify(data));
            setLastSaved(new Date().toLocaleTimeString());
        }, 60 * 1000);

        return () => clearInterval(interval);
    }, [data, draftKey]);

    // Offer to restore a saved draft on mount.
    useEffect(() => {
        if (!cookieConsentAllowed()) {
            return;
        }

        const draft = localStorage.getItem(draftKey);

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
            localStorage.removeItem(draftKey);
        }
        // Restore is a one-shot prompt on mount; `draftKey` is fixed for the
        // life of the form, so re-running on it would only re-open the modal.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleLoadDraft = () => {
        if (!savedDraft) {
            return;
        }

        setData((current) => ({
            ...current,
            ...savedDraft,
            // Locked fields come from the account, never the draft. A stale
            // draft value would be both un-editable and rejected on submit.
            ...(Object.fromEntries(
                lockedFields.map((field) => [field, current[field]]),
            ) as Partial<IntakeFormData>),
        }));
        setShowLoadDraftModal(false);
    };

    const handleDiscardDraft = () => {
        localStorage.removeItem(draftKey);
        setShowLoadDraftModal(false);
    };

    const saveProgress = () => {
        localStorage.setItem(draftKey, JSON.stringify(data));
        setLastSaved(new Date().toLocaleTimeString());
    };

    const populateContact = () => {
        if (
            !data.primary_parent_name &&
            !data.primary_parent_phone &&
            !data.primary_relationship_to_child
        ) {
            return;
        }

        setData((current) => ({
            ...current,
            emergency_contact_name: current.primary_parent_name,
            emergency_contact_phone: current.primary_parent_phone,
            emergency_contact_relationship:
                current.primary_relationship_to_child,
        }));
    };

    const toggleService = (
        field: 'services_needed' | 'diagnosis',
        value: string,
    ) => {
        const next = toggleArrayValue(data[field] as string[], value);

        setData((current) => ({
            ...current,
            [field]: next,
            // Un-checking "Other" must drop the free-text answer with it,
            // otherwise a stale description still posts.
            ...(field === 'diagnosis' && !next.includes('Other')
                ? { diagnosis_other: '' }
                : {}),
        }));
    };

    const toggleConsentDocument = (id: number, checked: boolean) => {
        setConsentStates((prev) => ({ ...prev, [id]: checked }));
    };

    const submitApplication = () => {
        const consentIds = Object.entries(consentStates)
            .filter(([, accepted]) => accepted)
            .map(([id]) => Number(id));

        transform((current) => ({
            ...current,
            terms_accepted: consentGivenTerms,
            privacy_accepted: consentGivenPrivacy,
            consent_ids: consentIds,
        }));

        post(submitUrl, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = (
                    page.props as { flash?: { reference_number?: string } }
                ).flash;
                setReferenceNumber(flash?.reference_number ?? null);
                localStorage.removeItem(draftKey);
                setPreviewOpen(false);
                setSubmittedOpen(true);
                reset();
                setConsentStates({});
                setConsentGivenTerms(false);
                setConsentGivenPrivacy(false);
                setConsentGivenFSCD1(false);
                setConsentGivenFSCD2(false);
                setConsentGivenFSCD3(false);
            },
            /*
             * Submission happens from the preview modal, so a rejected
             * application used to fail silently: the modal stayed open over a
             * field error the applicant never saw. Close it and take them to
             * the field that needs fixing.
             */
            onError: (validationErrors) => {
                setPreviewOpen(false);

                const [firstField] = Object.keys(validationErrors);

                if (!firstField) {
                    return;
                }

                toast.error(validationErrors[firstField]);

                requestAnimationFrame(() => {
                    document
                        .getElementById('intake-error-summary')
                        ?.scrollIntoView({
                            block: 'center',
                            behavior: 'smooth',
                        });
                });
            },
        });
    };

    const canSubmit =
        !processing &&
        allConsentsGiven &&
        consentGivenTerms &&
        consentGivenPrivacy &&
        !primaryEmailMismatch &&
        !secondaryEmailMismatch &&
        (!isFscdFunding ||
            (consentGivenFSCD1 && consentGivenFSCD2 && consentGivenFSCD3));

    return (
        <div>
            <div className="sticky top-[5%] my-6 w-full rounded-2xl bg-white p-4 shadow-2xl sm:my-10 sm:p-6">
                <div className="mb-4 flex items-center justify-between">
                    <p className="flex items-center gap-2 text-sm font-medium sm:text-base">
                        <CheckCircle2 className="h-4 w-4 text-muted-foreground sm:h-5 sm:w-5" />
                        <span className="font-bold text-primary">
                            {progress}%
                        </span>{' '}
                        Complete
                    </p>

                    {cookieConsentAllowed() && (
                        <Button
                            size="sm"
                            className="flex items-center gap-1 rounded-[10px] border border-primary text-primary hover:bg-primary hover:text-white"
                            variant="outline"
                            onClick={saveProgress}
                            type="button"
                        >
                            <Save className="h-3 w-3 sm:h-4 sm:w-4" /> Save for
                            Later
                        </Button>
                    )}
                </div>

                <div className="h-2 w-full overflow-hidden rounded-full bg-primary/20">
                    <div
                        className="h-2 rounded-full bg-primary transition-all duration-500"
                        style={{ width: `${progress}%` }}
                    />
                </div>

                {cookieConsentAllowed() && lastSaved && (
                    <p className="mt-3 flex items-center gap-2 text-xs text-gray-500 sm:text-sm">
                        <Save className="h-3 w-3 sm:h-4 sm:w-4" /> Last Saved:{' '}
                        {lastSaved}
                    </p>
                )}
            </div>

            <div id="intake-error-summary" className="my-6">
                <FormErrorSummary errors={errors} />
            </div>

            <div className="mb-6 rounded-2xl border border-primary-orange/40 bg-secondary-orange/5 p-4 sm:p-5">
                <p className="flex items-center gap-3 font-semibold text-foreground">
                    <Clock className="h-5 w-5 text-primary" />
                    We typically respond within 1-2 business days.
                </p>
                <p className="mt-1 ml-8 text-sm text-charcoal-gray sm:text-base">
                    Our intake coordinator will contact you to discuss next
                    steps.
                </p>
            </div>

            {/* Child's Information */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="applicant-info">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <User className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Child's Information
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <p className="mb-5 px-2 text-base leading-relaxed text-muted-foreground sm:px-0 sm:text-lg">
                            Please provide information about the child who will
                            be receiving services.
                        </p>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="flex flex-col">
                                <Label htmlFor="firstName">
                                    First Name{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="firstName"
                                    value={data.child_first_name}
                                    onChange={(e) =>
                                        setData(
                                            'child_first_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="John"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.child_first_name && (
                                    <p className="text-sm text-red-600">
                                        {errors.child_first_name}
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
                                    value={data.child_last_name}
                                    onChange={(e) =>
                                        setData(
                                            'child_last_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Doe"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.child_last_name && (
                                    <p className="text-sm text-red-600">
                                        {errors.child_last_name}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div className="flex flex-col">
                                <Label htmlFor="DoB">
                                    Date Of Birth{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="DoB"
                                    type="date"
                                    className="mt-2 rounded-[10px]"
                                    max={new Date().toISOString().split('T')[0]}
                                    value={data.date_of_birth}
                                    onChange={(e) =>
                                        setData('date_of_birth', e.target.value)
                                    }
                                />
                                {errors.date_of_birth && (
                                    <p className="text-sm text-red-600">
                                        {errors.date_of_birth}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="age">
                                    Age (Auto-Calculated)
                                </Label>
                                <Input
                                    id="age"
                                    className="mt-2 rounded-[10px]"
                                    disabled
                                    value={data.age}
                                />
                            </div>

                            <div>
                                <Label>
                                    Gender{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.gender}
                                    onValueChange={(value) =>
                                        setData('gender', value)
                                    }
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">
                                            Male
                                        </SelectItem>
                                        <SelectItem value="female">
                                            Female
                                        </SelectItem>
                                        <SelectItem value="other">
                                            Other
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.gender && (
                                    <p className="text-sm text-red-600">
                                        {errors.gender}
                                    </p>
                                )}
                            </div>
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
                            <div>
                                <Label>
                                    State / Province{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.state_province}
                                    onValueChange={(value) => {
                                        setData('state_province', value);
                                        setData('city', '');
                                    }}
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Province" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Provinces.map((p) => (
                                            <SelectItem key={p} value={p}>
                                                {p}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.state_province && (
                                    <p className="text-sm text-red-600">
                                        {errors.state_province}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>
                                    City <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.city}
                                    onValueChange={(value) =>
                                        setData('city', value)
                                    }
                                    disabled={!data.state_province}
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select City" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {data.state_province &&
                                            ProvinceCities[
                                                data.state_province
                                            ]?.map((city) => (
                                                <SelectItem
                                                    key={city}
                                                    value={city}
                                                >
                                                    {city}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                                {errors.city && (
                                    <p className="text-sm text-red-600">
                                        {errors.city}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="zip">
                                    Postal/Zip Code{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="zip"
                                    value={data.postal_code}
                                    onChange={(e) =>
                                        setData('postal_code', e.target.value)
                                    }
                                    placeholder="T2N 1N4"
                                    className="mt-2 rounded-[10px]"
                                    maxLength={7}
                                />
                                {errors.postal_code && (
                                    <p className="text-sm text-red-600">
                                        {errors.postal_code}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Education Background */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="education-background">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <GraduationCap className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Education Background
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-4 sm:p-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6">
                            <div className="flex flex-col">
                                <Label htmlFor="gradeLevel">Grade Level</Label>
                                <Input
                                    id="gradeLevel"
                                    value={data.grade_level}
                                    onChange={(e) =>
                                        setData('grade_level', e.target.value)
                                    }
                                    placeholder="e.g., Kindergarten, Grade 3, N/A"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.grade_level && (
                                    <p className="mt-1 text-xs text-red-600 sm:text-sm">
                                        {errors.grade_level}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="schoolName">School Name</Label>
                                <Input
                                    id="schoolName"
                                    value={data.school_name}
                                    onChange={(e) =>
                                        setData('school_name', e.target.value)
                                    }
                                    placeholder="School name or N/A"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.school_name && (
                                    <p className="mt-1 text-xs text-red-600 sm:text-sm">
                                        {errors.school_name}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Primary Parent/Guardian */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="primary-contact">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Users className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Primary Parent/Guardian
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        {isLocked('primary_parent_email') && (
                            <p className="mb-5 rounded-[10px] bg-secondary-orange/10 p-3 text-sm text-muted-foreground">
                                These details are taken from your account. To
                                change them, update your profile.
                            </p>
                        )}

                        <div className="flex flex-col">
                            <Label htmlFor="primaryFullName">
                                Full Name{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                id="primaryFullName"
                                value={data.primary_parent_name}
                                onChange={(e) =>
                                    setData(
                                        'primary_parent_name',
                                        e.target.value,
                                    )
                                }
                                placeholder="Full Name"
                                className="mt-2 rounded-[10px]"
                                disabled={isLocked('primary_parent_name')}
                            />
                            {errors.primary_parent_name && (
                                <p className="text-sm text-red-600">
                                    {errors.primary_parent_name}
                                </p>
                            )}
                        </div>

                        <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="flex flex-col">
                                <Label htmlFor="primaryPhone">
                                    Phone Number{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="primaryPhone"
                                    value={data.primary_parent_phone}
                                    onChange={(e) =>
                                        setData(
                                            'primary_parent_phone',
                                            e.target.value.replace(
                                                /[^0-9()+-\s]/g,
                                                '',
                                            ),
                                        )
                                    }
                                    placeholder="Phone Number"
                                    className="mt-2 rounded-[10px]"
                                    maxLength={12}
                                    disabled={isLocked('primary_parent_phone')}
                                />
                                {errors.primary_parent_phone && (
                                    <p className="text-sm text-red-600">
                                        {errors.primary_parent_phone}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="primaryEmail">
                                    Email Address{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="primaryEmail"
                                    value={data.primary_parent_email}
                                    onChange={(e) =>
                                        setData(
                                            'primary_parent_email',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Email"
                                    className="mt-2 rounded-[10px]"
                                    disabled={isLocked('primary_parent_email')}
                                />
                                {errors.primary_parent_email && (
                                    <p className="text-sm text-red-600">
                                        {errors.primary_parent_email}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="mt-5 flex flex-col">
                            <Label htmlFor="primaryConfirmEmail">
                                Confirm Email Address{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                id="primaryConfirmEmail"
                                value={data.primary_parent_email_confirm}
                                onChange={(e) =>
                                    setData(
                                        'primary_parent_email_confirm',
                                        e.target.value,
                                    )
                                }
                                placeholder="Confirm your email"
                                className="mt-2 rounded-[10px]"
                                disabled={isLocked(
                                    'primary_parent_email_confirm',
                                )}
                            />
                            {primaryEmailMismatch && (
                                <p className="text-sm text-red-600">
                                    Emails do not match
                                </p>
                            )}
                            {errors.primary_parent_email_confirm && (
                                <p className="text-sm text-red-600">
                                    {errors.primary_parent_email_confirm}
                                </p>
                            )}
                        </div>

                        <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <Label>
                                    Relationship to Child{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.primary_relationship_to_child}
                                    onValueChange={(value) =>
                                        setData(
                                            'primary_relationship_to_child',
                                            value,
                                        )
                                    }
                                    disabled={isLocked(
                                        'primary_relationship_to_child',
                                    )}
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Relationship" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="mother">
                                            Mother
                                        </SelectItem>
                                        <SelectItem value="father">
                                            Father
                                        </SelectItem>
                                        <SelectItem value="guardian">
                                            Legal Guardian
                                        </SelectItem>
                                        <SelectItem value="other">
                                            Other
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.primary_relationship_to_child && (
                                    <p className="text-sm text-red-600">
                                        {errors.primary_relationship_to_child}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>
                                    Preferred Contact Method{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.primary_contact_method}
                                    onValueChange={(value) =>
                                        setData('primary_contact_method', value)
                                    }
                                    disabled={isLocked(
                                        'primary_contact_method',
                                    )}
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Contact Method" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="phone">
                                            Phone
                                        </SelectItem>
                                        <SelectItem value="email">
                                            Email
                                        </SelectItem>
                                        <SelectItem value="text_message">
                                            Text Message
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.primary_contact_method && (
                                    <p className="text-sm text-red-600">
                                        {errors.primary_contact_method}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Secondary Parent/Guardian */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="secondary-contact">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <User className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Secondary Parent/Guardian (Optional)
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div className="flex flex-col">
                            <Label htmlFor="secondaryFullName">Full Name</Label>
                            <Input
                                id="secondaryFullName"
                                value={data.secondary_parent_name}
                                onChange={(e) =>
                                    setData(
                                        'secondary_parent_name',
                                        e.target.value,
                                    )
                                }
                                placeholder="Full Name"
                                className="mt-2 rounded-[10px]"
                            />
                            {errors.secondary_parent_name && (
                                <p className="text-sm text-red-600">
                                    {errors.secondary_parent_name}
                                </p>
                            )}
                        </div>

                        <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="flex flex-col">
                                <Label htmlFor="secondaryPhone">
                                    Phone Number
                                </Label>
                                <Input
                                    id="secondaryPhone"
                                    value={data.secondary_parent_phone}
                                    onChange={(e) =>
                                        setData(
                                            'secondary_parent_phone',
                                            e.target.value.replace(
                                                /[^0-9()+-\s]/g,
                                                '',
                                            ),
                                        )
                                    }
                                    placeholder="Phone Number"
                                    className="mt-2 rounded-[10px]"
                                    maxLength={12}
                                />
                                {errors.secondary_parent_phone && (
                                    <p className="text-sm text-red-600">
                                        {errors.secondary_parent_phone}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col">
                                <Label htmlFor="secondaryEmail">
                                    Email Address
                                </Label>
                                <Input
                                    id="secondaryEmail"
                                    value={data.secondary_parent_email}
                                    onChange={(e) =>
                                        setData((current) => ({
                                            ...current,
                                            secondary_parent_email:
                                                e.target.value,
                                            secondary_parent_email_confirm:
                                                e.target.value,
                                        }))
                                    }
                                    placeholder="Email"
                                    className="mt-2 rounded-[10px]"
                                />
                                {errors.secondary_parent_email && (
                                    <p className="text-sm text-red-600">
                                        {errors.secondary_parent_email}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <Label>Relationship to Child</Label>
                                <Select
                                    value={data.secondary_relationship_to_child}
                                    onValueChange={(value) =>
                                        setData(
                                            'secondary_relationship_to_child',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Relationship" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="mother">
                                            Mother
                                        </SelectItem>
                                        <SelectItem value="father">
                                            Father
                                        </SelectItem>
                                        <SelectItem value="guardian">
                                            Legal Guardian
                                        </SelectItem>
                                        <SelectItem value="other">
                                            Other
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.secondary_relationship_to_child && (
                                    <p className="text-sm text-red-600">
                                        {errors.secondary_relationship_to_child}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Preferred Contact Method</Label>
                                <Select
                                    value={data.secondary_contact_method}
                                    onValueChange={(value) =>
                                        setData(
                                            'secondary_contact_method',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Contact Method" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="phone">
                                            Phone
                                        </SelectItem>
                                        <SelectItem value="email">
                                            Email
                                        </SelectItem>
                                        <SelectItem value="text_message">
                                            Text Message
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.secondary_contact_method && (
                                    <p className="text-sm text-red-600">
                                        {errors.secondary_contact_method}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Emergency Contact */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="emergency-contact">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Phone className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Emergency Contact Information
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        {isLocked('emergency_contact_name') && (
                            <p className="mb-5 rounded-[10px] bg-secondary-orange/10 p-3 text-sm text-muted-foreground">
                                These details are taken from your account. To
                                change them, update your profile.
                            </p>
                        )}

                        <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-base leading-relaxed text-muted-foreground sm:text-lg">
                                Person to contact in case of emergency (can be
                                same as primary contact)
                            </p>
                            {/*
                                Hidden while locked — populateContact() writes
                                these fields programmatically, which `disabled`
                                inputs don't prevent, so the button would be a
                                way around the lock.
                            */}
                            {!isLocked('emergency_contact_name') && (
                                <div className="flex justify-start sm:justify-end">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="flex items-center gap-2 rounded-[10px] border border-primary text-primary"
                                        onClick={populateContact}
                                    >
                                        <Copy /> Copy Primary Contact
                                    </Button>
                                </div>
                            )}
                        </div>

                        <div className="flex flex-col">
                            <Label htmlFor="emergencyFullName">
                                Full Name{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                id="emergencyFullName"
                                disabled={isLocked('emergency_contact_name')}
                                value={data.emergency_contact_name}
                                onChange={(e) =>
                                    setData(
                                        'emergency_contact_name',
                                        e.target.value,
                                    )
                                }
                                placeholder="Full Name"
                                className="mt-2 rounded-[10px]"
                            />
                            {errors.emergency_contact_name && (
                                <p className="text-sm text-red-600">
                                    {errors.emergency_contact_name}
                                </p>
                            )}
                        </div>

                        <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="flex flex-col">
                                <Label htmlFor="emergencyPhone">
                                    Phone Number{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Input
                                    id="emergencyPhone"
                                    disabled={isLocked(
                                        'emergency_contact_phone',
                                    )}
                                    value={data.emergency_contact_phone}
                                    onChange={(e) =>
                                        setData(
                                            'emergency_contact_phone',
                                            e.target.value.replace(
                                                /[^0-9()+-\s]/g,
                                                '',
                                            ),
                                        )
                                    }
                                    placeholder="Phone Number"
                                    className="mt-2 rounded-[10px]"
                                    maxLength={12}
                                />
                                {errors.emergency_contact_phone && (
                                    <p className="text-sm text-red-600">
                                        {errors.emergency_contact_phone}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>
                                    Relationship to Child{' '}
                                    <span className="text-red-700">*</span>
                                </Label>
                                <Select
                                    value={data.emergency_contact_relationship}
                                    onValueChange={(value) =>
                                        setData(
                                            'emergency_contact_relationship',
                                            value,
                                        )
                                    }
                                    disabled={isLocked(
                                        'emergency_contact_relationship',
                                    )}
                                >
                                    <SelectTrigger className="mt-2 rounded-[10px]">
                                        <SelectValue placeholder="Select Relationship" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="mother">
                                            Mother
                                        </SelectItem>
                                        <SelectItem value="father">
                                            Father
                                        </SelectItem>
                                        <SelectItem value="guardian">
                                            Legal Guardian
                                        </SelectItem>
                                        <SelectItem value="other">
                                            Other
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.emergency_contact_relationship && (
                                    <p className="text-sm text-red-600">
                                        {errors.emergency_contact_relationship}
                                    </p>
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Medical and Development History */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="medical-history">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Hospital className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Medical and Development History
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div>
                            <Label>
                                Diagnosis (Select all that apply){' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <div className="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {medicalConditionsOptions.map((condition) => (
                                    <div
                                        key={condition}
                                        className="flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={data.diagnosis.includes(
                                                condition,
                                            )}
                                            onChange={() =>
                                                toggleService(
                                                    'diagnosis',
                                                    condition,
                                                )
                                            }
                                            id={condition}
                                            className="h-4 w-4 accent-primary"
                                        />
                                        <Label
                                            htmlFor={condition}
                                            className="text-sm sm:text-base"
                                        >
                                            {condition}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.diagnosis && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.diagnosis}
                                </p>
                            )}

                            {data.diagnosis.includes('Other') && (
                                <div className="mt-4">
                                    <Label htmlFor="diagnosisOther">
                                        Please specify the other diagnosis{' '}
                                        <span className="text-red-700">*</span>
                                    </Label>
                                    <Input
                                        id="diagnosisOther"
                                        value={data.diagnosis_other}
                                        onChange={(e) =>
                                            setData(
                                                'diagnosis_other',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g., Cerebral Palsy"
                                        className="mt-2 rounded-[10px]"
                                    />
                                    {errors.diagnosis_other && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.diagnosis_other}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="mt-6">
                            <Label>
                                Does the child have any medical conditions?
                            </Label>
                            <RadioGroup
                                value={
                                    data.has_medical_conditions ? 'yes' : 'no'
                                }
                                onValueChange={(v) =>
                                    setData(
                                        'has_medical_conditions',
                                        v === 'yes',
                                    )
                                }
                            >
                                <div className="mt-2 flex flex-row gap-6">
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="yes"
                                            id="medical-yes"
                                        />
                                        <Label htmlFor="medical-yes">Yes</Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="no"
                                            id="medical-no"
                                        />
                                        <Label htmlFor="medical-no">No</Label>
                                    </div>
                                </div>
                            </RadioGroup>
                        </div>

                        {data.has_medical_conditions && (
                            <div className="mt-4">
                                <Label>Please Describe</Label>
                                <textarea
                                    value={data.medical_conditions}
                                    onChange={(e) =>
                                        setData(
                                            'medical_conditions',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-2 w-full rounded-[10px] border px-2 py-1"
                                    placeholder="Please provide details about the medical conditions"
                                    rows={4}
                                />
                            </div>
                        )}

                        <div className="mt-6 flex flex-col">
                            <Label htmlFor="languagesSpoken">
                                What languages are spoken at home
                            </Label>
                            <Input
                                id="languagesSpoken"
                                value={data.languages_spoken_at_home}
                                onChange={(e) =>
                                    setData(
                                        'languages_spoken_at_home',
                                        e.target.value,
                                    )
                                }
                                placeholder="e.g., English, Spanish"
                                className="mt-2 rounded-[10px]"
                            />
                            {errors.languages_spoken_at_home && (
                                <p className="text-sm text-red-600">
                                    {errors.languages_spoken_at_home}
                                </p>
                            )}
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Funding Source */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="funding-source">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <CreditCard className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Funding Source
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div>
                            <Label>
                                How will the service be funded?{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Select
                                value={data.funding_source}
                                onValueChange={(value) =>
                                    setData('funding_source', value)
                                }
                            >
                                <SelectTrigger className="mt-2 rounded-[10px]">
                                    <SelectValue placeholder="Select a funding" />
                                </SelectTrigger>
                                <SelectContent>
                                    {/*
                                     * Driven by the shared label map so the
                                     * applicant, the admin detail page and the
                                     * client funding tab always word a funding
                                     * source identically. The id gives tests a
                                     * handle on the SS option, whose label's
                                     * parentheses parse as CSS selector tokens.
                                     */}
                                    {Object.entries(FUNDING_SOURCE_LABELS).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                                id={
                                                    value === 'SS-FSCD'
                                                        ? 'funding-ss'
                                                        : undefined
                                                }
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            {errors.funding_source && (
                                <p className="text-sm text-red-600">
                                    {errors.funding_source}
                                </p>
                            )}
                        </div>

                        {isFscdFunding && (
                            <div className="mt-6 rounded-[10px] bg-primary/20 p-4">
                                <p className="flex flex-row gap-2 text-lg text-primary">
                                    <InfoIcon /> FSCD Information
                                </p>

                                <div className="flex flex-col">
                                    <Label htmlFor="fscdWorkerName">
                                        FSCD Case Worker Name{' '}
                                        <span className="text-red-700">*</span>
                                    </Label>
                                    <Input
                                        id="fscdWorkerName"
                                        value={
                                            data.fscd_info.FSCD_case_worker_name
                                        }
                                        onChange={(e) =>
                                            setData('fscd_info', {
                                                ...data.fscd_info,
                                                FSCD_case_worker_name:
                                                    e.target.value,
                                            })
                                        }
                                        placeholder="John"
                                        className="mt-2 rounded-[10px]"
                                    />
                                    {errors[
                                        'fscd_info.FSCD_case_worker_name'
                                    ] && (
                                        <p className="text-sm text-red-600">
                                            {
                                                errors[
                                                    'fscd_info.FSCD_case_worker_name'
                                                ]
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="mt-4 flex flex-col">
                                    <Label htmlFor="fscdWorkerEmail">
                                        FSCD Case Worker Email{' '}
                                        <span className="text-red-700">*</span>
                                    </Label>
                                    <Input
                                        id="fscdWorkerEmail"
                                        value={
                                            data.fscd_info
                                                .FSCD_case_worker_email
                                        }
                                        onChange={(e) =>
                                            setData('fscd_info', {
                                                ...data.fscd_info,
                                                FSCD_case_worker_email:
                                                    e.target.value,
                                            })
                                        }
                                        placeholder="john@example.com"
                                        className="mt-2 rounded-[10px]"
                                    />
                                    {errors[
                                        'fscd_info.FSCD_case_worker_email'
                                    ] && (
                                        <p className="text-sm text-red-600">
                                            {
                                                errors[
                                                    'fscd_info.FSCD_case_worker_email'
                                                ]
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="mt-4 flex flex-col">
                                    <Label htmlFor="fscdContractDate">
                                        FSCD Contract Start Date{' '}
                                        <span className="text-red-700">*</span>
                                    </Label>
                                    <Input
                                        id="fscdContractDate"
                                        type="date"
                                        min={
                                            new Date()
                                                .toISOString()
                                                .split('T')[0]
                                        }
                                        value={
                                            data.fscd_info
                                                .FSCD_approval_start_date
                                        }
                                        onChange={(e) =>
                                            setData('fscd_info', {
                                                ...data.fscd_info,
                                                FSCD_approval_start_date:
                                                    e.target.value,
                                            })
                                        }
                                        className="mt-2 rounded-[10px]"
                                    />
                                    {errors[
                                        'fscd_info.FSCD_approval_start_date'
                                    ] && (
                                        <p className="text-sm text-red-600">
                                            {
                                                errors[
                                                    'fscd_info.FSCD_approval_start_date'
                                                ]
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="mt-10 space-y-5 border-t border-primary p-5">
                                    <p className="text-lg font-bold text-primary">
                                        Required FSCD Consents *{' '}
                                        <span className="text-sm font-light">
                                            Consent to all items is required to
                                            move forward
                                        </span>
                                    </p>

                                    <div className="grid grid-cols-1 gap-4">
                                        {fscdConsentTerms.map((terms) => {
                                            const state =
                                                fscdConsentState[terms.id];
                                            const hasRead = Boolean(
                                                fscdTermsRead[terms.id],
                                            );

                                            return (
                                                <div
                                                    key={terms.id}
                                                    className="flex items-start gap-3"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        id={terms.id}
                                                        checked={state.checked}
                                                        disabled={!hasRead}
                                                        className="mt-1 h-5 w-5 accent-primary disabled:cursor-not-allowed disabled:opacity-50"
                                                        onChange={(e) =>
                                                            state.setChecked(
                                                                e.target
                                                                    .checked,
                                                            )
                                                        }
                                                    />
                                                    <label
                                                        htmlFor={terms.id}
                                                        className={`text-sm sm:text-base ${hasRead ? '' : 'text-charcoal-gray'}`}
                                                    >
                                                        {terms.label} —{' '}
                                                        <button
                                                            type="button"
                                                            className="cursor-pointer text-primary underline"
                                                            onClick={() =>
                                                                setOpenFscdTermsId(
                                                                    terms.id,
                                                                )
                                                            }
                                                        >
                                                            Terms and Conditions
                                                        </button>
                                                        {!hasRead && (
                                                            <span className="mt-1 block text-xs text-charcoal-gray">
                                                                Read the terms
                                                                to the end to
                                                                enable this
                                                                consent.
                                                            </span>
                                                        )}
                                                    </label>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        )}

                        {data.funding_source === 'Insurance' && (
                            <div className="mt-6 rounded-[10px] bg-primary/20 p-4">
                                <p className="flex flex-row gap-2 text-lg text-primary">
                                    <InfoIcon /> Insurance Information
                                </p>

                                <div className="flex flex-col">
                                    <Label htmlFor="insuranceProvider">
                                        Insurance Provider{' '}
                                        <span className="text-red-700">*</span>
                                    </Label>
                                    <Input
                                        id="insuranceProvider"
                                        value={
                                            data.insurance_info
                                                .insurance_provider
                                        }
                                        onChange={(e) =>
                                            setData('insurance_info', {
                                                ...data.insurance_info,
                                                insurance_provider:
                                                    e.target.value,
                                            })
                                        }
                                        placeholder="Selected Insurance Provider"
                                        className="mt-2 rounded-[10px]"
                                    />
                                    {errors[
                                        'insurance_info.insurance_provider'
                                    ] && (
                                        <p className="text-sm text-red-600">
                                            {
                                                errors[
                                                    'insurance_info.insurance_provider'
                                                ]
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div className="flex flex-col">
                                        <Label htmlFor="policyNumber">
                                            Policy / Group Number{' '}
                                            <span className="text-red-700">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="policyNumber"
                                            value={
                                                data.insurance_info
                                                    .policy_number
                                            }
                                            onChange={(e) =>
                                                setData('insurance_info', {
                                                    ...data.insurance_info,
                                                    policy_number:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="Enter Policy or Group Number"
                                            className="mt-2 rounded-[10px]"
                                        />
                                        {errors[
                                            'insurance_info.policy_number'
                                        ] && (
                                            <p className="text-sm text-red-600">
                                                {
                                                    errors[
                                                        'insurance_info.policy_number'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex flex-col">
                                        <Label htmlFor="certificateNumber">
                                            Certificate / ID Number{' '}
                                            <span className="text-red-700">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="certificateNumber"
                                            value={
                                                data.insurance_info
                                                    .certificate_number
                                            }
                                            onChange={(e) =>
                                                setData('insurance_info', {
                                                    ...data.insurance_info,
                                                    certificate_number:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="Enter Certificate or ID number"
                                            className="mt-2 rounded-[10px]"
                                        />
                                        {errors[
                                            'insurance_info.certificate_number'
                                        ] && (
                                            <p className="text-sm text-red-600">
                                                {
                                                    errors[
                                                        'insurance_info.certificate_number'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div className="flex flex-col">
                                        <Label htmlFor="policyHolderName">
                                            Policy Holder Name{' '}
                                            <span className="text-red-700">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="policyHolderName"
                                            value={
                                                data.insurance_info
                                                    .policy_holder_name
                                            }
                                            onChange={(e) =>
                                                setData('insurance_info', {
                                                    ...data.insurance_info,
                                                    policy_holder_name:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="Full name of Policy holder"
                                            className="mt-2 rounded-[10px]"
                                        />
                                        {errors[
                                            'insurance_info.policy_holder_name'
                                        ] && (
                                            <p className="text-sm text-red-600">
                                                {
                                                    errors[
                                                        'insurance_info.policy_holder_name'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex flex-col">
                                        <Label htmlFor="policyDOB">
                                            Policy Holder Date of Birth{' '}
                                            <span className="text-red-700">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="policyDOB"
                                            type="date"
                                            max={
                                                new Date()
                                                    .toISOString()
                                                    .split('T')[0]
                                            }
                                            value={
                                                data.insurance_info
                                                    .policy_holder_date_of_birth
                                            }
                                            onChange={(e) =>
                                                setData('insurance_info', {
                                                    ...data.insurance_info,
                                                    policy_holder_date_of_birth:
                                                        e.target.value,
                                                })
                                            }
                                            className="mt-2 rounded-[10px]"
                                        />
                                        {errors[
                                            'insurance_info.policy_holder_date_of_birth'
                                        ] && (
                                            <p className="text-sm text-red-600">
                                                {
                                                    errors[
                                                        'insurance_info.policy_holder_date_of_birth'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-6">
                                    <Label>
                                        Is pre-authorization already obtained?{' '}
                                        <span className="text-red-700">*</span>
                                    </Label>
                                    <RadioGroup
                                        value={
                                            data.insurance_info
                                                .pre_authorization_obtained
                                        }
                                        onValueChange={(v) =>
                                            setData('insurance_info', {
                                                ...data.insurance_info,
                                                pre_authorization_obtained: v,
                                            })
                                        }
                                    >
                                        <div className="mt-2 flex flex-col gap-3">
                                            <div className="flex items-center gap-2">
                                                <RadioGroupItem
                                                    value="Yes, pre-authorization has been obtained"
                                                    id="authorization-yes"
                                                />
                                                <Label htmlFor="authorization-yes">
                                                    Yes, pre-authorization has
                                                    been obtained
                                                </Label>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <RadioGroupItem
                                                    value="No, I need help obtaining pre-authorization"
                                                    id="authorization-no"
                                                />
                                                <Label htmlFor="authorization-no">
                                                    No, I need help obtaining
                                                    pre-authorization
                                                </Label>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <RadioGroupItem
                                                    value="I'm not sure"
                                                    id="authorization-not-sure"
                                                />
                                                <Label htmlFor="authorization-not-sure">
                                                    I'm not sure
                                                </Label>
                                            </div>
                                        </div>
                                    </RadioGroup>
                                </div>

                                <div className="mt-4 flex flex-col">
                                    <Label htmlFor="maximumUsed">
                                        Annual maximum used so far (if known)
                                    </Label>
                                    <Input
                                        id="maximumUsed"
                                        value={
                                            data.insurance_info
                                                .used_annual_maximum
                                        }
                                        onChange={(e) =>
                                            setData('insurance_info', {
                                                ...data.insurance_info,
                                                used_annual_maximum:
                                                    e.target.value,
                                            })
                                        }
                                        placeholder="e.g., $500, $1000"
                                        className="mt-2 rounded-[10px]"
                                    />
                                    <p className="mt-2 text-center text-sm text-muted-foreground">
                                        Optional: This helps us plan your
                                        coverage. You can find this information
                                        on your insurance portal or recent
                                        claims.
                                    </p>
                                </div>

                                <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div className="flex flex-col">
                                        <Label htmlFor="policyStart">
                                            Authorization Start Date
                                        </Label>
                                        <Input
                                            id="policyStart"
                                            type="date"
                                            value={
                                                data.insurance_info
                                                    .authorization_start_date
                                            }
                                            onChange={(e) =>
                                                setData('insurance_info', {
                                                    ...data.insurance_info,
                                                    authorization_start_date:
                                                        e.target.value,
                                                })
                                            }
                                            className="mt-2 rounded-[10px]"
                                        />
                                    </div>

                                    <div className="flex flex-col">
                                        <Label htmlFor="policyEnd">
                                            Authorization End Date
                                        </Label>
                                        <Input
                                            id="policyEnd"
                                            type="date"
                                            value={
                                                data.insurance_info
                                                    .authorization_end_date
                                            }
                                            onChange={(e) =>
                                                setData('insurance_info', {
                                                    ...data.insurance_info,
                                                    authorization_end_date:
                                                        e.target.value,
                                                })
                                            }
                                            className="mt-2 rounded-[10px]"
                                        />
                                    </div>
                                </div>

                                <div className="mt-6 rounded-[10px] border border-blue-300 bg-blue-200 p-3">
                                    <p className="flex flex-row items-center gap-3 font-bold text-blue-900">
                                        <InfoIcon /> Note:
                                    </p>
                                    <p className="ml-9 text-blue-900">
                                        We will verify your insurance coverage
                                        and benefits after receiving your intake
                                        form. Some insurance providers require
                                        pre-authorization before services begin.
                                        We can help you with this process.
                                    </p>
                                </div>
                            </div>
                        )}
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Service Needed */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="service-needed">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <ServerCog className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Services Needed
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <p className="px-2 text-base leading-relaxed text-muted-foreground sm:px-0 sm:text-lg">
                            Select all services you are interested in for your
                            child.
                        </p>

                        <div className="mt-6">
                            <div className="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {intakeServicesFor(data.funding_source).map(
                                    (service) => (
                                        <div
                                            key={service}
                                            className="flex items-start gap-2"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.services_needed.includes(
                                                    service,
                                                )}
                                                onChange={() =>
                                                    toggleService(
                                                        'services_needed',
                                                        service,
                                                    )
                                                }
                                                id={service}
                                                className="mt-1 h-5 w-5 accent-primary"
                                            />
                                            <label
                                                htmlFor={service}
                                                className="text-sm sm:text-base"
                                            >
                                                {service}
                                            </label>
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Other Programs or Agencies */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="other-programs">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Building2 className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Other Programs or Agencies
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div className="mt-6">
                            <Label>
                                Is your child currently receiving services from
                                other programs or agencies?
                            </Label>
                            <RadioGroup
                                value={
                                    data.currently_receiving_services
                                        ? 'yes'
                                        : 'no'
                                }
                                onValueChange={(v) =>
                                    setData(
                                        'currently_receiving_services',
                                        v === 'yes',
                                    )
                                }
                            >
                                <div className="mt-2 flex flex-row gap-6">
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="yes"
                                            id="other-yes"
                                        />
                                        <Label htmlFor="other-yes">Yes</Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="no"
                                            id="other-no"
                                        />
                                        <Label htmlFor="other-no">No</Label>
                                    </div>
                                </div>
                            </RadioGroup>
                        </div>

                        {data.currently_receiving_services && (
                            <div className="mt-4">
                                <Label>Please list programs/agencies</Label>
                                <textarea
                                    value={data.receiving_services_desc}
                                    onChange={(e) =>
                                        setData(
                                            'receiving_services_desc',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-2 w-full rounded-[10px] border px-2 py-1"
                                    placeholder="Please list any other programs or agencies"
                                    rows={4}
                                />
                            </div>
                        )}
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Availability */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="availability">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Timer className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Availability
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <Label className="font-medium">
                            Availability <span className="text-red-700">*</span>
                        </Label>
                        <p className="mt-1 text-sm text-charcoal-gray">
                            Tick every time of day that works, for each day of
                            the week.
                        </p>

                        <AvailabilityGrid
                            slots={data.availability_slots}
                            onToggle={(day, time) =>
                                setData(
                                    'availability_slots',
                                    toggleAvailabilitySlot(
                                        data.availability_slots,
                                        day,
                                        time,
                                    ),
                                )
                            }
                        />

                        {errors.availability_slots && (
                            <p className="mt-2 text-sm text-red-600">
                                {errors.availability_slots}
                            </p>
                        )}
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* Additional Information */}
            <Accordion
                type="single"
                collapsible
                className="my-8 rounded-lg bg-white shadow-2xl sm:my-10"
            >
                <AccordionItem value="additional-info">
                    <AccordionTrigger className="flex items-center gap-3 rounded-t-lg bg-gradient-to-br from-secondary-orange/10 to-transparent p-4 transition hover:bg-secondary-orange/20 sm:p-5">
                        <div className="flex flex-row items-center gap-3">
                            <div className="flex-shrink-0 rounded-md bg-primary p-3 sm:p-4">
                                <Building2 className="h-4 w-4 text-white sm:h-5 sm:w-5" />
                            </div>
                            <p className="text-sm font-medium text-primary sm:text-base">
                                Additional Information
                            </p>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="p-5">
                        <div className="mt-4">
                            <Label>Additional Information</Label>
                            <textarea
                                value={data.additional_information}
                                onChange={(e) =>
                                    setData(
                                        'additional_information',
                                        e.target.value,
                                    )
                                }
                                className="mt-2 w-full rounded-[10px] border px-2 py-1"
                                placeholder="Please share any additional information that might help us serve your child better"
                                rows={4}
                            />
                        </div>

                        <div>
                            <Label>
                                How did you hear about us?{' '}
                                <span className="text-red-700">*</span>
                            </Label>
                            <Select
                                value={data.referral_source}
                                onValueChange={(value) =>
                                    setData('referral_source', value)
                                }
                            >
                                <SelectTrigger className="mt-2 rounded-[10px]">
                                    <SelectValue placeholder="Select a source" />
                                </SelectTrigger>
                                <SelectContent>
                                    {leadSource.map((l) => (
                                        <SelectItem key={l} value={l}>
                                            {l}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.referral_source && (
                                <p className="text-sm text-red-600">
                                    {errors.referral_source}
                                </p>
                            )}
                        </div>

                        {data.referral_source === 'Other' && (
                            <div className="mt-4">
                                <Label>Please specify</Label>
                                <textarea
                                    value={data.referral_source_other}
                                    onChange={(e) =>
                                        setData(
                                            'referral_source_other',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-2 w-full rounded-[10px] border px-2 py-1"
                                    placeholder="Please add your referral"
                                    rows={2}
                                />
                            </div>
                        )}
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            {/* What's Next */}
            <div className="rounded-sm border-2 border-primary shadow-2xl">
                <div className="bg-gradient-to-br from-secondary-orange/15 to-transparent p-4 sm:p-6">
                    <p className="flex flex-row items-center gap-3 text-lg font-medium text-primary sm:gap-5 sm:text-xl">
                        <Calendar className="h-5 w-5 sm:h-6 sm:w-6" />
                        What Happens Next?
                    </p>
                    <p className="mt-3 text-sm text-charcoal-gray sm:mt-4 sm:text-base">
                        Here's what you can expect after submitting this intake
                        form
                    </p>
                </div>

                <div className="mt-6 grid grid-cols-1 gap-6 p-5 sm:grid-cols-2 sm:gap-8 sm:p-10">
                    {[
                        {
                            title: 'Step 1: Review',
                            desc: 'Our team reviews your intake within 1-2 business days',
                            icon: Clock,
                        },
                        {
                            title: 'Step 2: Contact',
                            desc: 'An Intake coordinator will call or email you to discuss your needs',
                            icon: Phone,
                        },
                        {
                            title: 'Step 3: Assessment',
                            desc: 'We schedule an initial assessment with your child',
                            icon: Calendar,
                        },
                        {
                            title: 'Step 4: Match',
                            desc: 'We match you with the perfect therapist and begin services',
                            icon: CheckCircle2,
                        },
                    ].map((step) => (
                        <div
                            key={step.title}
                            className="flex flex-row items-start gap-4 sm:items-center sm:gap-5"
                        >
                            <div className="flex-shrink-0 rounded-full bg-primary p-3 text-white sm:p-4">
                                <step.icon />
                            </div>
                            <div>
                                <p className="font-medium">{step.title}</p>
                                <p className="text-sm text-charcoal-gray sm:text-base">
                                    {step.desc}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Consent & Submit */}
            <div className="mt-6 rounded-sm bg-white pb-10 shadow-2xl">
                <div className="flex flex-col items-start gap-5 p-6 sm:flex-row sm:items-center sm:p-10">
                    <div className="flex-shrink-0">
                        <Lock className="h-6 w-6 text-primary" />
                    </div>
                    <p className="text-sm text-charcoal-gray">
                        By submitting this form, you consent to Creative
                        Abilities Therapy Services using this information to
                        match your child with appropriate services. All
                        information is kept strictly confidential and secure.
                    </p>
                </div>

                <div className="m-4 flex flex-col gap-6 rounded-sm border border-gray-300 p-6 shadow-lg sm:m-10 sm:p-10">
                    <p className="flex flex-row items-center gap-3 text-lg font-semibold text-primary">
                        <CheckCheck className="h-5 w-5" />
                        Required Consents
                    </p>

                    <div className="flex items-start gap-3">
                        <input
                            type="checkbox"
                            id="consentTerms"
                            checked={consentGivenTerms}
                            className="mt-1 h-5 w-5 accent-primary"
                            onChange={(e) =>
                                setConsentGivenTerms(e.target.checked)
                            }
                        />
                        <label htmlFor="consentTerms" className="flex-1">
                            <p>
                                I have read and agree to the{' '}
                                <a
                                    href="/termsandconditions"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary underline"
                                >
                                    Terms and Conditions
                                </a>
                            </p>
                            <p className="text-sm text-charcoal-gray">
                                Please review and accept the terms and
                                conditions
                            </p>
                        </label>
                    </div>

                    <div className="flex items-start gap-3">
                        <input
                            type="checkbox"
                            id="consentPrivacy"
                            checked={consentGivenPrivacy}
                            className="mt-1 h-5 w-5 accent-primary"
                            onChange={(e) =>
                                setConsentGivenPrivacy(e.target.checked)
                            }
                        />
                        <label htmlFor="consentPrivacy" className="flex-1">
                            <p>
                                I have read and agree to the{' '}
                                <a
                                    href="/privacypolicy"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary underline"
                                >
                                    Privacy Policy
                                </a>
                            </p>
                            <p className="text-sm text-charcoal-gray">
                                Please review and accept the privacy policy to
                                continue
                            </p>
                        </label>
                    </div>

                    {requiredConsents.map((c) => (
                        <div className="flex items-start gap-3" key={c.id}>
                            <input
                                type="checkbox"
                                id={`consent-${c.id}`}
                                checked={!!consentStates[c.id]}
                                className="mt-1 h-5 w-5 accent-primary"
                                onChange={(e) =>
                                    toggleConsentDocument(
                                        c.id,
                                        e.target.checked,
                                    )
                                }
                            />
                            <label
                                htmlFor={`consent-${c.id}`}
                                className="flex-1"
                            >
                                <p>
                                    I have read and agree to the{' '}
                                    <a
                                        className="cursor-pointer text-primary underline"
                                        onClick={() => {
                                            setSelectedConsent(c);
                                            setConsentDialogOpen(true);
                                        }}
                                    >
                                        {c.title}
                                    </a>{' '}
                                    Consent
                                </p>
                                <p className="text-sm text-charcoal-gray">
                                    Please review and accept the privacy policy
                                    to continue
                                </p>
                            </label>
                        </div>
                    ))}
                </div>

                <div className="flex flex-col items-center gap-3 px-6 sm:px-10">
                    <Button
                        className="flex w-full items-center justify-center gap-2 rounded-[10px] border-2 border-primary py-4 text-lg text-white sm:py-5 sm:text-xl"
                        disabled={!canSubmit}
                        type="button"
                        onClick={() => {
                            if (missingFields.length > 0) {
                                setMissingDialogOpen(true);
                            } else {
                                setPreviewOpen(true);
                            }
                        }}
                    >
                        {processing ? (
                            'Submitting...'
                        ) : (
                            <>
                                <Eye className="h-5 w-5" /> Preview & Submit
                                Intake Form
                            </>
                        )}
                    </Button>

                    <p className="mt-2 text-center text-sm text-muted-foreground">
                        Please accept both consents to enable submission
                    </p>
                </div>
            </div>

            {selectedConsent && (
                <ConsentModal
                    consent={selectedConsent}
                    open={consentDialogOpen}
                    onOpenChange={setConsentDialogOpen}
                    replacements={{
                        primaryParentlegalName: data.primary_parent_name,
                        childsFullName: `${data.child_first_name} ${data.child_last_name}`,
                        childsDateOfBirth: data.date_of_birth,
                    }}
                />
            )}

            {openFscdTerms && (
                <FscdConsentTermsModal
                    key={openFscdTerms.id}
                    terms={openFscdTerms}
                    open
                    onOpenChange={(open) =>
                        setOpenFscdTermsId(open ? openFscdTerms.id : null)
                    }
                    alreadyRead={Boolean(fscdTermsRead[openFscdTerms.id])}
                    onRead={() =>
                        setFscdTermsRead((previous) =>
                            previous[openFscdTerms.id]
                                ? previous
                                : { ...previous, [openFscdTerms.id]: true },
                        )
                    }
                />
            )}

            <IntakePreviewModal
                isOpen={previewOpen}
                onClose={() => setPreviewOpen(false)}
                applicationData={data}
                submitApplication={submitApplication}
            />

            <IntakeSubmittedModal
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
                        <DialogTitle>
                            Unfinished Intake Application Found
                        </DialogTitle>
                    </DialogHeader>
                    <div className="my-4">
                        You have a saved draft of an Intake application. Do you
                        want to load it?
                    </div>
                    <DialogFooter className="flex justify-end gap-4">
                        <Button variant="outline" onClick={handleDiscardDraft}>
                            Discard
                        </Button>
                        <Button onClick={handleLoadDraft}>Load Draft</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
