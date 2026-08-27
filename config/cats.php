<?php

return [
    'name' => env('CATS_NAME', 'Creative Abilities Therapy Services'),
    'phone' => env('CATS_PHONE'),
    'email' => env('CATS_EMAIL'),
    'address' => env('CATS_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Invoice letterhead
    |--------------------------------------------------------------------------
    |
    | The fixed details printed on every invoice PDF
    | (resources/views/pdf/invoice.blade.php). Kept here rather than in the
    | template so a change of address or director does not need a code edit.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Offer letter boilerplate
    |--------------------------------------------------------------------------
    |
    | The terms that read the same on every offer
    | (resources/views/pdf/offer-letter.blade.php). Kept here rather than in
    | the template so wording can change without a code edit. Anything that
    | varies per candidate — position, rate, start date — comes off the
    | application itself.
    |
    | `acceptance_days` is how long a candidate has to sign, in calendar days.
    | It sets both the deadline printed on the letter and the lifetime of the
    | signed link they sign through, so the offer and the link expire together.
    |
    */
    'offer' => [
        'legal_name' => env('CATS_OFFER_LEGAL_NAME', 'Creative Abilities Therapy Services'),
        'engagement_type' => env('CATS_OFFER_ENGAGEMENT_TYPE', 'Independent Contractor'),
        'location' => env('CATS_OFFER_LOCATION', 'Home or Community'),
        'schedule' => env('CATS_OFFER_SCHEDULE', 'Flexible and based on client referrals and your availability.'),
        'reports_to' => env('CATS_OFFER_REPORTS_TO', 'Operations & Program Lead, Creative Abilities Therapy Services'),
        'acceptance_days' => (int) env('CATS_OFFER_ACCEPTANCE_DAYS', 5),
    ],

    'invoice' => [
        'legal_name' => env('CATS_LEGAL_NAME', 'Creative Abilities Therapy Services Inc.'),
        'business_partner_number' => env('CATS_BUSINESS_PARTNER_NUMBER', '0020133765'),
        'street' => env('CATS_INVOICE_STREET', '39 Mahogany Drive SE'),
        'city_line' => env('CATS_INVOICE_CITY_LINE', 'Calgary, Alberta T3M 2K3'),
        'phone' => env('CATS_INVOICE_PHONE', '(587) 433 8780'),
        'website' => env('CATS_INVOICE_WEBSITE', 'creativeabilitiestherapyservices.ca'),
        'tagline' => env('CATS_INVOICE_TAGLINE', 'Empowering Every Child, Embracing Every Ability'),
        'clinical_director' => env('CATS_CLINICAL_DIRECTOR', 'Mary Ann Lerit'),
        /** Optional path under public/ for the director's signature image. */
        'clinical_director_signature' => env('CATS_CLINICAL_DIRECTOR_SIGNATURE'),
    ],
];
