/**
 * Intake form taxonomy (funding-source service lists, provinces/cities, lead
 * sources, availability options), ported 1:1 from cats-frontend/src/json/intake.tsx.
 * Shared between the Phase 5 intake application form and career application form.
 */

export const medicalConditionsOptions = [
    'Autism Spectrum Disorder (ASD)',
    'ADD/ADHD (Attention Deficit Disorder)',
    'Global Developmental Delay (GDD)',
    'Fetal Alcohol Spectrum Disorder (FASD)',
    'Genetic Disorder',
    'Down Syndrome',
    'Developmental Delay',
    'Physical Disablility',
    'Anxiety/Depression',
    'Speech/Language Disorder',
    'Learning Disability',
    'Sensory Processing Disorder',
    'Other',
];

export const BDSService = [
    'Speech and Language Therapy',
    'Psychological Support',
    'Occupational Therapy',
    'Physiotherapy',
    'Behavioural Consulting',
    'Behavioural Aide Services',
    'Community Aide Services',
    'Respite Aide Services',
];

export const SSService = [
    'Clinical Coordinator',
    'Speech and Language Therapy',
    'Psychological Support',
    'Occupational Therapy',
    'Physiotherapy',
    'Behavioural Consulting',
    'Behavioural Aide Services',
    'Community Aide Services',
    'Respite Aide Services',
];

export const CounselingService = ['Counselling'];

export const InsuranceAndPrivateService = [
    'Speech and Language Therapy',
    'Psychological Support',
    'Counselling',
    'Occupational Therapy',
    'Physiotherapy',
    'Behavioural Consulting',
    'Behavioural Aide Services',
    'Community Aide Services',
    'Respite Aide Services',
];

export const allServices = [
    'Speech and Language Therapy',
    'Psychological Support',
    'Occupational Therapy',
    'Physiotherapy',
    'Behavioural Consulting',
    'Behavioural Aide Services',
    'Community Aide Services',
    'Respite Aide Services',
    'Counselling',
    'Clinical Coordinator',
];

// Full services list shown in the Intake form's "Services Needed" section,
// shown regardless of the selected funding source. Labels are kept identical
// to `allServices` (the team member Specializations list) so a submitted
// intake service can be matched against therapist specializations.
export const IntakeServicesNeeded = [
    'Occupational Therapy',
    'Speech and Language Therapy',
    'Psychological Support',
    'Behavioural Aide Services',
    'Respite Aide Services',
    'Physiotherapy',
    'Behavioural Consulting',
    'Counselling',
    'Community Aide Services',
];

/**
 * Clinical Coordination is only fundable under Specialized Services, so the
 * option is offered on the intake form exclusively when that funding source
 * is selected rather than being listed for everyone.
 */
export const SS_ONLY_SERVICE = 'Clinical Coordinator';

/**
 * Services Needed options for a funding source. Every source sees the base
 * list; SS-FSCD additionally sees the Clinical Coordinator option, placed
 * above Occupational Therapy.
 */
export function intakeServicesFor(fundingSource: string): string[] {
    if (fundingSource !== 'SS-FSCD') {
        return IntakeServicesNeeded;
    }

    const insertAt = IntakeServicesNeeded.indexOf('Occupational Therapy');

    return [
        ...IntakeServicesNeeded.slice(0, insertAt),
        SS_ONLY_SERVICE,
        ...IntakeServicesNeeded.slice(insertAt),
    ];
}

/**
 * Display names for the stored funding-source codes. The three FSCD variants
 * are funded under different programs, so they must stay distinguishable
 * wherever a funding source is shown.
 */
export const FUNDING_SOURCE_LABELS: Record<string, string> = {
    'BDS-FSCD': 'Behavioural/Developmental Support (BDS) - FSCD',
    'SS-FSCD': 'Specialized Services (SS) - FSCD',
    'Counselling-FSCD': 'Counselling - FSCD',
    Insurance: 'Insurance',
    private: 'Private Pay',
};

