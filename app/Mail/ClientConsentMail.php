<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once every service on the child's intake is approved. The consent
 * package rides along as a PDF to print and sign, and the button opens the
 * page where the parent uploads the signed copy back.
 */
class ClientConsentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var string base64-encoded PDF bytes — raw binary can't survive the queue's JSON serialization. */
    public readonly string $pdfContents;

    public function __construct(
        public readonly Client $client,
        public readonly string $uploadUrl,
        string $pdfContents = '',
    ) {
        $this->pdfContents = base64_encode($pdfContents);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Please Sign: Therapy Consent for {$this->client->displayName()}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.client-consent');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->pdfContents === '') {
            return [];
        }

        return [
            Attachment::fromData(fn (): string => base64_decode($this->pdfContents), 'CATS_Consent.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
