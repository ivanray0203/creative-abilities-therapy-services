<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/cats/serializers.py::_send_offer_letter, sent when an application transitions to "hired". */
class OfferLetterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var string base64-encoded PDF bytes — raw binary can't survive the queue's JSON serialization. */
    public readonly string $pdfContents;

    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $position,
        string $pdfContents,
    ) {
        $this->pdfContents = base64_encode($pdfContents);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Offer Letter - {$this->position} Position",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.offer-letter');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => base64_decode($this->pdfContents), 'Offer_Letter.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
