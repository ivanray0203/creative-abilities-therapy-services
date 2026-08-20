import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Check,
    Clock,
    MapPin,
    Tag,
    Users,
} from 'lucide-react';

import ProgramRegistrationForm from '@/components/forms/program-registration-form';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import {
    formatProgramDate,
    formatProgramDates,
    formatProgramPrice,
    programAvailabilityLabel,
} from '@/lib/programs';
import type { ProgramDetail } from '@/types/program';

export default function ProgramDetailPage({
    program,
}: {
    program: ProgramDetail;
}) {
    const closesOn = formatProgramDate(
        program.registration_closes_on ?? program.starts_on,
    );

    const facts = [
        { icon: Users, label: 'Ages', value: program.age_range },
        {
            icon: CalendarDays,
            label: 'Runs',
            value: formatProgramDates(program),
        },
        { icon: Clock, label: 'Schedule', value: program.schedule },
        { icon: MapPin, label: 'Location', value: program.location },
        { icon: Tag, label: 'Cost', value: formatProgramPrice(program.price) },
    ].filter((fact) => Boolean(fact.value));

    return (
        <>
            <Head title={program.name} />

            <section className="bg-gradient-to-b from-secondary-orange/10 to-transparent">
                <div className="container mx-auto px-4 py-14">
                    <Link
                        href="/programs"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        All programs
                    </Link>

                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        {program.category && (
                            <span className="rounded-full bg-secondary-orange/10 px-3 py-1 text-xs font-medium text-primary">
                                {program.category}
                            </span>
                        )}
                        <span
                            className={`text-xs ${program.is_open ? 'text-muted-foreground' : 'text-destructive'}`}
                        >
                            {programAvailabilityLabel(program)}
                        </span>
                    </div>

                    <h1 className="mt-3 text-3xl font-bold text-charcoal-gray md:text-4xl">
                        {program.name}
                    </h1>
                    <p className="mt-3 max-w-2xl text-muted-foreground">
                        {program.summary}
                    </p>
                </div>
            </section>

            <section className="container mx-auto grid grid-cols-1 gap-8 px-4 py-12 lg:grid-cols-3">
                <div className="space-y-8 lg:col-span-2">
                    <div>
                        <h2 className="text-xl font-semibold text-charcoal-gray">
                            About this program
                        </h2>
                        {/* Seeded copy uses blank lines for paragraphs. */}
                        {program.description
                            .split('\n')
                            .filter((paragraph) => paragraph.trim() !== '')
                            .map((paragraph) => (
                                <p
                                    key={paragraph.slice(0, 40)}
                                    className="mt-3 text-muted-foreground"
                                >
                                    {paragraph}
                                </p>
                            ))}
                    </div>

                    {program.highlights.length > 0 && (
                        <div>
                            <h2 className="text-xl font-semibold text-charcoal-gray">
                                What&apos;s included
                            </h2>
                            <ul className="mt-3 space-y-2">
                                {program.highlights.map((highlight) => (
                                    <li
                                        key={highlight}
                                        className="flex items-start gap-2 text-muted-foreground"
                                    >
                                        <Check className="mt-1 h-4 w-4 shrink-0 text-primary" />
                                        {highlight}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div id="register">
                        <h2 className="text-xl font-semibold text-charcoal-gray">
                            Register
                        </h2>
                        <p className="mt-1 mb-5 text-sm text-muted-foreground">
                            {program.is_open && closesOn
                                ? `Registration closes on ${closesOn}.`
                                : 'Register your child for this program.'}
                        </p>
                        <ProgramRegistrationForm program={program} />
                    </div>
                </div>

                <Card className="h-fit rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="font-bold text-primary">
                            Program details
                        </p>
                        <div className="mt-4 space-y-4">
                            {facts.map((fact) => (
                                <div
                                    key={fact.label}
                                    className="flex items-start gap-3"
                                >
                                    <fact.icon className="mt-1 h-4 w-4 shrink-0 text-primary" />
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            {fact.label}
                                        </p>
                                        <p className="text-sm text-charcoal-gray">
                                            {fact.value}
                                        </p>
                                    </div>
                                </div>
                            ))}

                            {program.places_left !== null && (
                                <div className="flex items-start gap-3">
                                    <Users className="mt-1 h-4 w-4 shrink-0 text-primary" />
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Places
                                        </p>
                                        <p className="text-sm text-charcoal-gray">
                                            {program.places_left} of{' '}
                                            {program.capacity} remaining
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </section>
        </>
    );
}

ProgramDetailPage.layout = (page: React.ReactNode) => (
    <PublicLayout>{page}</PublicLayout>
);
