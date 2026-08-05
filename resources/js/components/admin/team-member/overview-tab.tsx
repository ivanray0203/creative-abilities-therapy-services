import { Award, Briefcase, Mail, MapPin, Phone } from 'lucide-react';

import { EmploymentStatusBadge } from '@/components/admin/team-member/badges';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { maskSIN } from '@/lib/mask-sin';
import type { TeamMember } from '@/types/team-member';

/** Reference: cats-frontend/src/pages/admin/teamMemberTabs/OverviewTeamMember.tsx */
export default function OverviewTab({
    teamMember,
}: {
    teamMember: TeamMember;
}) {
    // The reference's condition is inverted (`!credentials && !specializations`,
    // showing this card only when both are empty) — ported as the evidently
    // intended behavior: show the card when there's something to show.
    const hasCredentials =
        (teamMember.credentials?.length ?? 0) > 0 ||
        (teamMember.specializations?.length ?? 0) > 0;

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
                                <p>{teamMember.user?.email}</p>
                            </div>
                        </div>
                        {teamMember.secondary_email && (
                            <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                                <Mail className="h-5 w-5" />
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Secondary Email
                                    </p>
                                    <p>{teamMember.secondary_email}</p>
                                </div>
                            </div>
                        )}
                        <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                            <Phone className="h-5 w-5" />
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Phone Number
                                </p>
                                <p>{teamMember.phone || '-'}</p>
                            </div>
                        </div>
                        <div className="flex flex-row items-center gap-3 rounded-[5px] bg-gray-100 p-3">
                            <MapPin className="h-5 w-5" />
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Address
                                </p>
                                <p>
                                    {teamMember.street_address}{' '}
                                    {teamMember.address_line_2}{' '}
                                    {teamMember.city} {teamMember.province}{' '}
                                    {teamMember.zip_code}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex flex-row items-center gap-3">
                        <Briefcase className="text-primary" /> Employment
                        Information
                    </p>

                    <div className="mt-10 flex flex-col gap-5">
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Employee ID
                            </p>
                            <p>EMP-00{teamMember.id}</p>
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Position
                            </p>
                            <p>{teamMember.position || '-'}</p>
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Employment Status
                            </p>
                            <EmploymentStatusBadge
                                status={teamMember.employment_status}
                            />
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">
                                Hire Date
                            </p>
                            <p>{teamMember.hire_date || '-'}</p>
                        </div>
                        <div className="rounded-[5px] bg-gray-100 p-3">
                            <p className="text-xs text-muted-foreground">SIN</p>
                            <p className="font-mono">
                                {teamMember.sin_number
                                    ? maskSIN(teamMember.sin_number)
                                    : 'Not Provided'}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {hasCredentials && (
                <Card className="rounded-[10px] md:col-span-2">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            <Award className="text-primary" /> Credentials &amp;
                            Specializations
                        </p>

                        <div className="mt-5 flex flex-wrap gap-2">
                            {(teamMember.credentials ?? []).map(
                                (credential) => (
                                    <Badge
                                        key={credential}
                                        variant="outline"
                                        className="rounded-[5px]"
                                    >
                                        {credential}
                                    </Badge>
                                ),
                            )}
                            {(teamMember.specializations ?? []).map(
                                (specialization) => (
                                    <Badge
                                        key={specialization}
                                        variant="outline"
                                        className="rounded-[5px] bg-secondary-orange/10"
                                    >
                                        {specialization}
                                    </Badge>
                                ),
                            )}
                        </div>
                    </CardContent>
                </Card>
            )}

            <Card className="rounded-[10px] md:col-span-2">
                <CardContent className="p-5 text-center text-muted-foreground">
                    Performance Metrics — Coming Soon
                </CardContent>
            </Card>
        </div>
    );
}
