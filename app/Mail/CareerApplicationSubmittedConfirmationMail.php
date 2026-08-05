<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/cats/serializers.py:1563, confirmation sent to the applicant. */
class CareerApplicationSubmittedConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Received',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.career-application-submitted-confirmation');
    }
}
