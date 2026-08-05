import { router } from '@inertiajs/react';
import { Star, UserPlus, Users2Icon, X } from 'lucide-react';
import { useState } from 'react';

import AddTherapistModal from '@/components/admin/client/add-therapist-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Therapist.tsx */
export default function TherapistTab({
    client,
    therapists,
    isPreview,
}: {
    client: Client;
    therapists: TherapistOption[];
    isPreview?: boolean;
}) {
    const [addOpen, setAddOpen] = useState(false);
    const careTeam = client.care_team ?? [];

    const removeFromCareTeam = (therapistId: number) => {
        router.patch(
            `/admin/clients/${client.id}/care-team`,
            { therapist_id: therapistId, action: 'remove' },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <div className="flex flex-col justify-between md:flex-row">
                            <div>
                                <p className="flex flex-row items-center gap-3">
                                    <Users2Icon className="text-primary" /> Care
                                    Team
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Therapists assigned to this client
                                </p>
                            </div>

                            {!isPreview && (
                                <Button
                                    variant="outline"
                                    className="mt-5 rounded-[5px] bg-gray-100 md:mt-0"
                                    onClick={() => setAddOpen(true)}
                                >
                                    <UserPlus />
                                    Add Therapist
                                </Button>
                            )}
                        </div>

                        <div className="mt-10 grid grid-cols-1 gap-5">
                            {careTeam.length > 0 ? (
                                careTeam.map((therapist) => {
                                    const isPrimary =
                                        therapist.id ===
                                        client.primary_therapist_id;

                                    return (
                                        <div
                                            key={therapist.id}
                                            className="flex flex-row items-center justify-between rounded-sm border p-3"
                                        >
                                            <div className="flex flex-row items-center gap-3">
                                                <div>
                                                    <p className="flex items-center gap-2">
                                                        {therapist.first_name}{' '}
                                                        {therapist.last_name}
                                                        {isPrimary && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="gap-1 rounded-[5px]"
                                                            >
                                                                <Star className="h-3 w-3" />
                                                                Primary
                                                            </Badge>
                                                        )}
                                                    </p>
                                                    {therapist.email && (
                                                        <p className="text-sm text-muted-foreground">
                                                            {therapist.email}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            {!isPreview && !isPrimary && (
                                                <Button
                                                    variant="ghost"
                                                    className="rounded-[5px] text-red-700 hover:bg-red-600 hover:text-white"
                                                    onClick={() =>
                                                        removeFromCareTeam(
                                                            therapist.id,
                                                        )
                                                    }
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    );
                                })
                            ) : (
                                <p className="text-center text-muted-foreground">
                                    No care team members yet
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {!isPreview && (
                <AddTherapistModal
                    client={client}
                    therapists={therapists}
                    isOpen={addOpen}
                    onClose={() => setAddOpen(false)}
                />
            )}
        </>
    );
}
