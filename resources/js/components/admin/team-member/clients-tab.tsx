import { Link } from '@inertiajs/react';
import { ChevronRight, Users } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import type { Client } from '@/types/client';

/** Reference: cats-frontend/src/pages/admin/teamMemberTabs/ClientTab.tsx */
export default function ClientsTab({ clients }: { clients: Client[] }) {
    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            {clients.length > 0 ? (
                clients.map((client) => (
                    <Link key={client.id} href={`/admin/clients/${client.id}`}>
                        <Card className="rounded-[10px] hover:shadow-md">
                            <CardContent className="flex flex-row items-center justify-between gap-3 p-5">
                                <div>
                                    <p>
                                        {client.original_intake
                                            ? `${client.original_intake.child_first_name} ${client.original_intake.child_last_name}`
                                            : `Client #${client.id}`}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {(
                                            client.original_intake?.diagnosis ??
                                            []
                                        ).join(', ') || 'No diagnosis on file'}
                                    </p>
                                </div>
                                <ChevronRight className="h-5 w-5 text-muted-foreground" />
                            </CardContent>
                        </Card>
                    </Link>
                ))
            ) : (
                <Card className="rounded-[10px]">
                    <CardContent className="flex flex-col items-center gap-3 p-10 text-center text-muted-foreground">
                        <Users className="h-8 w-8" />
                        No clients assigned yet
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
