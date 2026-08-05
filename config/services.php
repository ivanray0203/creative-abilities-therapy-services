<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase 15 — External Integrations (Google Drive, Mailcow)
    |--------------------------------------------------------------------------
    |
    | These keep the reference app's third-party services (not a Laravel-native
    | substitute). Left unset in local/test, document uploads transparently
    | fall back to local disk storage (GoogleDriveService binding). Outbound
    | mail is plain SMTP (config/mail.php) through the Mailcow-provisioned
    | mailbox — no separate Gmail API transport.
    */

    'google_drive' => [
        'credentials_path' => env('GOOGLE_DRIVE_CREDENTIALS_PATH'),
        'shared_drive_id' => env('SHARED_DRIVE_ID'),
    ],

    'mailcow' => [
        'api_url' => env('MAILCOW_API_URL', 'https://mail.creativeabilitiestherapyservices.ca/api/v1'),
        'api_key' => env('MAILCOW_API_KEY'),
        'default_domain' => env('MAILCOW_DEFAULT_DOMAIN', 'creativeabilitiestherapyservices.ca'),
    ],

];
