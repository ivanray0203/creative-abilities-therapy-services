import type { ProgramSummary } from '@/types/program';

/** `$320.00`, or "Free" when the program carries no price. */
export function formatProgramPrice(price: string | null): string {
    const numeric = Number(price ?? 0);

    return numeric > 0 ? `$${numeric.toFixed(2)}` : 'Free';
}

/**
 * `5 January 2027`, from the `YYYY-MM-DD` the controller sends. Split on the
 * parts rather than `new Date(value)`, which reads a bare date as UTC
 * midnight and shows the previous day west of UTC.
 */
export function formatProgramDate(value: string | null): string | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

/**
 * The run of a program as one line, tolerating a missing end date. Takes
 * just the two dates so the admin list, which carries a different shape,
 * can reuse it.
 */
export function formatProgramDates(
    program: Pick<ProgramSummary, 'starts_on' | 'ends_on'>,
): string | null {
    const starts = formatProgramDate(program.starts_on);
    const ends = formatProgramDate(program.ends_on);

    if (!starts) {
        return null;
    }

    if (!ends || ends === starts) {
        return starts;
    }

    return `${starts} - ${ends}`;
}

/**
 * The short status line shown on a card and above the form. Capacity is only
 * mentioned once it is genuinely running out, so an early listing doesn't
 * read as pressure selling.
 */
export function programAvailabilityLabel(
    program: Pick<ProgramSummary, 'is_open' | 'places_left'>,
): string {
    if (!program.is_open) {
        return 'Registration closed';
    }

    if (program.places_left === null) {
        return 'Open for registration';
    }

    if (program.places_left <= 3) {
        return `${program.places_left} place${program.places_left === 1 ? '' : 's'} left`;
    }

    return 'Open for registration';
}
