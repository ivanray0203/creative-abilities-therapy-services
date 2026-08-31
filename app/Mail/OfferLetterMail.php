<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The offer letter, sent when an application moves to `offer_sent`.
 *
 * Carries a link to sign rather than asking the candidate to print and scan:
 * they have no account yet, so the link is a temporary signed URL that lapses
 * with the offer. The unsigned PDF rides along as an attachment so they have
 * a copy to read or keep regardless.
 */
class OfferLetterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var string base64-encoded PDF bytes — raw binary can't survive the queue's JSON serialization. */
    public readonly string $pdfContents;

    public function __construct(
        public readonly Application $application,
        public readonly string $signingUrl,
        string $pdfContents = '',
    ) {
        $this->pdfContents = base64_encode($pdfContents);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Offer Letter - {$this->application->position_applied}",
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
        if ($this->pdfContents === '') {
            return [];
        }

        return [
            Attachment::fromData(fn (): string => base64_decode($this->pdfContents), 'Offer_Letter.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
