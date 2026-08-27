<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignOfferLetterRequest;
use App\Models\Application;
use App\Services\AuditLogger;
use App\Services\OfferLetterService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where a candidate reads and signs their offer.
 *
 * Unauthenticated by design: the account is created by the hire, which cannot
 * happen until this signature is on file. The `signed` middleware on the
 * routes is what stands in for a login, and the signature expires with the
 * offer itself.
 */
class OfferLetterController extends Controller
{
    public function show(Application $application, OfferLetterService $offers): Response
    {
        $state = $this->state($application);

        return Inertia::render('public/offer-letter', [
            'application' => $this->candidateView($application),
            'state' => $state,
            // A signature covers the whole URL, so the one that got the
            // candidate here does not validate on the accept/decline paths.
            'acceptUrl' => $state === 'open' ? $offers->signedUrl($application, 'public.offer.accept') : null,
            'declineUrl' => $state === 'open' ? $offers->signedUrl($application, 'public.offer.decline') : null,
        ]);
    }

    public function accept(SignOfferLetterRequest $request, Application $application, OfferLetterService $offers): RedirectResponse
    {
        if ($this->state($application) !== 'open') {
            return back()->with('error', 'This offer can no longer be signed.');
        }

        if (! $offers->accept($application, $request->validated()['signature'])) {
            return back()->with('error', 'We could not file your signed offer letter. Please try again.');
        }

        AuditLogger::log(
            'Offer accepted',
            'Applications',
            "Application #{$application->id} ({$application->reference_number}) accepted the offer",
            'success',
            $application->email,
        );

        return back()->with('success', 'Thank you — your signed offer letter has been sent.');
    }

    public function decline(Application $application, OfferLetterService $offers): RedirectResponse
    {
        if ($this->state($application) !== 'open') {
            return back()->with('error', 'This offer can no longer be answered.');
        }

        $offers->decline($application);

        AuditLogger::log(
            'Offer declined',
            'Applications',
            "Application #{$application->id} ({$application->reference_number}) declined the offer",
            'warning',
            $application->email,
        );

        return back()->with('success', 'Thank you for letting us know.');
    }

    /**
     * What the page should render: the letter with a signature pad, or a
     * closed state explaining why it cannot be signed.
     */
    private function state(Application $application): string
    {
        return match (true) {
            $application->offer_accepted_at !== null => 'accepted',
            $application->offer_declined_at !== null => 'declined',
            $application->offer_sent_at === null => 'withdrawn',
            $application->offerHasExpired() => 'expired',
            default => 'open',
        };
    }

    /**
     * Only what the letter itself shows. The application row carries internal
     * notes, ratings and referee contact details, none of which belong in a
     * payload served behind a shareable link.
     *
     * @return array<string, mixed>
     */
    private function candidateView(Application $application): array
    {
        return [
            'id' => $application->id,
            'first_name' => $application->first_name,
            'full_name' => trim("{$application->first_name} {$application->last_name}"),
            'position_applied' => $application->position_applied,
            'hourly_rate' => $application->hourly_rate,
            'reference_number' => $application->reference_number,
            'preferred_start_date' => $application->preferred_start_date?->toDateString(),
            'offer_sent_at' => $application->offer_sent_at?->toIso8601String(),
            'offer_expires_at' => $application->offer_expires_at?->toIso8601String(),
            'street_address' => $application->street_address,
            'address_line_2' => $application->address_line_2,
            'city' => $application->city,
            'province' => $application->province,
            'zip_code' => $application->zip_code,
        ];
    }
}
