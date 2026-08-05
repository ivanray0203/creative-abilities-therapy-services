<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/cats/serializers.py:1592, notifies admin of a new career application. */
class CareerApplicationAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Career Application Received',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.career-application-admin-notification');
    }
}
