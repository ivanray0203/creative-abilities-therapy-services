<?php

namespace App\Mail;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells a parent their aide's time sheet is waiting for their signature. */
class TimesheetReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Timesheet $timesheet) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Timesheet {$this->timesheet->timesheet_number} — signature needed",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.timesheet-ready');
    }
}
