<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * One-time OAuth authorization so document uploads can write to Google
 * Drive as a real account — see GoogleDriveService for why a service
 * account doesn't work here (no storage quota outside a Shared Drive,
 * which needs Google Workspace).
 */
class GoogleDriveConnectionController extends Controller
{
    public function connect(): RedirectResponse
    {
        $client = $this->client();
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');

        if (blank($code)) {
            return redirect()->route('admin.dashboard')->with('error', 'Google Drive authorization was cancelled or denied.');
        }

        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode((string) $code);

        if (isset($token['error'])) {
            return redirect()->route('admin.dashboard')->with('error', "Google Drive authorization failed: {$token['error_description']}");
        }

        file_put_contents(config('services.google_drive.token_path'), json_encode($token));

        return redirect()->route('admin.dashboard')->with('success', 'Google Drive connected successfully.');
    }

    private function client(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->setRedirectUri(route('admin.google-drive.callback'));
        $client->addScope(GoogleDrive::DRIVE);

        return $client;
    }
}
