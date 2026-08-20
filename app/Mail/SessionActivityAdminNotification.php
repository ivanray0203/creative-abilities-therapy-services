<?php

namespace App\Mail;

use App\Models\ScheduleSession;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The admin-side counterpart to the three client session mailables. One
 * mailable rather than three because admins get the same operational summary
 * each time — only the verb and subject line differ.
 */
class SessionActivityAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  'scheduled'|'rescheduled'|'cancelled'  $action
     */
    public function __construct(
        public readonly ScheduleSession $session,
        public readonly string $action,
        public readonly ?CarbonInterface $previousStart = null,
    ) {}

    public function envelope(): Envelope
    {
        $client = $this->session->client?->displayName() ?? 'Unknown client';

        return new Envelope(
            subject: "Session {$this->action}: {$client}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.session-activity-admin-notification');
    }
}
