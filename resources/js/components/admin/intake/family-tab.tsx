import {
    AlertCircleIcon,
    Globe,
    Mail,
    MapPin,
    Phone,
    User,
    Users2Icon,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { capitalize } from '@/lib/helpers';
import type { Intake } from '@/types/intake';

/** Reference: cats-frontend/src/pages/admin/intake/Family.tsx */
export default function FamilyTab({
    intake,
    isPreview,
}: {
    intake: Intake;
    isPreview?: boolean;
}) {
    const fullAddress = `${intake.street_address ?? ''} ${intake.address_line_2 ?? ''} ${intake.city ?? ''} ${intake.state_province ?? ''} ${intake.postal_code ?? ''}`;

    return (
        <>
            <div className="grid gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <User className="text-primary" /> Primary
                            Parent/Guardian
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Main Contact for communication
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Full Name
                                </p>
                                <p>{intake.primary_parent_name}</p>
                            </div>
                            <div className="border-b pb-3">
                                <p className="text-xs text-muted-foreground">
                                    Relationship to Child
                                </p>
                                <p>
                                    {capitalize(
                                        intake.primary_relationship_to_child,
                                    )}
                                </p>
                            </div>

                            <div className="flex flex-row items-center gap-3">
                                <Mail className="h-4 w-4 text-muted-foreground" />
                                <p>{intake.primary_parent_email}</p>
                            </div>
                            <div className="flex flex-row items-center gap-3 border-b pb-3">
                                <Phone className="h-4 w-4 text-muted-foreground" />
                                <p>{intake.primary_parent_phone}</p>
                            </div>

                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Preferred Contact Method
                                </p>
                                <div className="mt-2 flex">
                                    <p className="rounded-[5px] bg-gray-500 p-1 text-sm text-white">
                                        {capitalize(
                                            intake.primary_contact_method,
                                        )}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Users2Icon className="text-primary" /> Secondary
                            Parent/Guardian
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Additional Contact Person
                        </p>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Full Name
                                </p>
                                <p>{intake.secondary_parent_name || '-'}</p>
                            </div>
                            <div className="border-b pb-3">
                                <p className="text-xs text-muted-foreground">
                                    Relationship to Child
                                </p>
                                <p>
                                    {capitalize(
                                        intake.secondary_relationship_to_child,
                                    ) || '-'}
                                </p>
                            </div>

                            <div className="flex flex-row items-center gap-3">
                                <Mail className="h-4 w-4 text-muted-foreground" />
                                <p>{intake.secondary_parent_email || '-'}</p>
                            </div>
                            <div className="flex flex-row items-center gap-3 border-b pb-3">
                                <Phone className="h-4 w-4 text-muted-foreground" />
                                <p>{intake.secondary_parent_phone || '-'}</p>
                            </div>

                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Preferred Contact Method
                                </p>
                                <div className="mt-2 flex">
                                    <p className="rounded-[5px] bg-gray-500 p-1 text-sm text-white">
                                        {capitalize(
                                            intake.secondary_contact_method,
                                        ) || 'Not Available'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px] border border-primary bg-gradient-to-b from-primary/20 to-transparent">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <AlertCircleIcon className="text-primary" />{' '}
                            Emergency Contact
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Critical contact in case of emergency
                        </p>

                        <div className="mt-5 rounded-[10px] border border-primary bg-gradient-to-b from-primary/5 to-transparent p-3">
                            <p className="flex flex-row items-center gap-3 text-sm text-dark-orange">
                                <AlertCircleIcon />
                                This contact should be called if
                                parents/guardians cannot be reached during
                                emergencies.
                            </p>
                        </div>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Contact Name
                                </p>
                                <p>{intake.emergency_contact_name || '-'}</p>
                            </div>
                            <div className="border-b pb-5">
                                <p className="text-xs text-muted-foreground">
                                    Relationship to Child
                                </p>
                                <p>
                                    {capitalize(
                                        intake.emergency_contact_relationship,
                                    ) || '-'}
                                </p>
                            </div>

                            <div className="flex flex-row justify-between">
                                <p className="flex flex-row items-center gap-3 text-sm">
                                    <Phone className="h-4 w-4" />
                                    {intake.emergency_contact_phone}
                                </p>

                                {!isPreview && (
                                    <a
                                        href={`tel:${intake.emergency_contact_phone}`}
                                    >
                                        <Button
                                            variant="outline"
                                            className="w-full rounded-[5px] border-primary text-primary hover:bg-primary hover:text-white sm:w-auto"
                                        >
                                            Call Now
                                        </Button>
                                    </a>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <MapPin className="text-primary" /> Family Address
                        </p>

                        <div className="mt-10 grid grid-cols-2 gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Address
                                </p>
                                <p>{fullAddress}</p>

                                {!isPreview && (
                                    <a
                                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(fullAddress)}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Button
                                            variant="ghost"
                                            className="mt-2 rounded-[5px] border-primary text-primary hover:bg-primary hover:text-white"
                                        >
                                            <Globe /> View on Map
                                        </Button>
                                    </a>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
