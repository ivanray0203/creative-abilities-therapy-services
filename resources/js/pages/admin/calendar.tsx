import { Head } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';

type ViewMode = 'month' | 'week' | 'day';

interface Session {
    id: string;
    date: string;
    time: string;
    endTime: string;
    client: string;
    type: 'OT' | 'ABA' | 'Speech';
    location: string;
}

/**
 * Static mock sessions, ported 1:1 from cats-frontend's CalendarPage.tsx
 * dummySessions. The reference's fetchSessions() has its axios call
 * commented out and always falls back to this same data, so there is no
 * real endpoint to wire up here either.
 */
const DUMMY_SESSIONS: Session[] = [
    {
        id: '1',
        date: '2025-11-04',
        time: '09:00',
        endTime: '10:30',
        client: 'Emma Thompson',
        type: 'OT',
        location: 'Main Office - Room 101',
    },
    {
        id: '2',
        date: '2025-11-04',
        time: '11:00',
        endTime: '12:00',
        client: 'Noah Williams',
        type: 'ABA',
        location: 'School - Lincoln Elementary',
    },
    {
        id: '3',
        date: '2025-11-06',
        time: '10:00',
        endTime: '11:00',
        client: 'Sophia Anderson',
        type: 'Speech',
        location: 'Clinic Room 3',
    },
];

function formatDate(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function isSameDay(a: Date, b: Date): boolean {
    return formatDate(a) === formatDate(b);
}

function getMonthGrid(date: Date): Date[] {
    const start = new Date(date.getFullYear(), date.getMonth(), 1);
    const startDay = start.getDay();
    const grid: Date[] = [];

    for (let i = 0; i < 35; i++) {
        const d = new Date(start);
        d.setDate(1 - startDay + i);
        grid.push(d);
    }

    return grid;
}

function getWeekDays(date: Date): Date[] {
    const start = new Date(date);
    start.setDate(date.getDate() - date.getDay());

    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);

        return d;
    });
}

/**
 * Admin calendar, ported from cats-frontend's src/pages/admin/CalendarPage.tsx.
 */
