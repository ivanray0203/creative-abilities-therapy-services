import { Flag, GraduationCap, Mail, MapPin, Phone } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { capitalize, formatDate } from '@/lib/helpers';
import type { Application } from '@/types/application';

/** Reference: cats-frontend/src/pages/admin/applicationTabs/OverviewApplication.tsx */
export default function OverviewTab({
    application,
}: {
    application: Application;
}) {
    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:grid-cols-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex flex-row items-center gap-3">
                        <Mail className="text-primary" /> Contact Information
                    </p>

                    <div className="mt-10 flex flex-col gap-5">
                        <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                            <Mail className="h-5 w-5" />
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Email Address
                                </p>
                                <p>{application.email}</p>
                            </div>
                        </div>
                        <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                            <Phone className="h-5 w-5" />
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Phone Number
                                </p>
                                <p>{application.phone}</p>
                            </div>
                        </div>
                        <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                            <Flag className="h-5 w-5" />
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Resident Status
                                </p>
                                <p>
                                    {application.resident_status ||
                                        'Not Specified'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                            <MapPin className="h-5 w-5" />
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Address
                                </p>
                                <p>
                                    {application.street_address}{' '}
                                    {application.address_line_2}{' '}
                                    {application.city} {application.province}{' '}
                                    {application.zip_code}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex flex-row items-center gap-3">
                        <GraduationCap className="text-primary" /> Professional
                        Details
                    </p>

                    <div className="mt-10 flex flex-col gap-5">
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Position Applied For
                            </p>
                            <p>{application.position_applied}</p>
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Current Status
                            </p>
                            <p>{capitalize(application.profession_status)}</p>
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Preferred Start Date
                            </p>
                            <p>
                                {formatDate(application.preferred_start_date)}
                            </p>
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Transportation
                            </p>
                            <div className="mt-1 flex flex-wrap gap-2">
                                {application.has_vehicle && (
                                    <Badge
                                        variant="outline"
                                        className="rounded-[5px] bg-gray-700 text-gray-100"
                                    >
                                        Vehicle Access
                                    </Badge>
                                )}
                                {application.drivers_license && (
                                    <Badge
                                        variant="outline"
                                        className="rounded-[5px] bg-gray-500 text-gray-100"
                                    >
                                        Driver&apos;s License
                                    </Badge>
                                )}
                                {!application.has_vehicle &&
                                    !application.drivers_license && (
                                        <Badge
                                            variant="outline"
                                            className="rounded-[5px] bg-gray-500 text-gray-100"
                                        >
                                            No License and No Access to Vehicle
                                        </Badge>
                                    )}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