/** Falls back to the raw stored code so an unmapped source is never blank. */
export function fundingSourceLabel(fundingSource?: string | null): string {
    if (!fundingSource) {
        return '-';
    }

    return FUNDING_SOURCE_LABELS[fundingSource] ?? fundingSource;
}

export const servicesMap: Record<string, string[]> = {
    'BDS-FSCD': BDSService,
    'SS-FSCD': SSService,
    'Counselling-FSCD': CounselingService,
    Insurance: InsuranceAndPrivateService,
    private: InsuranceAndPrivateService,
};

// Availability
export const days = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];

export const times = [
    'Mornings (8am-11am)',
    'Afternoons (12pm-3pm)',
    'Evenings (4pm-7pm)',
];

/**
 * Availability is captured as a day x time-of-day grid: which time-of-day
 * works on which day, keyed by the singular day name in `days`.
 */
export type AvailabilitySlots = Record<string, string[]>;

/** Toggles one grid cell, dropping days that end up with no times selected. */
export function toggleAvailabilitySlot(
    slots: AvailabilitySlots,
    day: string,
    time: string,
): AvailabilitySlots {
    const current = slots[day] ?? [];
    const next = current.includes(time)
        ? current.filter((entry) => entry !== time)
        : [...current, time];

    if (next.length === 0) {
        const { [day]: _removed, ...rest } = slots;

        return rest;
    }

    return { ...slots, [day]: next };
}

/**
 * The days and times the grid implies, in taxonomy order. The server derives
 * and stores the same two lists so existing readers (therapist dashboard, CSV
 * export, ScheduleMatcher) keep working; this mirrors it for the preview.
 */
export function availabilitySummary(slots: AvailabilitySlots): {
    days: string[];
    times: string[];
} {
    const selectedDays = days.filter((day) => (slots[day] ?? []).length > 0);
    const selectedTimes = times.filter((time) =>
        selectedDays.some((day) => (slots[day] ?? []).includes(time)),
    );

    return { days: selectedDays, times: selectedTimes };
}

// Lead source
export const leadSource = [
    'Doctor/Healthcare Provider Referral',
    'School Recommendation',
    'Online Search (Google, etc.)',
    'Family or Family Member',
    'Social Media',
    'Community Event',
    'Other',
];

export const ProvinceCities: Record<string, string[]> = {
    Alberta: [
        'Calgary',
        'Airdrie',
        'Chestermere',
        'Cochrane',
        'Okotoks',
        'Stratmore',
        'High River',
        'Canmore',
        'Banff',
        'Edmonton',
        'Red Deer',
        'Lethbridge',
        'Medicine Hat',
        'Grande Prairie',
        'Other',
    ],
    'British Columbia': [
        'Vancouver',
        'Victoria',
        'Surrey',
        'Burnaby',
        'Richmond',
        'Kelowna',
        'Abbotsford',
    ],
    Manitoba: ['Winnipeg', 'Brandon', 'Steinbach', 'Thompson'],
    'New Brunswick': ['Moncton', 'Saint John', 'Fredericton', 'Dieppe'],
    'Newfoundland and Labrador': [
        'St. John’s',
        'Mount Pearl',
        'Corner Brook',
        'Gander',
    ],
    'Nova Scotia': ['Halifax', 'Sydney', 'Dartmouth', 'Truro'],
    Ontario: [
        'Toronto',
        'Ottawa',
        'Mississauga',
        'Brampton',
        'Hamilton',
        'London',
        'Markham',
        'Vaughan',
        'Kitchener',
        'Windsor',
    ],
    'Prince Edward Island': ['Charlottetown', 'Summerside'],
    Quebec: [
        'Montreal',
        'Quebec City',
        'Laval',
        'Gatineau',
        'Sherbrooke',
        'Trois-Rivières',
    ],
    Saskatchewan: ['Saskatoon', 'Regina', 'Prince Albert', 'Moose Jaw'],
    'Northwest Territories': ['Yellowknife', 'Hay River', 'Inuvik'],
    Nunavut: ['Iqaluit', 'Rankin Inlet', 'Arviat'],
    Yukon: ['Whitehorse', 'Dawson City'],
};

export const Provinces = Object.keys(ProvinceCities);
