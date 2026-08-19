<?php

namespace App\Mail;

use App\Models\ScheduleSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the client's parent when a therapist or admin books a new session. */
class SessionScheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ScheduleSession $session) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Session Has Been Scheduled',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.session-scheduled');
    }
}
