<?php

use App\Support\OnboardingDocuments;

test('clinicians hand in every standard document', function () {
    expect(OnboardingDocuments::forPosition('Occupational Therapist (OT)'))->toBe([
        "Driver's License",
        'Criminal Record / Vulnerable Sector Check',
        'Void Cheque / Direct Deposit Information',
        'Professional License and Certification',
        'Professional Liability Insurance',
    ]);
});

test('aides skip the professional license and liability insurance', function (string $position) {
    expect(OnboardingDocuments::forPosition($position))->toBe([
        "Driver's License",
        'Criminal Record / Vulnerable Sector Check',
        'Void Cheque / Direct Deposit Information',
    ]);
})->with(['Behavioural & Developmental Aide', 'Community & Respite Aide', ' behaviour aide ']);

test('a position\'s own documents follow the standard ones without repeats', function () {
    expect(OnboardingDocuments::forPosition('Psychologist', ['First Aid Certificate', "Driver's License"]))->toBe([
        "Driver's License",
        'Criminal Record / Vulnerable Sector Check',
        'Void Cheque / Direct Deposit Information',
        'Professional License and Certification',
        'Professional Liability Insurance',
        'First Aid Certificate',
    ]);
});
