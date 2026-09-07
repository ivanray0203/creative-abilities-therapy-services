import { Head, useForm } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';

import ChangePasswordCard from '@/components/admin/administrator/change-password-card';
import { EmploymentStatusBadge } from '@/components/admin/team-member/badges';
import DocumentsTab from '@/components/admin/team-member/documents-tab';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TherapistLayout from '@/layouts/therapist-layout';
import { WEEK_DAYS } from '@/lib/content/careers-config';
import type { TeamMember, TeamMemberDocument } from '@/types/team-member';

interface ProfileProps {
    teamMember: TeamMember;
    documents: TeamMemberDocument[];
    missingDocuments: string[];
}

interface ProfileForm {
    phone: string;
    office_phone: string;
    secondary_email: string;
    street_address: string;
    address_line_2: string;
    city: string;
    province: string;
    zip_code: string;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    availability: { week_day: string; time_from: string; time_to: string }[];
}

/**
 * Therapist self-service profile — reference: cats-frontend's
 * `team-members/me` view. Employment fields (department/hire date/hourly
 * rate/max caseload) are read-only for self, matching the backend guard.
 */
export default function TherapistProfile({
    teamMember,
    documents,
    missingDocuments,
}: ProfileProps) {
    const { data, setData, put, processing, errors } = useForm<ProfileForm>({
        phone: teamMember.phone ?? '',
        office_phone: teamMember.office_phone ?? '',
        secondary_email: teamMember.secondary_email ?? '',
        street_address: teamMember.street_address ?? '',
        address_line_2: teamMember.address_line_2 ?? '',
        city: teamMember.city ?? '',
        province: teamMember.province ?? '',
        zip_code: teamMember.zip_code ?? '',
        emergency_contact_name: teamMember.emergency_contact_name ?? '',
        emergency_contact_phone: teamMember.emergency_contact_phone ?? '',
        availability: WEEK_DAYS.map((day) => {
            const existing = teamMember.availability?.find(
                (slot) => slot.week_day === day,
            );

            return {
                week_day: day,
                time_from: existing?.time_from ?? '',
                time_to: existing?.time_to ?? '',
            };
        }),
    });

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

    const submit = () => {
        put('/therapist/profile');
    };

    const isOnboarding = teamMember.employment_status === 'onboarding';

    return (
        <>
            <Head title="My Profile" />

            <div className="p-6">
                {isOnboarding && (
                    <div className="mb-5 rounded-[5px] border border-cyan-400 bg-cyan-50 p-4 text-cyan-900">
                        <p className="flex items-center gap-2 font-medium">
                            <ClipboardCheck className="h-4 w-4" />
                            Welcome! Your onboarding is in progress.
                        </p>
                        <p className="mt-1 ml-6 text-sm">
                            {missingDocuments.length > 0
                                ? 'Upload each required document under the Documents tab. Our team will review them and email you once your hire is confirmed — the rest of the portal unlocks then.'
                                : 'All required documents are in. Our team is reviewing them and will email you once your hire is confirmed — the rest of the portal unlocks then.'}
                        </p>
                    </div>
                )}

                <Tabs defaultValue={isOnboarding ? 'documents' : 'profile'}>
                    <TabsList className="flex w-full flex-wrap justify-start">
                        <TabsTrigger value="profile">
                            Profile Information
                        </TabsTrigger>
                        <TabsTrigger value="security">Security</TabsTrigger>
                        <TabsTrigger value="documents">
                            Documents ({documents.length})
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent
                        value="profile"
                        className="grid grid-cols-1 gap-5 pt-5"
                    >
                        <Card className="rounded-[10px]">
                            <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                                <p className="font-bold text-primary md:col-span-2">
                                    Employment Information
                                </p>

                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Position
                                    </p>
                                    <p>{teamMember.position || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Employment Status
                                    </p>
                                    <EmploymentStatusBadge
                                        status={teamMember.employment_status}
                                    />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Hire Date
                                    </p>
                                    <p>{teamMember.hire_date || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Hourly Rate
                                    </p>
                                    <p>${teamMember.hourly_rate ?? '-'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Maximum Caseload
                                    </p>
                                    <p>{teamMember.maximum_caseload}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-[10px]">
                            <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                                <p className="font-bold text-primary md:col-span-2">
                                    Contact Information
                                </p>

                                <div>
                                    <Label htmlFor="profile-phone">Phone</Label>
                                    <Input
                                        id="profile-phone"
                                        value={data.phone}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData('phone', event.target.value)
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="profile-office-phone">
                                        Office Phone
                                    </Label>
                                    <Input
                                        id="profile-office-phone"
                                        value={data.office_phone}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'office_phone',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="profile-secondary-email">
                                        Secondary Email
                                    </Label>
                                    <Input
                                        id="profile-secondary-email"
                                        type="email"
                                        value={data.secondary_email}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'secondary_email',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {errors.secondary_email && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.secondary_email}
                                        </p>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <Label htmlFor="profile-street">
                                        Street Address
                                    </Label>
                                    <Input
                                        id="profile-street"
                                        value={data.street_address}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'street_address',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="profile-city">City</Label>
                                    <Input
                                        id="profile-city"
                                        value={data.city}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData('city', event.target.value)
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="profile-province">
                                        Province
                                    </Label>
                                    <Input
                                        id="profile-province"
                                        value={data.province}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'province',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="profile-zip">
                                        Postal Code
                                    </Label>
                                    <Input
                                        id="profile-zip"
                                        value={data.zip_code}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'zip_code',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-[10px]">
                            <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                                <p className="font-bold text-primary md:col-span-2">
                                    Emergency Contact
                                </p>

                                <div>
                                    <Label htmlFor="profile-emergency-name">
                                        Contact Name
                                    </Label>
                                    <Input
                                        id="profile-emergency-name"
                                        value={data.emergency_contact_name}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'emergency_contact_name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="profile-emergency-phone">
                                        Contact Phone
                                    </Label>
                                    <Input
                                        id="profile-emergency-phone"
                                        value={data.emergency_contact_phone}
                                        className="mt-2 rounded-[10px]"
                                        onChange={(event) =>
                                            setData(
                                                'emergency_contact_phone',
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
                                            <p className="text-sm">
                                                {slot.week_day}
                                            </p>
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

                        <div className="flex justify-end">
                            <Button
                                className="rounded-[10px]"
                                onClick={submit}
                                disabled={processing}
                            >
                                Save Changes
                            </Button>
                        </div>
                    </TabsContent>

                    <TabsContent value="security" className="pt-5">
                        <ChangePasswordCard />
                    </TabsContent>

                    <TabsContent value="documents" className="pt-5">
                        <DocumentsTab
                            teamMember={teamMember}
                            documents={documents}
                            missingDocuments={missingDocuments}
                            uploadUrl="/therapist/profile/documents"
                            deleteUrl={(documentId) =>
                                `/therapist/profile/documents/${documentId}`
                            }
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

TherapistProfile.layout = (page: React.ReactNode) => (
    <TherapistLayout>{page}</TherapistLayout>
);
