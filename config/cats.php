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
