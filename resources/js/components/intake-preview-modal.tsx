import { Calendar, Check, Luggage, User } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface FscdInfoPreview {
    FSCD_case_worker_name?: string;
    FSCD_case_worker_email?: string;
    FSCD_approval_start_date?: string;
}

interface InsuranceInfoPreview {
    insurance_provider?: string;
    policy_number?: string;
    certificate_number?: string;
    policy_holder_name?: string;
    policy_holder_date_of_birth?: string;
    pre_authorization_obtained?: string;
    used_annual_maximum?: string;
    authorization_start_date?: string;
    authorization_end_date?: string;
}

export interface IntakePreviewData {
    child_first_name: string;
    child_middle_name?: string;
    child_last_name: string;
    date_of_birth: string;
    age: number;
    gender?: string;
    street_address: string;
    address_line_2?: string;
    city: string;
    state_province: string;
    postal_code: string;
    grade_level?: string;
    school_name?: string;
    primary_parent_name: string;
    primary_relationship_to_child: string;
    primary_parent_phone: string;
    primary_parent_email: string;
    primary_contact_method: string;
    secondary_parent_name?: string;
    secondary_relationship_to_child?: string;
    secondary_parent_phone?: string;
    secondary_parent_email?: string;
    secondary_contact_method?: string;
    emergency_contact_name: string;
    emergency_contact_relationship: string;
    emergency_contact_phone: string;
    diagnosis: string[];
    has_medical_conditions: boolean;
    medical_conditions?: string;
    languages_spoken_at_home?: string;
    funding_source: string;
    fscd_info?: FscdInfoPreview;
    insurance_info?: InsuranceInfoPreview;
    services_needed: string[];
    currently_receiving_services: boolean;
    receiving_services_desc?: string;
    available_days: string[];
    preferred_times: string[];
    additional_information?: string;
    referral_source: string;
}

interface IntakePreviewModalProps {
    isOpen: boolean;
    onClose: () => void;
    applicationData: IntakePreviewData;
    submitApplication?: () => void;
}

/**
 * Review-before-submit summary, ported from
 * cats-frontend/src/modals/IntakePreviewModal.tsx.
 */
