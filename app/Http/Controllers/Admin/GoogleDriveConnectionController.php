<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        if (blank(config('services.google_drive.client_id')) || blank(config('services.google_drive.client_secret'))) {
            return $this->fail('Google Drive is not configured: set GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_CLIENT_SECRET.');
        }

        $client = $this->client();
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        if (filled($error = $request->query('error'))) {
            return $this->fail("Google Drive authorization was denied by Google: {$error}.");
        }

        $code = $request->query('code');

        if (blank($code)) {
            return $this->fail('Google Drive authorization did not start. Begin at the "Connect Google Drive" link rather than opening the callback URL directly.');
        }

        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode((string) $code);

        if (isset($token['error'])) {
            $reason = $token['error_description'] ?? $token['error'];

            return $this->fail("Google Drive authorization failed: {$reason}");
        }

        if (blank($token['refresh_token'] ?? null)) {
            return $this->fail('Google returned no refresh token. Revoke this app under your Google account\'s third-party access, then connect again.');
        }

        $path = config('services.google_drive.token_path');

        if (! is_dir($directory = dirname($path))) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($path, json_encode($token)) === false) {
            return $this->fail("Google Drive connected, but the token could not be saved to {$path}. Check the directory is writable.");
        }

        return redirect()->route('admin.dashboard')->with('success', 'Google Drive connected successfully.');
    }

    private function fail(string $message): RedirectResponse
    {
        Log::error('Google Drive authorization failed.', ['reason' => $message]);

        return redirect()->route('admin.dashboard')->with('error', $message);
    }

    private function client(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->setRedirectUri(route('admin.google-drive.callback'));
        // Drive for document uploads; Calendar so interview bookings can
        // create Google Meet links on the connected account's calendar. An
        // account connected before Calendar was added has to reconnect once.
        $client->addScope(GoogleDrive::DRIVE);
        $client->addScope(GoogleCalendar::CALENDAR_EVENTS);

        return $client;
    }
}
