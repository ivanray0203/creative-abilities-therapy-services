<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
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
}
