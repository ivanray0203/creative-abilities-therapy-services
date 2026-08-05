/**
 * Public-facing Career model shape, matching the `Career` Eloquent model's
 * JSON cast shape (app/Models/Career.php). Unlike cats-frontend's
 * src/types/career.types.ts, the `highlights` field is spelled correctly
 * here (the reference has a typo'd `hightlights`).
 */
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
    due_date: string;
    highlights: string[];
    level: string;
    hours: string;
    required_documents: string[];
}
