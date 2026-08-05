/**
 * Public-facing Service model shape, matching cats-frontend's
 * src/types/services.types.ts and the `Service` Eloquent model's JSON cast
 * shape (app/Models/Service.php).
 */

export interface AreaOfFocus {
    title: string;
    desc: string;
}

export interface Service {
    id?: number;
    name: string;
    code: string;
    short_description: string;
    description: string;
    duration_minutes: number;
    base_price: number;
    is_active: boolean;
    benefits: string[];
    offerings: string[];
    approaches: string[];
    outcomes: string[];
    ages: string;
    conditions: string;
    signs_to_look_for: string;
    frequency: string;
    location: string;
    tags: string[];
    main_tag: string;
    duration: string;
    description_highlight: string | null;
    area_of_focus: AreaOfFocus[];
    photo?: string;
}
