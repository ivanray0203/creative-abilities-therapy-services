import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import PublicLayout from '@/layouts/public-layout';
import {
    formatProgramDates,
    formatProgramPrice,
    programAvailabilityLabel,
} from '@/lib/programs';
import type { ProgramSummary } from '@/types/program';

const ALL_CATEGORIES = 'All';

/**
 * Public listing of the clinic's group programs — camps, social groups, and
 * parent workshops, as opposed to the one-to-one therapy an intake leads to.
 */
export default function Programs({ programs }: { programs: ProgramSummary[] }) {
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState(ALL_CATEGORIES);

    const categories = useMemo(() => {
        const found = programs
            .map((program) => program.category)
            .filter((value): value is string => Boolean(value));

        return [ALL_CATEGORIES, ...Array.from(new Set(found))];
    }, [programs]);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return programs.filter((program) => {
            if (category !== ALL_CATEGORIES && program.category !== category) {
                return false;
            }

            if (term === '') {
                return true;
            }

            return [program.name, program.summary, program.age_range ?? '']
                .join(' ')
                .toLowerCase()
                .includes(term);
        });
    }, [programs, search, category]);

    return (
        <>
            <Head title="Programs" />

            <section className="bg-gradient-to-b from-secondary-orange/10 to-transparent">
                <div className="container mx-auto px-4 py-20 text-center md:py-28">
                    <h1 className="text-3xl font-bold text-charcoal-gray md:text-4xl">
                        Our Programs
                    </h1>
                    <p className="mx-auto mt-4 max-w-2xl text-muted-foreground">
                        Group programs where children build skills alongside
                        their peers, and workshops for the parents supporting
                        them. Register directly below — no referral needed.
                    </p>
                </div>
            </section>

            <section className="container mx-auto px-4 pb-20">
                <div className="flex flex-col gap-4 md:flex-row md:items-center">
                    <Input
                        id="program-search"
                        placeholder="Search programs..."
                        value={search}
                        className="rounded-[10px] md:max-w-sm"
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <div className="flex flex-wrap gap-2">
                        {categories.map((option) => (
                            <Button
                                key={option}
                                type="button"
                                size="sm"
                                variant={
                                    category === option ? 'default' : 'outline'
                                }
                                className="rounded-full"
                                onClick={() => setCategory(option)}
                            >
                                {option}
                            </Button>
                        ))}
                    </div>
                </div>

                {filtered.length === 0 ? (
                    <p className="mt-12 text-center text-muted-foreground">
                        {programs.length === 0
                            ? 'There are no programs running at the moment. Please check back soon.'
                            : 'No programs match your search.'}
                    </p>
                ) : (
                    <div className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {filtered.map((program) => (
                            <Card
                                key={program.id}
                                className="flex flex-col rounded-[10px]"
                            >
                                <CardContent className="flex flex-1 flex-col p-5">
                                    <div className="flex items-start justify-between gap-3">
                                        {program.category && (
                                            <span className="rounded-full bg-secondary-orange/10 px-3 py-1 text-xs font-medium text-primary">
                                                {program.category}
                                            </span>
                                        )}
                                        <span
                                            className={`text-xs whitespace-nowrap ${program.is_open ? 'text-muted-foreground' : 'text-destructive'}`}
                                        >
                                            {programAvailabilityLabel(program)}
                                        </span>
                                    </div>

                                    <h2 className="mt-3 text-lg font-semibold text-charcoal-gray">
                                        {program.name}
                                    </h2>
                                    <p className="mt-2 flex-1 text-sm text-muted-foreground">
                                        {program.summary}
                                    </p>

                                    <div className="mt-4 space-y-2 text-sm text-muted-foreground">
                                        {program.age_range && (
                                            <p className="flex items-center gap-2">
                                                <Users className="h-4 w-4 shrink-0" />
                                                {program.age_range}
                                            </p>
                                        )}
                                        {formatProgramDates(program) && (
                                            <p className="flex items-center gap-2">
                                                <CalendarDays className="h-4 w-4 shrink-0" />
                                                {formatProgramDates(program)}
                                            </p>
                                        )}
                                        {program.location && (
                                            <p className="flex items-center gap-2">
                                                <MapPin className="h-4 w-4 shrink-0" />
                                                {program.location}
                                            </p>
                                        )}
                                    </div>

                                    <div className="mt-5 flex items-center justify-between">
                                        <span className="font-semibold text-charcoal-gray">
                                            {formatProgramPrice(program.price)}
                                        </span>
                                        <Button
                                            asChild
                                            size="sm"
                                            className="rounded-[5px]"
                                        >
                                            <Link
                                                href={`/programs/${program.slug}`}
                                            >
                                                View & Register
                                                <ArrowRight className="ml-1 h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </section>
        </>
    );
}

Programs.layout = (page: React.ReactNode) => (
    <PublicLayout>{page}</PublicLayout>
);
