<?php

namespace App\Mail;

use App\Models\Intake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Reference: cats-backend/cats/views.py:172, notifies admin of a new intake submission. */
class IntakeSubmittedAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Intake $intake) {}

    public function envelope(): Envelope
    {
        Log::info('IntakeSubmittedAdminNotification building for delivery.', [
            'intake_id' => $this->intake->id,
            'reference_number' => $this->intake->reference_number,
            'mailer' => config('mail.default'),
        ]);

        return new Envelope(
            subject: 'New Intake Form Submission',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.intake-submitted-admin-notification');
    }
}
