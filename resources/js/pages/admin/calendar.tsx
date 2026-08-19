import { Head } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import { CalendarDaySessionsModal } from '@/components/admin/calendar-day-sessions-modal';
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
import type { CalendarSession } from '@/types/session';

type ViewMode = 'month' | 'week' | 'day';

interface TherapistOption {
    id: number;
    name: string;
}

/**
 * Local calendar date, not UTC. The server sends `date` in the app timezone,
 * so `toISOString()` here would put the two a day apart for any admin west
 * of UTC and quietly drop sessions out of the grid.
 */
function formatDate(date: Date): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
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
export default function AdminCalendar({
    sessions: allSessions,
    therapists,
}: {
    sessions: CalendarSession[];
    therapists: TherapistOption[];
}) {
    const [view, setView] = useState<ViewMode>('week');
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState(formatDate(new Date()));
    const [search, setSearch] = useState('');
    const [therapistId, setTherapistId] = useState('all');
    const [dayModalOpen, setDayModalOpen] = useState(false);

    const sessions = useMemo(() => {
        const term = search.trim().toLowerCase();

        return allSessions.filter((session) => {
            if (
                therapistId !== 'all' &&
                String(session.therapistId) !== therapistId
            ) {
                return false;
            }

            if (term === '') {
                return true;
            }

            return [session.client, session.type, session.therapist].some(
                (field) => field.toLowerCase().includes(term),
            );
        });
    }, [allSessions, search, therapistId]);

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

    /** Month cells only have room for a count, so the detail goes in a modal. */
    const openDay = (date: string) => {
        setSelectedDate(date);
        setDayModalOpen(true);
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
                        Clinic Calendar
                    </h1>
                    <p className="text-muted-foreground">
                        Every therapist&apos;s schedule in one place
                    </p>
                </div>

                {/* Filters */}
                <Card className="p-4">
                    <div className="flex items-center gap-4">
                        <Input
                            placeholder="Search by client, therapist, or service..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                        <Select
                            value={therapistId}
                            onValueChange={setTherapistId}
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Therapists
                                </SelectItem>
                                {therapists.map((therapist) => (
                                    <SelectItem
                                        key={therapist.id}
                                        value={String(therapist.id)}
                                    >
                                        {therapist.name}
                                    </SelectItem>
                                ))}
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
                                        id={`month-day-${key}`}
                                        onClick={() => openDay(key)}
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
                                                className={`mb-1 rounded bg-muted p-1 text-xs ${s.status === 'cancelled' ? 'line-through opacity-60' : ''}`}
                                            >
                                                <p>
                                                    {s.time} · {s.client}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {s.therapist}
                                                </p>
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
                                    className={`rounded bg-muted/40 p-3 ${s.status === 'cancelled' ? 'opacity-60' : ''}`}
                                >
                                    <p className="font-medium">
                                        {s.time} – {s.endTime} · {s.type}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {s.client} with {s.therapist}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {s.location || 'No location set'} ·{' '}
                                        {s.status}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            <CalendarDaySessionsModal
                open={dayModalOpen}
                onClose={() => setDayModalOpen(false)}
                date={selectedDate}
                sessions={daySessions}
            />
        </>
    );
}

AdminCalendar.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
