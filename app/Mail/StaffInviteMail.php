<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/accounts/serializers.py::RegisterSerializer.create(), sent to a newly registered admin/staff user with their generated password. */
class StaffInviteMail extends Mailable implements ShouldQueue
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
            subject: 'Your Creative Abilities Therapy Account',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.staff-invite');
    }
}
