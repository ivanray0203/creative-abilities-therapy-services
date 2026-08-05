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
    'Clinical Coordination',
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
    'Clinical Coordination',
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
    'Afternoon (1pm-3pm)',
    'Evenings (4pm-7pm)',
];

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
