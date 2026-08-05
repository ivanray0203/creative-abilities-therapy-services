<?php

namespace App\Mail;

use App\Models\Intake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Reference: cats-backend/cats/util.py::email_send_to_therapist, sent when an intake is sent to a therapist for review. */
class IntakeAssignedToTherapistMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $services  Service names assigned to this therapist in this batch; empty for a whole-intake assignment.
     */
    public function __construct(
        public readonly string $therapistFirstName,
        public readonly Intake $intake,
        public readonly array $services = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Intake Assigned for Review',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.intake-assigned-to-therapist');
    }
}
