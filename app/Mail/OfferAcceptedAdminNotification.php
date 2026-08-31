<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the admins a candidate has answered their offer, either way. */
class OfferAcceptedAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly bool $declined = false,
    ) {}

    public function envelope(): Envelope
    {
        $name = trim("{$this->application->first_name} {$this->application->last_name}");

        return new Envelope(
            subject: $this->declined
                ? "Offer declined - {$name}"
                : "Offer accepted - {$name}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.offer-accepted-admin-notification');
    }
}
