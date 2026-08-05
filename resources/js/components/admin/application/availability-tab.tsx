import { Calendar } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import type { Application } from '@/types/application';

/** Reference: cats-frontend/src/pages/admin/applicationTabs/AvailabilityTab.tsx */
export default function AvailabilityTab({
    application,
}: {
    application: Application;
}) {
    const availability = application.availability ?? [];

    return (
        <div className="grid grid-cols-1 gap-2 p-5">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p>Weekly Availability</p>
                    <p className="text-sm text-muted-foreground">
                        Candidate&apos;s preferred work schedule
                    </p>

                    {availability.length > 0 ? (
                        <div className="mt-3 grid grid-cols-1 gap-3">
                            {availability.map((slot, index) => (
                                <div
                                    key={index}
                                    className="flex flex-row items-center justify-between rounded-[5px] bg-gray-100 p-3"
                                >
                                    <p className="font-mono">
                                        {slot.week_day || 'Not Specified'}
                                    </p>
                                    <p className="font-mono">
                                        {slot.time_from && slot.time_to
                                            ? `${slot.time_from} - ${slot.time_to}`
                                            : 'No Time Set'}
                                    </p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center py-10 text-center opacity-70">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                <Calendar className="h-6 w-6 text-muted-foreground" />
                            </div>
                            <p className="mt-4 text-lg font-medium">
                                No Availability Set
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                This candidate has not added any work schedule.
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
