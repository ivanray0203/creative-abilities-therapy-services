<?php

namespace App\Mail;

use App\Models\ProgramRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the admins a family has signed a child up for a program. */
class ProgramRegistrationAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ProgramRegistration $registration) {}

    public function envelope(): Envelope
    {
        $program = optional($this->registration->program)->name ?? 'a program';

        return new Envelope(
            subject: "New Program Registration: {$program}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.program-registration-admin-notification');
    }
}
