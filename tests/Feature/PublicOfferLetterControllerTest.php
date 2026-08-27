<?php

use App\Mail\OfferAcceptedAdminNotification;
use App\Models\Application;
use App\Models\User;
use App\Services\OfferLetterService;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/** A 1x1 PNG, the smallest thing that clears the signature validator. */
function signaturePng(): string
{
    return 'data:image/png;base64,'.base64_encode(base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));
}

function signedShowUrl(Application $application): string
{
    return app(OfferLetterService::class)->signedUrl($application);
}

function signedAcceptUrl(Application $application): string
{
    return app(OfferLetterService::class)->signedUrl($application, 'public.offer.accept');
}

test('the offer page needs a valid signature', function () {
    $application = Application::factory()->offerSent()->create();

    // The bare URL, with no signature on it at all.
    $this->get("/offer/{$application->id}")->assertForbidden();

    // A signature minted for someone else's offer.
    $other = Application::factory()->offerSent()->create();
    $borrowed = str_replace("/offer/{$other->id}", "/offer/{$application->id}", signedShowUrl($other));
    $this->get($borrowed)->assertForbidden();

    $this->get(signedShowUrl($application))->assertOk();
});

test('a candidate reads their offer without an account', function () {
    $application = Application::factory()->offerSent()->create([
        'first_name' => 'Bryan',
        'last_name' => 'Lerit',
        'position_applied' => 'Behavioural Aide',
        'hourly_rate' => 20.28,
    ]);

    $this->get(signedShowUrl($application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/offer-letter')
            ->where('state', 'open')
            ->where('application.full_name', 'Bryan Lerit')
            ->where('application.position_applied', 'Behavioural Aide')
            ->has('acceptUrl')
            ->has('declineUrl')
            // Internal hiring data must not ride along on a shareable link.
            ->missing('application.internal_notes')
            ->missing('application.candidate_rating')
            ->missing('application.references')
        );

    expect(auth()->check())->toBeFalse();
});

test('signing files the signed copy, stamps acceptance and tells the admins', function () {
    Mail::fake();
    Storage::fake('public');

    User::factory()->create(['role' => 'admin', 'is_active' => true, 'new_applications' => true]);
    $application = Application::factory()->offerSent()->create();

    $this->post(signedAcceptUrl($application), ['signature' => signaturePng()])
        ->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->offer_accepted_at)->not->toBeNull();
    expect($application->signed_offer_letter)->not->toBeNull();
    expect($application->hasSignedOffer())->toBeTrue();
    // Signing does not hire anybody — that is still the admin's call.
    expect($application->application_status)->toBe('offer_sent');
    expect($application->hired)->toBeFalse();
    expect(User::where('email', $application->email)->exists())->toBeFalse();

    Mail::assertQueued(
        OfferAcceptedAdminNotification::class,
        fn (OfferAcceptedAdminNotification $mail): bool => $mail->declined === false
    );
});

test('a signature that is not a PNG is rejected', function () {
    Storage::fake('public');

    $application = Application::factory()->offerSent()->create();

    $this->post(signedAcceptUrl($application), ['signature' => 'data:image/svg+xml;base64,PHN2Zy8+'])
        ->assertSessionHasErrors('signature');

    // A PNG data URI wrapping something that is not a PNG.
    $this->post(signedAcceptUrl($application), ['signature' => 'data:image/png;base64,'.base64_encode('<svg/>')])
        ->assertSessionHasErrors('signature');

    expect($application->refresh()->offer_accepted_at)->toBeNull();
});

test('an offer cannot be signed twice', function () {
    Storage::fake('public');

    $application = Application::factory()->offerSigned()->create();

    $this->post(signedAcceptUrl($application), ['signature' => signaturePng()])
        ->assertSessionHas('error');

    $this->get(signedShowUrl($application))
        ->assertInertia(fn ($page) => $page
            ->where('state', 'accepted')
            ->where('acceptUrl', null)
        );
});

test('an expired offer can no longer be signed', function () {
    Storage::fake('public');

    $application = Application::factory()->offerExpired()->create();

    // The URL's own lifetime is the offer's deadline, so the signature has
    // lapsed too — the middleware turns it away before the controller runs.
    $this->get(signedShowUrl($application))->assertForbidden();

    // Even a link that somehow outlived the offer is refused on state.
    $stillValid = URL::temporarySignedRoute(
        'public.offer.accept',
        now()->addDay(),
        ['application' => $application->id],
    );

    $this->post($stillValid, ['signature' => signaturePng()])->assertSessionHas('error');

    expect($application->refresh()->offer_accepted_at)->toBeNull();
});

test('a candidate can decline their offer', function () {
    Mail::fake();

    User::factory()->create(['role' => 'admin', 'is_active' => true, 'new_applications' => true]);
    $application = Application::factory()->offerSent()->create();

    $declineUrl = app(OfferLetterService::class)->signedUrl($application, 'public.offer.decline');

    $this->post($declineUrl)->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->offer_declined_at)->not->toBeNull();
    expect($application->application_status)->toBe('declined');
    expect($application->declined)->toBeTrue();

    Mail::assertQueued(
        OfferAcceptedAdminNotification::class,
        fn (OfferAcceptedAdminNotification $mail): bool => $mail->declined === true
    );
});

test('the offer letter renders with the terms from the reference letter', function () {
    $application = Application::factory()->offerSent()->create([
        'first_name' => 'Bryan',
        'last_name' => 'Lerit',
        'position_applied' => 'Behavioural Aide',
        'hourly_rate' => 20.28,
        'street_address' => '39 Mahogany Drive SE',
        'city' => 'Calgary',
        'province' => 'Alberta',
        'zip_code' => 'T3M2K3',
    ]);

    $pdf = app(PdfService::class)->offerLetter($application);

    expect($pdf)->toStartWith('%PDF');
    expect(strlen($pdf))->toBeGreaterThan(1000);
});
