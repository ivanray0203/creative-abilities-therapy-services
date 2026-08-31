<?php

namespace App\Mail;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the admins a parent has signed and returned an aide's time sheet. */
class TimesheetSignedAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Timesheet $timesheet) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Signed Timesheet Returned: {$this->timesheet->timesheet_number}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.timesheet-signed-admin-notification');
    }
}
