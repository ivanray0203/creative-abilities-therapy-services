<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/cats/services.py::promote_intake_to_client's welcome email, sent when a brand new parent account is created on intake approval. */
class WelcomeClientAccountMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firstName,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to CATS – Your Client Account is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.welcome-client-account');
    }

    /**
     * The portal walkthrough video, when the configured file exists.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $path = (string) config('cats.account_walkthrough_video');

        if ($path === '' || ! is_file($path)) {
            return [];
        }

        return [
            Attachment::fromPath($path)
                ->as('Demo-Video-Watch-Before-Logging-In.mp4')
                ->withMime('video/mp4'),
        ];
    }
}
