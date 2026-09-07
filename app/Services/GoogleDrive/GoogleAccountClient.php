<?php

namespace App\Services\GoogleDrive;

use Google\Client as GoogleClient;

/**
 * Builds an authorized Google API client from the OAuth token stored by
 * Admin\GoogleDriveConnectionController, refreshing it on disk when it has
 * expired. Drive uploads and Google Meet links both authenticate as the
 * same connected account, so the token handling lives here once.
 */
class GoogleAccountClient
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $tokenPath,
    ) {}

    /** True once the one-time admin connection has stored a token. */
    public function isConnected(): bool
    {
        return is_file($this->tokenPath);
    }

    /**
     * @param  array<int, string>  $scopes
     */
    public function make(array $scopes): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setScopes($scopes);
        $client->setAccessType('offline');

        $contents = is_file($this->tokenPath) ? file_get_contents($this->tokenPath) : false;

        if ($contents === false) {
            throw new \RuntimeException("Unable to read the Google token at [{$this->tokenPath}].");
        }

        $token = json_decode($contents, true);

        // The Google client throws "Invalid token format" on anything that is
        // not a token array, which says nothing about what to do next.
        if (! is_array($token) || ! isset($token['access_token'])) {
            throw new \RuntimeException("The Google token at [{$this->tokenPath}] is not a usable token — reconnect via the admin Google Drive settings.");
        }

        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken() ?? $token['refresh_token'] ?? null;

            if ($refreshToken === null) {
                throw new \RuntimeException("Google token at [{$this->tokenPath}] has no refresh token — reconnect via the admin Google Drive settings.");
            }

            $refreshed = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            // A refused refresh comes back as an `error`/`error_description`
            // pair rather than a token. Writing that over the stored
            // credential is what turns one expired token into a permanently
            // broken connection, so keep the file as it is and say why.
            if (! isset($refreshed['access_token'])) {
                throw new \RuntimeException(sprintf(
                    'Google refused to refresh the token (%s) — reconnect via the admin Google Drive settings.',
                    $refreshed['error_description'] ?? $refreshed['error'] ?? 'no access token was returned',
                ));
            }

            $refreshed['refresh_token'] ??= $refreshToken;

            file_put_contents($this->tokenPath, json_encode($refreshed));
            $client->setAccessToken($refreshed);
        }

        return $client;
    }
}
