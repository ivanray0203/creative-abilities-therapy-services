<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the admins a parent has uploaded their signed consent, or replaced
 * an earlier upload with a corrected one.
 */
class ClientConsentSignedAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Client $client,
        public readonly bool $replaced = false,
    ) {}

    public function envelope(): Envelope
    {
        $heading = $this->replaced ? 'Signed Consent Replaced' : 'Signed Consent Returned';

        return new Envelope(
            subject: "{$heading}: {$this->client->displayName()}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.client-consent-signed-admin-notification');
    }
}
