import {
    CalendarX2,
    CircleSlash,
    Clock,
    Target,
    TrendingUp,
} from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import type { ClientProgress } from '@/types/client';

/**
 * What has actually been delivered for this client.
 *
 * Built from figures the app already captured but never showed: the sessions
 * authorised per availed service, the goals recorded against it, and the
 * elapsed time each therapist's clock-out wrote down.
 */
export default function ProgressTab({
    progress,
}: {
    progress: ClientProgress;
}) {
    if (!progress.has_data) {
        return (
            <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
                <Card className="rounded-[10px]">
                    <CardContent className="flex flex-col items-center gap-3 p-10 text-center text-muted-foreground">
                        <TrendingUp className="h-8 w-8" />
                        <p>
                            No progress yet — it appears once services are
                            availed and sessions delivered.
                        </p>
                    </CardContent>
                </Card>
            </div>
        );
    }

    const { attendance, hours, services } = progress;

    return (
        <div className="grid grid-cols-1 gap-4 pt-2 md:p-5 md:pt-0">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <SummaryCard
                    label="Hours Delivered"
                    value={`${hours.total}`}
                    caption="across all services"
                    icon={<Clock className="h-5 w-5 text-primary" />}
                />
                <SummaryCard
                    label="Hours This Month"
                    value={`${hours.this_month}`}
                    caption="delivered so far"
                    icon={<TrendingUp className="h-5 w-5 text-primary" />}
                />
                <SummaryCard
                    label="Attendance"
                    value={
                        attendance.rate !== null ? `${attendance.rate}%` : '—'
                    }
                    caption={`${attendance.attended} attended · ${attendance.no_show} missed`}
                    icon={<Target className="h-5 w-5 text-primary" />}
                />
            </div>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex items-center gap-3 font-bold text-primary">
                        <Target className="h-5 w-5" /> Service Progress
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Sessions delivered against those authorised
                    </p>

                    {services.length === 0 ? (
                        <p className="mt-6 text-sm text-muted-foreground">
                            No services availed yet.
                        </p>
                    ) : (
                        <div className="mt-6 grid grid-cols-1 gap-5">
                            {services.map((service) => (
                                <div
                                    key={service.id}
                                    className="rounded-[10px] border p-4"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p className="font-medium">
                                                {service.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {[
                                                    service.therapist,
                                                    service.frequency,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') || '—'}
                                            </p>
                                        </div>

                                        <div className="text-right">
                                            <p className="font-semibold">
                                                {service.authorised !== null
                                                    ? `${service.delivered} of ${service.authorised}`
                                                    : `${service.delivered} delivered`}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {service.hours} hrs
                                                {service.remaining !== null
                                                    ? ` · ${service.remaining} left`
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>

                                    {service.percent !== null ? (
                                        <div className="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-2 rounded-full bg-primary transition-all"
                                                style={{
                                                    width: `${service.percent}%`,
                                                }}
                                            />
                                        </div>
                                    ) : (
                                        /* Nothing was authorised, so there is
                                           no denominator to show a bar against. */
                                        <p className="mt-3 text-xs text-muted-foreground">
                                            No session count authorised for this
                                            service.
                                        </p>
                                    )}

                                    {service.goals && (
                                        <div className="mt-4 border-t pt-3">
                                            <p className="text-xs text-muted-foreground">
                                                Goals
                                            </p>
                                            <p className="mt-1 text-sm">
                                                {service.goals}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex items-center gap-3 font-bold text-primary">
                        <CalendarX2 className="h-5 w-5" /> Attendance
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Cancellations are usually agreed in advance, so they are
                        left out of the rate
                    </p>

                    <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <Tally
                            label="Attended"
                            value={attendance.attended}
                            tone="text-green-700"
                        />
                        <Tally
                            label="Cancelled"
                            value={attendance.cancelled}
                            tone="text-muted-foreground"
                        />
                        <Tally
                            label="No Show"
                            value={attendance.no_show}
                            tone="text-destructive"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function SummaryCard({
    label,
    value,
    caption,
    icon,
}: {
    label: string;
    value: string;
    caption: string;
    icon: React.ReactNode;
}) {
    return (
        <Card className="rounded-[10px]">
            <CardContent className="p-5">
                <div className="flex items-center justify-between">
                    <p className="text-xs text-muted-foreground">{label}</p>
                    {icon}
                </div>
                <p className="mt-2 text-2xl font-bold">{value}</p>
                <p className="text-xs text-muted-foreground">{caption}</p>
            </CardContent>
        </Card>
    );
}

function Tally({
    label,
    value,
    tone,
}: {
    label: string;
    value: number;
    tone: string;
}) {
    return (
        <div className="rounded-[10px] border p-4">
            <p className="flex items-center gap-2 text-xs text-muted-foreground">
                <CircleSlash className="h-3 w-3" /> {label}
            </p>
            <p className={`mt-1 text-xl font-semibold ${tone}`}>{value}</p>
        </div>
    );
}
