<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a candidate moves to onboarding: their portal account has just
 * been created, and the email carries the temporary password plus the list
 * of documents their position requires them to upload before they can be
 * hired. `$password` is null when the email already belonged to an account,
 * in which case the existing password still applies.
 *
 * Reference: cats-backend/cats/serializers.py::_send_login_credentials.
 */
class LoginCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $requiredDocuments
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $email,
        public readonly ?string $password,
        public readonly array $requiredDocuments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your CATS Account – Onboarding Login Credentials');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.login-credentials');
    }
}
