<?php

namespace App\Mail;

use App\Models\ScheduleSession;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the client's parent when an existing session moves. Carries the
 * previous start so the parent can tell which appointment changed without
 * cross-referencing their own calendar.
 */
class SessionRescheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ScheduleSession $session,
        public readonly ?CarbonInterface $previousStart = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Session Has Been Rescheduled',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.session-rescheduled');
    }
}
