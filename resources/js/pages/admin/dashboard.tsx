import { Head, Link } from '@inertiajs/react';
import {
    Calendar,
    CalendarPlus,
    ClipboardList,
    DollarSign,
    FileText,
    Receipt,
    TrendingDown,
    TrendingUp,
    UserPlus,
    Users,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';

interface Trend {
    delta: number;
    percent: number;
}

interface DashboardStats {
    pending_intakes: number;
    pending_intakes_trend: Trend;
    active_clients: number;
    active_clients_trend: Trend;
    unpaid_invoices_total: number;
    unpaid_invoices_count: number;
    today_sessions_total: number;
    today_sessions_completed: number;
    today_sessions_upcoming: number;
}

interface RevenueMonth {
    month: string;
    revenue: number;
}

interface ServiceSlice {
    label: string;
    percent: number;
    sessions: number;
    color: string;
}

interface AdminDashboardProps {
    stats: DashboardStats;
    revenueByMonth: RevenueMonth[];
    sessionsByService: ServiceSlice[];
}

function TrendLine({ trend, suffix }: { trend: Trend; suffix: string }) {
    if (trend.delta === 0) {
        return (
            <p className="mt-2 text-xs text-muted-foreground">
                No change {suffix}
            </p>
        );
    }

    const isUp = trend.delta > 0;
    const Icon = isUp ? TrendingUp : TrendingDown;

    return (
        <p
            className={`mt-2 flex items-center gap-1 text-xs ${isUp ? 'text-green-600' : 'text-red-600'}`}
        >
            <Icon className="h-3 w-3" />
            {isUp ? '+' : ''}
            {trend.delta} ({trend.percent}%) {suffix}
        </p>
    );
}

/**
 * Converts a cumulative percent range into an SVG pie-slice `d` path,
 * replacing the reference's 5 hand-authored `<path>` arcs (which assumed a
 * fixed 25/27/19/13/15 split) with a generator driven by real percentages.
 */
function describeSlice(startPercent: number, endPercent: number): string {
    const cx = 100;
    const cy = 100;
    const r = 80;
    const startAngle = startPercent * 3.6 - 90;
    const endAngle = endPercent * 3.6 - 90;

    const toPoint = (angleDeg: number) => {
        const rad = (angleDeg * Math.PI) / 180;

        return { x: cx + r * Math.cos(rad), y: cy + r * Math.sin(rad) };
    };

    const start = toPoint(startAngle);
    const end = toPoint(endAngle);
    const largeArc = endPercent - startPercent > 50 ? 1 : 0;

    return `M ${cx} ${cy} L ${start.x} ${start.y} A ${r} ${r} 0 ${largeArc} 1 ${end.x} ${end.y} Z`;
}

/**
 * Admin dashboard, ported from cats-frontend's src/pages/admin/Dashboard.tsx.
 * The reference file gated this markup behind a permanently-false `isReady`
 * flag (so it rendered a "Coming Soon" placeholder and this content was
 * dead code) and every stat/chart value was hardcoded. The layout and
 * copy are preserved here, but the numbers now come from real queries in
 * AdminDashboard\DashboardController — there's no "expenses" entity
 * anywhere in this app, so that mock series was dropped rather than
 * inventing numbers for it.
 */
export default function AdminDashboard({
    stats,
    revenueByMonth,
    sessionsByService,
}: AdminDashboardProps) {
    const maxRevenue = Math.max(1, ...revenueByMonth.map((m) => m.revenue));

    const pieSlices = sessionsByService.reduce<
        Array<ServiceSlice & { start: number; end: number }>
    >((acc, slice) => {
        const start = acc.length > 0 ? acc[acc.length - 1].end : 0;
        acc.push({ ...slice, start, end: start + slice.percent });

        return acc;
    }, []);

    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-foreground">
                            Dashboard
                        </h1>
                        <p className="text-muted-foreground">
                            Welcome back! Here's your business overview for
                            today.
                        </p>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Calendar className="h-4 w-4" />
                        {new Date().toLocaleDateString(undefined, {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                        })}
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-6 md:grid-cols-4">
                    <Card className="border-l-4 border-l-blue-500 p-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="mb-1 text-sm text-muted-foreground">
                                    Pending Intakes
                                </p>
                                <p className="text-3xl font-bold">
                                    {stats.pending_intakes}
                                </p>
                                <TrendLine
                                    trend={stats.pending_intakes_trend}
                                    suffix="from last week"
                                />
                            </div>
                            <div className="rounded-lg bg-blue-50 p-3">
                                <FileText className="h-6 w-6 text-blue-600" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-l-green-500 p-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="mb-1 text-sm text-muted-foreground">
                                    Active Clients
                                </p>
                                <p className="text-3xl font-bold">
                                    {stats.active_clients}
                                </p>
                                <TrendLine
                                    trend={stats.active_clients_trend}
                                    suffix="this month"
                                />
                            </div>
                            <div className="rounded-lg bg-green-50 p-3">
                                <Users className="h-6 w-6 text-green-600" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-l-yellow-500 p-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="mb-1 text-sm text-muted-foreground">
                                    Unpaid Invoices
                                </p>
                                <p className="text-3xl font-bold">
                                    $
                                    {stats.unpaid_invoices_total.toLocaleString(
                                        undefined,
                                        {
                                            maximumFractionDigits: 0,
                                        },
                                    )}
                                </p>
                                <p className="mt-2 flex items-center gap-1 text-xs text-yellow-600">
                                    <Calendar className="h-3 w-3" />
                                    {stats.unpaid_invoices_count} invoice
                                    {stats.unpaid_invoices_count === 1
                                        ? ''
                                        : 's'}{' '}
                                    pending payment
                                </p>
                            </div>
                            <div className="rounded-lg bg-yellow-50 p-3">
                                <DollarSign className="h-6 w-6 text-yellow-600" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-l-primary p-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="mb-1 text-sm text-muted-foreground">
                                    Today's Sessions
                                </p>
                                <p className="text-3xl font-bold">
                                    {stats.today_sessions_total}
                                </p>
                                <p className="mt-2 text-xs text-green-600">
                                    {stats.today_sessions_completed} completed •{' '}
                                    {stats.today_sessions_upcoming} upcoming
                                </p>
                            </div>
                            <div className="rounded-lg bg-primary/10 p-3">
                                <Calendar className="h-6 w-6 text-primary" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Quick Actions */}
                <Card className="p-6">
                    <div className="mb-4 flex items-center gap-2">
                        <TrendingUp className="h-5 w-5 text-primary" />
                        <h2 className="text-lg font-semibold">Quick Actions</h2>
                    </div>
                    <p className="mb-4 text-sm text-muted-foreground">
                        Common tasks and shortcuts
                    </p>
                    <div className="grid gap-4 md:grid-cols-4">
                        <Button
                            asChild
                            variant="outline"
                            className="flex h-20 flex-col gap-2"
                        >
                            <Link href="/admin/clients">
                                <UserPlus className="h-5 w-5 text-primary" />
                                <span className="text-sm">New Client</span>
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="flex h-20 flex-col gap-2"
                        >
                            <Link href="/admin/sessions/create">
                                <CalendarPlus className="h-5 w-5 text-primary" />
                                <span className="text-sm">
                                    Schedule Session
                                </span>
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="flex h-20 flex-col gap-2"
                        >
                            <Link href="/admin/invoices/create">
                                <Receipt className="h-5 w-5 text-primary" />
                                <span className="text-sm">Create Invoice</span>
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="flex h-20 flex-col gap-2"
                        >
                            <Link href="/admin/intake">
                                <ClipboardList className="h-5 w-5 text-primary" />
                                <span className="text-sm">Review Intakes</span>
                            </Link>
                        </Button>
                    </div>
                </Card>

                {/* Charts Row */}
                <div className="grid gap-6 md:grid-cols-2">
                    <Card className="p-6">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingUp className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">
                                Revenue Overview
                            </h2>
                        </div>
                        <p className="mb-6 text-sm text-muted-foreground">
                            Paid invoice revenue (Last 7 months)
                        </p>
                        <div className="flex h-64 items-end justify-between gap-2">
                            {revenueByMonth.map(({ month, revenue }) => (
                                <div
                                    key={month}
                                    className="flex flex-1 flex-col items-center gap-2"
                                >
                                    <div className="w-full">
                                        <div
                                            className="w-full rounded-t bg-primary"
                                            style={{
                                                height: `${(revenue / maxRevenue) * 200}px`,
                                            }}
                                        />
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        {month}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div className="mt-4 flex items-center justify-center gap-6">
                            <div className="flex items-center gap-2">
                                <div className="h-3 w-3 rounded bg-primary" />
                                <span className="text-sm text-muted-foreground">
                                    Revenue
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="mb-4 flex items-center gap-2">
                            <Calendar className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">
                                Sessions by Service
                            </h2>
                        </div>
                        <p className="mb-6 text-sm text-muted-foreground">
                            This month's service distribution
                        </p>
                        {sessionsByService.length === 0 ? (
                            <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">
                                No sessions scheduled this month
                            </div>
                        ) : (
                            <>
                                <div className="flex h-64 items-center justify-center">
                                    <svg
                                        viewBox="0 0 200 200"
                                        className="h-48 w-48"
                                    >
                                        {pieSlices.map((slice) => (
                                            <path
                                                key={slice.label}
                                                d={describeSlice(
                                                    slice.start,
                                                    slice.end,
                                                )}
                                                fill={slice.color}
                                            />
                                        ))}
                                    </svg>
                                </div>
                                <div className="mt-4 grid grid-cols-2 gap-4">
                                    {sessionsByService.map(
                                        (
                                            { label, percent, sessions, color },
                                            index,
                                        ) => (
                                            <div
                                                key={label}
                                                className={`flex items-center gap-2 ${index === sessionsByService.length - 1 && sessionsByService.length % 2 !== 0 ? 'col-span-2' : ''}`}
                                            >
                                                <div
                                                    className="h-3 w-3 rounded"
                                                    style={{
                                                        backgroundColor: color,
                                                    }}
                                                />
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium">
                                                        {label}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {percent}%
                                                    </p>
                                                </div>
                                                <p className="text-sm font-medium">
                                                    {sessions} session
                                                    {sessions === 1 ? '' : 's'}
                                                </p>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
