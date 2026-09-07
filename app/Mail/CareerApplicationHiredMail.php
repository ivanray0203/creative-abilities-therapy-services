<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the candidate once an admin has reviewed their onboarding documents and hired them. */
class CareerApplicationHiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Team – You Have Been Hired',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.career-application-hired');
    }
}
