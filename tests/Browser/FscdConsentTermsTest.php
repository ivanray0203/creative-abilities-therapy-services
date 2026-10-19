<?php

use Pest\Browser\Api\AwaitableWebpage;

/**
 * An FSCD funding source asks for one consent — communicating with the FSCD
 * worker — which the applicant can give straight away; the terms are there
 * to review, not a gate.
 */
function openFscdFundingSection(): AwaitableWebpage
{
    return visit('/intake/apply')
        ->click('Decline')
        ->click('Funding Source')
        ->click('Select a funding')
        // Any FSCD source shows the consent; this one's label has no slash,
        // which the browser plugin would parse as a CSS selector token.
        ->click('Counselling - FSCD');
}

it('offers the clinical coordinator service only under ss-fscd funding', function () {
    $page = visit('/intake/apply')
        ->click('Decline')
        ->click('Services Needed')
        ->assertDontSee('Clinical Coordinator');

    $page->click('Funding Source')
        ->click('Select a funding')
        // Selected by id: the label's parentheses are parsed as CSS tokens.
        ->click('#funding-ss')
        ->assertSee('Clinical Coordinator');
});

it('shows only the fscd worker communication consent', function () {
    openFscdFundingSection()
        ->assertSee('Required FSCD Consent')
        ->assertPresent('#consentFSCD1')
        ->assertNotPresent('#consentFSCD2')
        ->assertNotPresent('#consentFSCD3');
});

it('lets the consent be checked without opening its terms', function () {
    openFscdFundingSection()
        ->assertEnabled('#consentFSCD1')
        ->click('#consentFSCD1')
        ->assertChecked('#consentFSCD1');
});

it('opens the terms for review from the consent label', function () {
    openFscdFundingSection()
        ->click('Terms and Conditions')
        ->assertSee('Consent to Communicate with Your FSCD Worker')
        ->assertSee('End of terms')
        ->click('Close')
        ->assertEnabled('#consentFSCD1');
});
