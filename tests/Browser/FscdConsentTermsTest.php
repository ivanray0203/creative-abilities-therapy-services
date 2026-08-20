<?php

use Pest\Browser\Api\AwaitableWebpage;

/**
 * The three FSCD consents are a funding condition, so the intake form must
 * not let an applicant give them without opening the terms and reading to
 * the end.
 */
function openFscdFundingSection(): AwaitableWebpage
{
    return visit('/intake/apply')
        ->click('Decline')
        ->click('Funding Source')
        ->click('Select a funding')
        // Any FSCD source shows the consents; this one's label has no slash,
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

it('disables the fscd consent checkboxes until their terms are opened', function () {
    openFscdFundingSection()
        ->assertSee('Required FSCD Consents')
        ->assertDisabled('#consentFSCD1')
        ->assertDisabled('#consentFSCD2')
        ->assertDisabled('#consentFSCD3');
});

it('keeps a consent locked while its terms are only partly read', function () {
    openFscdFundingSection()
        ->click('Terms and Conditions')
        ->assertSee('Consent to Communicate with Your FSCD Worker')
        ->assertSee('Scroll to the end to unlock this consent.')
        ->click('Close')
        ->assertDisabled('#consentFSCD1');
});

it('unlocks a consent once its terms are scrolled to the end', function () {
    $page = openFscdFundingSection()
        ->click('Terms and Conditions')
        ->assertSee('Consent to Communicate with Your FSCD Worker');

    $page->script(<<<'JS'
        const terms = document.getElementById('fscd-terms-scroll');
        terms.scrollTop = terms.scrollHeight;
        terms.dispatchEvent(new Event('scroll'));
    JS);

    $page->assertSee('You can now check this consent.')
        ->click('Close')
        ->assertEnabled('#consentFSCD1')
        // Reading one document must not unlock the other two.
        ->assertDisabled('#consentFSCD2')
        ->assertDisabled('#consentFSCD3')
        ->click('#consentFSCD1')
        ->assertChecked('#consentFSCD1');
});
