import { Calendar, Mail, MapPin, Phone } from 'lucide-react';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { getInitials } from '@/lib/helpers';
import type { Client } from '@/types/client';

/** Reference: cats-frontend/src/components/ClientCard.tsx */
export function ClientCard({
    client,
    onViewSchedule,
}: {
    client: Client;
    onViewSchedule: (client: Client) => void;
}) {
    const intake = client.original_intake;

    if (!intake) {
        return null;
    }

    const fullAddress = [
        intake.street_address,
        intake.address_line_2,
        intake.city,
        intake.state_province,
        intake.postal_code,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <Card className="p-6">
            <div className="mb-4 flex items-start justify-between">
                <div className="flex items-start gap-4">
                    <Avatar className="h-16 w-16">
                        <AvatarFallback className="bg-primary text-lg text-primary-foreground">
                            {getInitials(
                                `${intake.child_first_name} ${intake.child_last_name}`,
                            )}
                        </AvatarFallback>
                    </Avatar>

                    <div>
                        <h3 className="text-xl font-semibold">
                            {intake.child_first_name} {intake.child_last_name}
                        </h3>
                        <p className="text-muted-foreground">
                            Age {intake.age}
                            {intake.diagnosis?.length
                                ? ` • ${intake.diagnosis.join(', ')}`
                                : ''}
                        </p>

                        <div className="mt-2 flex flex-wrap gap-2">
                            {(client.client_services ?? []).map((service) =>
                                service.service ? (
                                    <Badge
                                        key={service.id}
                                        variant="outline"
                                        className="rounded"
                                    >
                                        {service.service.name}
                                    </Badge>
                                ) : null,
                            )}
                        </div>
                    </div>
                </div>

                <Badge className="rounded border-green-200 bg-green-500/10 text-green-600 capitalize">
                    {client.status}
                </Badge>
            </div>

            <div className="mb-6">
                <h4 className="mb-3 font-semibold">Contact Information</h4>
                <div className="space-y-2 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Phone className="h-4 w-4" />
                        <span>{intake.primary_parent_phone || '-'}</span>
                    </div>
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Mail className="h-4 w-4" />
                        <span>{intake.primary_parent_email || '-'}</span>
                    </div>
                    {fullAddress && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <MapPin className="h-4 w-4" />
                            <span>{fullAddress}</span>
                        </div>
                    )}
                </div>
            </div>

            <Button
                className="w-full rounded"
                onClick={() => onViewSchedule(client)}
            >
                <Calendar className="mr-2 h-4 w-4" />
                View Schedule
            </Button>
        </Card>
    );
}
