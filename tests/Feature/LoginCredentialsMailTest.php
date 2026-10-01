<?php

use App\Mail\LoginCredentialsMail;

test('the onboarding email lists the standard documents every new hire must upload', function () {
    $mail = new LoginCredentialsMail('Jamie', 'jamie@example.com', 'secret1234');

    $mail->assertSeeInOrderInText([
        'upload and submit each of the following',
        "Driver's License",
        'Criminal Record / Vulnerable Sector Check',
        'Professional License and Certification',
        'Professional Liability Insurance',
    ]);
});

test('the onboarding email adds the position documents after the standard ones without repeats', function () {
    $mail = new LoginCredentialsMail('Jamie', 'jamie@example.com', null, [
        'Professional Liability Insurance',
        'First Aid Certificate',
    ]);

    expect($mail->documents())->toBe([
        ...LoginCredentialsMail::STANDARD_DOCUMENTS,
        'First Aid Certificate',
    ]);

    $mail->assertSeeInOrderInText(['Professional Liability Insurance', 'First Aid Certificate']);
    expect(substr_count($mail->render(), 'Professional Liability Insurance'))->toBe(1);
});
