/**
 * Public Programs pages, matching the arrays built by
 * Public\ProgramController (a flattened view of `Program`, not the model).
 */

export interface ProgramSummary {
    id: number;
    slug: string;
    name: string;
    category: string | null;
    summary: string;
    age_range: string | null;
    schedule: string | null;
    location: string | null;
    /** Decimal string, or null for a free program. */
    price: string | null;
    starts_on: string | null;
    ends_on: string | null;
    registration_closes_on: string | null;
    capacity: number | null;
    /** Null when the program takes an open number of participants. */
    places_left: number | null;
    is_open: boolean;
}

export interface ProgramDetail extends ProgramSummary {
    description: string;
    highlights: string[];
}
