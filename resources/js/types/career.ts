/**
 * Public-facing Career model shape, matching the `Career` Eloquent model's
 * JSON cast shape (app/Models/Career.php). Unlike cats-frontend's
 * src/types/career.types.ts, the `highlights` field is spelled correctly
 * here (the reference has a typo'd `hightlights`).
 */
/** A titled paragraph — the shape "What We Offer" takes. */
export interface CareerOffer {
    title: string;
    description: string;
}

/** A section whose body is a bullet list, optionally wrapped in prose. */
export interface CareerSection {
    title?: string;
    intro?: string;
    lead_in?: string;
    items: string[];
    closing?: string;
    paragraphs?: string[];
}

export interface CareerDetail {
    /** Opening paragraphs after `about_description`. */
    intro?: string[];
    role_summary?: string;
    responsibilities_lead_in?: string;
    collaboration?: CareerSection;
    qualifications_lead_in?: string;
    qualifications_note?: string;
    offers?: CareerOffer[];
    fscd?: CareerSection | null;
    /** Posting-specific sections, e.g. aide documentation expectations. */
    extras?: CareerSection[];
    contractor?: { title: string; paragraphs: string[] };
    closing_title?: string;
    closing?: string;
}

export interface Career {
    id: number;
    position: string;
    location: string;
    schedule: string;
    contract: string;
    rate: string;
    short_description: string;
    about_description: string;
    responsibilities: string[];
    qualifications: string[];
    skills: string[];
    benefits: string[];
    is_active: boolean;
    sort_order: number;
    due_date: string;
    highlights: string[];
    level: string;
    hours: string;
    required_documents: string[];
    detail: CareerDetail | null;
}
