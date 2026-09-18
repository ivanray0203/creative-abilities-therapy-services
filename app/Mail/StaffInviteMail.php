<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
