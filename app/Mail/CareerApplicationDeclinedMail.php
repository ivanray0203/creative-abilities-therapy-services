<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the candidate when an admin declines their application. */
class CareerApplicationDeclinedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Application',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.career-application-declined');
    }
}
