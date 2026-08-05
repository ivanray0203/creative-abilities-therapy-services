import { Clock } from 'lucide-react';

import { Card, CardContent, CardTitle } from '@/components/ui/card';
import type { Intake } from '@/types/intake';

const TIMELINE_COLORS = [
    'bg-blue-500',
    'bg-green-500',
    'bg-purple-500',
    'bg-yellow-500',
    'bg-red-500',
    'bg-pink-500',
    'bg-orange-500',
];

/** Reference: cats-frontend/src/pages/admin/intake/History.tsx */
export default function HistoryTab({
    intake,
}: {
    intake: Intake;
    isPreview?: boolean;
}) {
    const timeline = intake.timeline ?? [];

    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <CardTitle className="mb-3">Order Timeline</CardTitle>

                    {timeline.length > 0 ? (
                        <div className="relative space-y-4">
                            {timeline.map((entry, index) => (
                                <div
                                    key={entry.id ?? index}
                                    className="relative flex flex-row items-start gap-4"
                                >
                                    <div className="relative flex flex-col items-center">
                                        <div
                                            className={`flex items-center justify-center rounded-full p-2 text-white ${TIMELINE_COLORS[index % TIMELINE_COLORS.length]}`}
                                        >
                                            <Clock className="h-4 w-4 text-white" />
                                        </div>

                                        {index < timeline.length - 1 && (
                                            <div className="mt-1 w-px flex-1 bg-muted-foreground/30" />
                                        )}
                                    </div>

                                    <div>
                                        <p className="text-sm font-medium">
                                            {entry.title
                                                .charAt(0)
                                                .toUpperCase() +
                                                entry.title.slice(1)}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {entry.date} • {entry.time}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground italic">
                            No timeline available.
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
