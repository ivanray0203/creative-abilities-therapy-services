<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSignedConsentRequest;
use App\Models\Client;
use App\Services\AuditLogger;
use App\Services\ClientConsentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Where a parent downloads the consent package, signs it offline, and
 * uploads the signed copy back.
 *
 * Reached from the emailed link, so the `signed` middleware on the routes
 * stands in for a login — the same arrangement as the offer letter.
 */
class ClientConsentController extends Controller
{
    public function show(Client $client, ClientConsentService $consents): Response
    {
        $client->loadMissing('originalIntake');

        return Inertia::render('public/client-consent', [
            'consent' => [
                'child_name' => $client->displayName(),
                'parent_name' => $client->originalIntake?->primary_parent_name,
                'reference_number' => $client->originalIntake?->reference_number,
                'signed_at' => $client->consent_signed_at?->toIso8601String(),
            ],
            // A signature covers the whole URL, so the one that got the parent
            // here does not validate on the download or upload paths.
            'downloadUrl' => $consents->signedUrl($client, 'public.consent.download'),
            // Offered after signing too, so a parent who sent the wrong file
            // can replace it while their link is still valid.
            'uploadUrl' => $consents->signedUrl($client, 'public.consent.upload'),
        ]);
    }

    public function download(Client $client, ClientConsentService $consents): HttpResponse
    {
        return response($consents->pdf($client), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="CATS_Consent.pdf"',
        ]);
    }

    public function upload(UploadSignedConsentRequest $request, Client $client, ClientConsentService $consents): RedirectResponse
    {
        $replacing = $client->consent_signed_at !== null;

        $consents->receiveSigned($client, $request->file('files'));

        AuditLogger::log(
            $replacing ? 'Signed consent replaced' : 'Signed consent uploaded',
            'Clients',
            $replacing
                ? "Parent replaced the signed consent for client #{$client->id}"
                : "Parent uploaded the signed consent for client #{$client->id}",
            'success',
            $client->originalIntake?->primary_parent_email,
        );

        return back()->with('success', $replacing
            ? 'Thank you — your corrected signed consent has replaced the earlier upload.'
            : 'Thank you — your signed consent has been received.');
    }
}
