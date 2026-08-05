import { Calendar, Luggage, Plus, User, UserCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    EDUCATION_OPTIONS,
    LEAD_SOURCE_OPTIONS,
} from '@/lib/content/careers-config';

export interface AvailabilityPreviewEntry {
    week_day: string;
    time_from: string;
    time_to: string;
}

export interface ReferencePreviewEntry {
    full_name: string;
    position: string;
    work: string;
    email: string;
    phone: string;
}

export interface ApplicationPreviewData {
    first_name: string;
    middle_name: string;
    last_name: string;
    phone: string;
    email: string;
    street_address: string;
    address_line_2: string;
    city: string;
    province: string;
    zip_code: string;
    position_applied: string;
    profession_status: string;
    preferred_start_date: string;
    is_working_with_other: boolean;
    availability: AvailabilityPreviewEntry[];
    resume_file: File | null;
    cover_letter_file: File | null;
    drivers_license: boolean;
    has_vehicle: boolean;
    education: string;
    experience: string;
    skills: string[];
    references: ReferencePreviewEntry[];
    expected_salary: string;
    lead_source: string;
    reason_for_applying: string;
    other_notes: string;
}

interface ApplicationPreviewModalProps {
    isOpen: boolean;
    onClose: () => void;
    applicationData: ApplicationPreviewData;
    isPreview?: boolean;
    submitApplication?: () => void;
}

function educationLabel(key: string): string {
    return EDUCATION_OPTIONS.find((option) => option.key === key)?.label ?? key;
}

function leadSourceLabel(key: string): string {
    return (
        LEAD_SOURCE_OPTIONS.find((option) => option.key === key)?.label ?? key
    );
}

/**
 * Read-only summary of the in-progress application, ported from
 * cats-frontend/src/modals/ApplicationPreviewModal.tsx. Used both for the
 * "Preview Application" action (isPreview=true, no submit button) and as
 * the final confirmation step before the real submit (isPreview=false).
 */
