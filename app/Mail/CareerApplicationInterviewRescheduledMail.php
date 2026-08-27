<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when an already-booked interview moves. Carries the previous slot so
 * the candidate can tell which invitation changed, mirroring
 * SessionRescheduledMail.
 */
class CareerApplicationInterviewRescheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly ?string $previousSchedule = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Interview Has Been Rescheduled',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.career-application-interview-rescheduled');
    }
}
