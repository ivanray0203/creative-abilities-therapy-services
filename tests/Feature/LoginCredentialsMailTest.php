<?php

use App\Mail\LoginCredentialsMail;
use App\Support\OnboardingDocuments;

test('the onboarding email lists a clinician\'s documents, license and insurance included', function () {
    $mail = new LoginCredentialsMail('Jamie', 'jamie@example.com', 'secret1234', OnboardingDocuments::forPosition('Occupational Therapist (OT)'));

    $mail->assertSeeInOrderInText([
        'upload and submit each of the following',
        "Driver's License",
        'Criminal Record / Vulnerable Sector Check',
        'Void Cheque / Direct Deposit Information',
        'Professional License and Certification',
        'Professional Liability Insurance',
    ]);
});

test('the onboarding email leaves the license and insurance off an aide\'s list', function () {
    $mail = new LoginCredentialsMail('Jamie', 'jamie@example.com', 'secret1234', OnboardingDocuments::forPosition('Behavioural & Developmental Aide'));

    $mail->assertSeeInOrderInText(["Driver's License", 'Criminal Record / Vulnerable Sector Check', 'Void Cheque / Direct Deposit Information']);
    $mail->assertDontSeeInText('Professional License and Certification');
    $mail->assertDontSeeInText('Professional Liability Insurance');
});

test('the onboarding email lists each document once', function () {
    $mail = new LoginCredentialsMail('Jamie', 'jamie@example.com', null, [
        'Professional Liability Insurance',
        'First Aid Certificate',
        'Professional Liability Insurance',
    ]);

    expect($mail->documents())->toBe(['Professional Liability Insurance', 'First Aid Certificate'])
        ->and(substr_count($mail->render(), 'Professional Liability Insurance'))->toBe(1);
});
