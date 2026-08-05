<?php

namespace App\Mail;

use App\Models\Intake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/cats/views.py:145, confirmation sent to the submitting parent. */
class IntakeSubmittedConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Intake $intake) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Intake Form Received',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.intake-submitted-confirmation');
    }
}