export default function IntakePreviewModal({
    isOpen,
    onClose,
    applicationData,
    submitApplication,
}: IntakePreviewModalProps) {
    const isFscd =
        applicationData.funding_source !== 'Insurance' &&
        applicationData.funding_source !== 'private';

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] w-full max-w-3xl overflow-y-auto p-6">
                <DialogHeader>
                    <DialogTitle>Review Your Submission</DialogTitle>
                </DialogHeader>

                <div className="mt-5 flex flex-col gap-6">
                    {/* Child's Information */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <User />
                            <p className="font-semibold">Child's Information</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Full Name
                                </h3>
                                <p className="font-medium">
                                    {[
                                        applicationData.child_first_name,
                                        applicationData.child_middle_name,
                                        applicationData.child_last_name,
                                    ]
                                        .filter(Boolean)
                                        .join(' ') || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Date of Birth
                                </h3>
                                <p className="font-medium">
                                    {applicationData.date_of_birth || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Age
                                </h3>
                                <p className="font-medium break-words">
                                    {applicationData.age || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Gender
                                </h3>
                                <p className="font-medium break-words">
                                    {applicationData.gender || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Address
                                </h3>
                                <p className="font-medium break-words">
                                    {[
                                        applicationData.street_address,
                                        applicationData.address_line_2,
                                        applicationData.city,
                                        applicationData.state_province,
                                        applicationData.postal_code,
                                    ]
                                        .filter(Boolean)
                                        .join(', ') || '—'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Education Details */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Luggage />
                            <p className="font-semibold">Education Details</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Grade:
                                </h3>
                                <p className="font-medium">
                                    {applicationData.grade_level || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    School
                                </h3>
                                <p className="font-medium">
                                    {applicationData.school_name || '—'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Primary Contact */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Luggage />
                            <p className="font-semibold">Primary Contact</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Name:
                                </h3>
                                <p className="font-medium">
                                    {applicationData.primary_parent_name || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Relationship
                                </h3>
                                <p className="font-medium">
                                    {applicationData.primary_relationship_to_child ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Phone
                                </h3>
                                <p className="font-medium">
                                    {applicationData.primary_parent_phone ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Email
                                </h3>
                                <p className="font-medium">
                                    {applicationData.primary_parent_email ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Preferred Contact
                                </h3>
                                <p className="font-medium">
                                    {applicationData.primary_contact_method ||
                                        '—'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Secondary Contact */}
                    {applicationData.secondary_parent_name && (
                        <div className="rounded-sm bg-secondary-orange/5 p-5">
                            <div className="flex items-center gap-3 text-primary">
                                <Luggage />
                                <p className="font-semibold">
                                    Secondary Contact
                                </p>
                            </div>
                            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        Name:
                                    </h3>
                                    <p className="font-medium">
                                        {applicationData.secondary_parent_name ||
                                            '—'}
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        Relationship
                                    </h3>
                                    <p className="font-medium">
                                        {applicationData.secondary_relationship_to_child ||
                                            '—'}
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        Phone
                                    </h3>
                                    <p className="font-medium">
                                        {applicationData.secondary_parent_phone ||
                                            '—'}
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        Email
                                    </h3>
                                    <p className="font-medium">
                                        {applicationData.secondary_parent_email ||
                                            '—'}
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        Preferred Contact
                                    </h3>
                                    <p className="font-medium">
                                        {applicationData.secondary_contact_method ||
                                            '—'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Emergency Contact */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Luggage />
                            <p className="font-semibold">Emergency Contact</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Name:
                                </h3>
                                <p className="font-medium">
                                    {applicationData.emergency_contact_name ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Relationship
                                </h3>
                                <p className="font-medium">
                                    {applicationData.emergency_contact_relationship ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Phone
                                </h3>
                                <p className="font-medium">
                                    {applicationData.emergency_contact_phone ||
                                        '—'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Medical & Development History */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Calendar />
                            <p className="font-semibold">
                                Medical &amp; Development History
                            </p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Diagnosis
                                </h3>
                                <p className="font-medium">
                                    {applicationData.diagnosis?.length
                                        ? applicationData.diagnosis.join(', ')
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Has Medical Condition?
                                </h3>
                                <p className="font-medium">
                                    {applicationData.has_medical_conditions
                                        ? 'Yes'
                                        : 'No'}
                                </p>
                            </div>
                            {applicationData.has_medical_conditions && (
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        Medical Condition
                                    </h3>
                                    <p className="font-medium">
                                        {applicationData.medical_conditions}
                                    </p>
                                </div>
                            )}
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Language spoken at home
                                </h3>
                                <p className="font-medium">
                                    {applicationData.languages_spoken_at_home}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Funding */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Calendar />
                            <p className="font-semibold">Funding</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Selected Funding
                                </h3>
                                <p className="font-medium">
                                    {applicationData.funding_source || '—'}
                                </p>
                            </div>

                            {isFscd && (
                                <>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            FSCD Case Worker Name
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.fscd_info
                                                    ?.FSCD_case_worker_name
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            FSCD Case Worker Email
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.fscd_info
                                                    ?.FSCD_case_worker_email
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            FSCD Contract Start Date
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.fscd_info
                                                    ?.FSCD_approval_start_date
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            FSCD Consents
                                        </h3>
                                        <p className="flex flex-row items-center gap-2 font-medium">
                                            <Check className="h-4 w-4 text-green-600" />{' '}
                                            FSCD worker Communication Consent
                                        </p>
                                        <p className="flex flex-row items-center gap-2 font-medium">
                                            <Check className="h-4 w-4 text-green-600" />{' '}
                                            Reports sharing consent
                                        </p>
                                        <p className="flex flex-row items-center gap-2 font-medium">
                                            <Check className="h-4 w-4 text-green-600" />{' '}
                                            Non-approved cost acknowledgement
                                            consent
                                        </p>
                                    </div>
                                </>
                            )}

                            {applicationData.funding_source === 'Insurance' && (
                                <>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Insurance Provider
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.insurance_info
                                                    ?.insurance_provider
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Policy / Group Number
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.insurance_info
                                                    ?.policy_number
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Certificate / ID Number
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.insurance_info
                                                    ?.certificate_number
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Policy holder name
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.insurance_info
                                                    ?.policy_holder_name
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Policy Holder Date of Birth
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.insurance_info
                                                    ?.policy_holder_date_of_birth
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Pre-authorization
                                        </h3>
                                        <p className="font-medium">
                                            {
                                                applicationData.insurance_info
                                                    ?.pre_authorization_obtained
                                            }
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Used Annual maximum
                                        </h3>
                                        <p className="font-medium">
                                            {applicationData.insurance_info
                                                ?.used_annual_maximum || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Authorization Start Date
                                        </h3>
                                        <p className="font-medium">
                                            {applicationData.insurance_info
                                                ?.authorization_start_date ||
                                                '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm text-muted-foreground">
                                            Authorization End Date
                                        </h3>
                                        <p className="font-medium">
                                            {applicationData.insurance_info
                                                ?.authorization_end_date || '-'}
                                        </p>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Additional Information */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Calendar />
                            <p className="font-semibold">
                                Additional Informations
                            </p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Services Needed
                                </h3>
                                <p className="font-medium">
                                    {applicationData.services_needed?.length
                                        ? applicationData.services_needed.join(
                                              ', ',
                                          )
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Receiving services from other programs?
                                </h3>
                                <p className="font-medium">
                                    {applicationData.currently_receiving_services
                                        ? 'Yes'
                                        : 'No'}
                                </p>
                            </div>
                            {applicationData.currently_receiving_services && (
                                <div>
                                    <h3 className="text-sm text-muted-foreground">
                                        List of Programs/Services
                                    </h3>
                                    <p className="font-medium">
                                        {
                                            applicationData.receiving_services_desc
                                        }
                                    </p>
                                </div>
                            )}
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Availability (day)
                                </h3>
                                <p className="font-medium">
                                    {applicationData.available_days?.length
                                        ? applicationData.available_days.join(
                                              ', ',
                                          )
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Availability (times)
                                </h3>
                                <p className="font-medium">
                                    {applicationData.preferred_times?.length
                                        ? applicationData.preferred_times.join(
                                              ', ',
                                          )
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Additional Info
                                </h3>
                                <p className="font-medium">
                                    {applicationData.additional_information
                                        ? 'Yes'
                                        : 'No'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Referral Source
                                </h3>
                                <p className="font-medium">
                                    {applicationData.referral_source}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter className="mt-4 flex gap-4">
                    <Button
                        variant="outline"
                        className="flex-1"
                        onClick={onClose}
                    >
                        Close
                    </Button>
                    <Button
                        className="flex-1 bg-primary text-white"
                        onClick={submitApplication}
                    >
                        Confirm and Submit
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
