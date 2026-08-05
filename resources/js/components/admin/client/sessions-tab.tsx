import { Link } from '@inertiajs/react';
import { Calendar, ChevronRight } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import type { Client } from '@/types/client';

/**
 * Reference: cats-frontend/src/pages/admin/clientTabs/Sessions.tsx
 *
 * Each availed service links to its session history (Phase 8's
 * ServiceSessions page).
 */
export default function SessionsTab({ client }: { client: Client }) {
    const services = client.client_services ?? [];

    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            {services.length > 0 ? (
                services.map((service) => (
                    <Link
                        key={service.id}
                        href={`/admin/clients/${client.id}/services/${service.id}/sessions`}
                    >
                        <Card className="rounded-[10px] hover:shadow-md">
                            <CardContent className="flex flex-row items-center justify-between gap-3 p-5">
                                <div className="flex flex-row items-center gap-3">
                                    <div className="flex rounded-[5px] bg-secondary-orange/10 p-3 text-primary">
                                        <Calendar />
                                    </div>
                                    <div>
                                        <p>
                                            {service.service?.name ?? 'Service'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {service.frequency} &bull;{' '}
                                            {service.duration}
                                        </p>
                                    </div>
                                </div>

                                <ChevronRight className="h-5 w-5 text-muted-foreground" />
                            </CardContent>
                        </Card>
                    </Link>
                ))
            ) : (
                <Card className="rounded-[10px]">
                    <CardContent className="p-5 text-center text-muted-foreground">
                        No services availed yet
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