export default function AdminCalendar() {
    const [sessions] = useState<Session[]>(DUMMY_SESSIONS);
    const [view, setView] = useState<ViewMode>('week');
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState(formatDate(new Date()));

    const daySessions = useMemo(
        () => sessions.filter((s) => s.date === selectedDate),
        [sessions, selectedDate],
    );

    const monthLabel = currentDate.toLocaleString('default', {
        month: 'long',
        year: 'numeric',
    });

    const changeDay = (offset: number) => {
        const d = new Date(currentDate);
        d.setDate(d.getDate() + offset);
        setCurrentDate(d);
        setSelectedDate(formatDate(d));
    };

    const goToday = () => {
        const today = new Date();
        setCurrentDate(today);
        setSelectedDate(formatDate(today));
    };

    return (
        <>
            <Head title="Calendar" />
            <div className="space-y-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="mb-1 text-3xl font-bold text-foreground">
                        My Calendar
                    </h1>
                    <p className="text-muted-foreground">
                        View and manage your personal schedule
                    </p>
                </div>

                {/* Filters */}
                <Card className="p-4">
                    <div className="flex items-center gap-4">
                        <Input placeholder="Search by client or session type..." />
                        <Select defaultValue="all-types">
                            <SelectTrigger className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all-types">
                                    All Types
                                </SelectItem>
                                <SelectItem value="ot">
                                    Occupational Therapy
                                </SelectItem>
                                <SelectItem value="speech">
                                    Speech Therapy
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </Card>

                {/* View Toggle */}
                <div className="flex items-center justify-center gap-2">
                    <Button
                        variant={view === 'month' ? 'default' : 'outline'}
                        className="flex-1"
                        onClick={() => setView('month')}
                    >
                        Month View
                    </Button>
                    <Button
                        variant={view === 'week' ? 'default' : 'outline'}
                        className="flex-1"
                        onClick={() => setView('week')}
                    >
                        Week View
                    </Button>
                    <Button
                        variant={view === 'day' ? 'default' : 'outline'}
                        className="flex-1"
                        onClick={() => setView('day')}
                    >
                        Day View
                    </Button>
                </div>

                {/* Calendar Header */}
                <Card className="p-6">
                    <div className="mb-6 flex items-center justify-between">
                        <h2 className="text-xl font-semibold">{monthLabel}</h2>
                        <div className="flex items-center gap-4">
                            <Button
                                size="icon"
                                variant="ghost"
                                onClick={() => changeDay(-1)}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <span className="px-4 text-sm font-medium">
                                {currentDate.toDateString()}
                            </span>
                            <Button
                                size="icon"
                                variant="ghost"
                                onClick={() => changeDay(1)}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" onClick={goToday}>
                                Today
                            </Button>
                        </div>
                    </div>

                    {/* MONTH VIEW */}
                    {view === 'month' && (
                        <div className="grid grid-cols-7 gap-2">
                            {[
                                'Sun',
                                'Mon',
                                'Tue',
                                'Wed',
                                'Thu',
                                'Fri',
                                'Sat',
                            ].map((d) => (
                                <div
                                    key={d}
                                    className="py-2 text-center text-sm font-medium text-muted-foreground"
                                >
                                    {d}
                                </div>
                            ))}

                            {getMonthGrid(currentDate).map((day) => {
                                const key = formatDate(day);
                                const isToday = isSameDay(day, new Date());
                                const isCurrentMonth =
                                    day.getMonth() === currentDate.getMonth();
                                const count = sessions.filter(
                                    (s) => s.date === key,
                                ).length;

                                return (
                                    <Card
                                        key={key}
                                        onClick={() => setSelectedDate(key)}
                                        className={`min-h-24 cursor-pointer p-2 ${isToday ? 'border-2 border-primary' : ''} ${!isCurrentMonth ? 'opacity-50' : ''}`}
                                    >
                                        <div className="mb-1 text-sm font-medium">
                                            {day.getDate()}
                                        </div>
                                        {count > 0 && (
                                            <div className="rounded bg-blue-100 p-1 text-xs text-blue-700">
                                                {count} session(s)
                                            </div>
                                        )}
                                    </Card>
                                );
                            })}
                        </div>
                    )}

                    {/* WEEK VIEW */}
                    {view === 'week' && (
                        <div className="grid grid-cols-7 gap-2">
                            {getWeekDays(currentDate).map((day) => {
                                const key = formatDate(day);
                                const isToday = isSameDay(day, new Date());
                                const weekDaySessions = sessions.filter(
                                    (s) => s.date === key,
                                );

                                return (
                                    <Card
                                        key={key}
                                        onClick={() => setSelectedDate(key)}
                                        className={`min-h-32 cursor-pointer p-3 ${isToday ? 'border-2 border-primary' : ''}`}
                                    >
                                        <div className="mb-2 text-sm font-medium">
                                            {day.toLocaleDateString(undefined, {
                                                weekday: 'short',
                                                day: 'numeric',
                                            })}
                                        </div>
                                        {weekDaySessions.map((s) => (
                                            <div
                                                key={s.id}
                                                className="mb-1 rounded bg-muted p-1 text-xs"
                                            >
                                                {s.time} · {s.client}
                                            </div>
                                        ))}
                                    </Card>
                                );
                            })}
                        </div>
                    )}

                    {/* DAY VIEW */}
                    {view === 'day' && (
                        <div className="space-y-3">
                            {daySessions.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No sessions
                                </p>
                            )}
                            {daySessions.map((s) => (
                                <div
                                    key={s.id}
                                    className="rounded bg-muted/40 p-3"
                                >
                                    <p className="font-medium">
                                        {s.time} – {s.endTime}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {s.client}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {s.location}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

AdminCalendar.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