export default function ApplicationPreviewModal({
    isOpen,
    onClose,
    applicationData,
    isPreview = false,
    submitApplication,
}: ApplicationPreviewModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] w-full max-w-3xl overflow-y-auto p-6">
                <DialogHeader className="sticky top-0 z-50 border-b border-border bg-white">
                    <DialogTitle>Review Your Application</DialogTitle>
                </DialogHeader>

                <div className="mt-5 flex flex-col gap-6">
                    {/* Applicant Information */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <User />
                            <p className="font-semibold">
                                Applicant Information
                            </p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Full Name
                                </h3>
                                <p className="font-medium">
                                    {[
                                        applicationData.first_name,
                                        applicationData.middle_name,
                                        applicationData.last_name,
                                    ]
                                        .filter(Boolean)
                                        .join(' ') || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Phone
                                </h3>
                                <p className="font-medium">
                                    {applicationData.phone || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Email
                                </h3>
                                <p className="font-medium break-words">
                                    {applicationData.email || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Location
                                </h3>
                                <p className="font-medium break-words">
                                    {[
                                        applicationData.street_address,
                                        applicationData.address_line_2,
                                        applicationData.city,
                                        applicationData.province,
                                        applicationData.zip_code,
                                    ]
                                        .filter(Boolean)
                                        .join(', ') || '—'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Position Details */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Luggage />
                            <p className="font-semibold">Position Details</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Position
                                </h3>
                                <p className="font-medium">
                                    {applicationData.position_applied || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Profession Status
                                </h3>
                                <p className="font-medium">
                                    {applicationData.profession_status || '—'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Availability */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Calendar />
                            <p className="font-semibold">Availability</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Preferred Start Date
                                </h3>
                                <p className="font-medium">
                                    {applicationData.preferred_start_date ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Working with Other Company?
                                </h3>
                                <p className="font-medium">
                                    {applicationData.is_working_with_other
                                        ? 'Yes'
                                        : 'No'}
                                </p>
                            </div>
                            <div className="col-span-full">
                                <h3 className="text-sm text-muted-foreground">
                                    Availability
                                </h3>
                                <div className="font-medium">
                                    {applicationData.availability?.length >
                                    0 ? (
                                        applicationData.availability
                                            .filter(
                                                (slot) =>
                                                    slot.time_from ||
                                                    slot.time_to,
                                            )
                                            .map((slot) => (
                                                <p key={slot.week_day}>
                                                    {slot.week_day}:{' '}
                                                    {slot.time_from || '—'} -{' '}
                                                    {slot.time_to || '—'}
                                                </p>
                                            ))
                                    ) : (
                                        <p>—</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Personal Information */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <UserCheck />
                            <p className="font-semibold">
                                Personal Information
                            </p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Education
                                </h3>
                                <p className="font-medium">
                                    {educationLabel(
                                        applicationData.education,
                                    ) || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Experience
                                </h3>
                                <p className="font-medium">
                                    {applicationData.experience || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Skills
                                </h3>
                                <p className="font-medium">
                                    {applicationData.skills?.length
                                        ? applicationData.skills.join(', ')
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Resume
                                </h3>
                                <p className="font-medium">
                                    {applicationData.resume_file?.name || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Cover Letter
                                </h3>
                                <p className="font-medium">
                                    {applicationData.cover_letter_file?.name ||
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Drivers License
                                </h3>
                                <p className="font-medium">
                                    {applicationData.drivers_license
                                        ? 'Yes'
                                        : 'No'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Vehicle Access
                                </h3>
                                <p className="font-medium">
                                    {applicationData.has_vehicle ? 'Yes' : 'No'}
                                </p>
                            </div>
                            <div className="col-span-full">
                                <h3 className="text-sm text-muted-foreground">
                                    References
                                </h3>
                                {applicationData.references?.length > 0 ? (
                                    <div className="space-y-2 font-medium">
                                        {applicationData.references.map(
                                            (ref, idx) => (
                                                <div key={idx}>
                                                    <p>
                                                        <strong>Name:</strong>{' '}
                                                        {ref.full_name || '—'}
                                                    </p>
                                                    <p>
                                                        <strong>
                                                            Position:
                                                        </strong>{' '}
                                                        {ref.position || '—'}
                                                    </p>
                                                    <p>
                                                        <strong>Work:</strong>{' '}
                                                        {ref.work || '—'}
                                                    </p>
                                                    <p>
                                                        <strong>Email:</strong>{' '}
                                                        {ref.email || '—'}
                                                    </p>
                                                    <p>
                                                        <strong>Phone:</strong>{' '}
                                                        {ref.phone || '—'}
                                                    </p>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="font-medium">—</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Additional Information */}
                    <div className="rounded-sm bg-secondary-orange/5 p-5">
                        <div className="flex items-center gap-3 text-primary">
                            <Plus />
                            <p className="font-semibold">Additional Info</p>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Expected Salary
                                </h3>
                                <p className="font-medium">
                                    {applicationData.expected_salary || '—'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm text-muted-foreground">
                                    Source
                                </h3>
                                <p className="font-medium">
                                    {leadSourceLabel(
                                        applicationData.lead_source,
                                    ) || '—'}
                                </p>
                            </div>
                            <div className="col-span-full">
                                <h3 className="text-sm text-muted-foreground">
                                    Why Creative Abilities Therapy Services
                                </h3>
                                <p className="font-medium">
                                    {applicationData.reason_for_applying || '—'}
                                </p>
                            </div>
                            <div className="col-span-full">
                                <h3 className="text-sm text-muted-foreground">
                                    Other Comments
                                </h3>
                                <p className="font-medium">
                                    {applicationData.other_notes || '—'}
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
                    {!isPreview && (
                        <Button
                            className="flex-1 bg-primary text-white"
                            onClick={() => {
                                onClose();
                                submitApplication?.();
                            }}
                        >
                            Confirm and Submit
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
