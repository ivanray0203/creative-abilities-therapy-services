<?php

namespace App\Services;

use App\Mail\SessionActivityAdminNotification;
use App\Mail\SessionCancelledMail;
use App\Mail\SessionRescheduledMail;
use App\Mail\SessionScheduledMail;
use App\Models\ScheduleSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Session lifecycle emails, for both the client's parent and the admins.
 *
 * Before this, booking a session was silent on every channel — the parent
 * only found out by logging into the portal. Each event notifies both sides:
 * the parent because it is their child's appointment, the admins because
 * therapists schedule against clinic capacity.
 *
 * Every mailable here is `ShouldQueue`, so these are handed to the queue
 * rather than sent inline — a booking must not block on SMTP. The sends are
 * still wrapped so a queue/transport failure can't turn a saved session into
 * a failed request.
 */
class SessionNotifier
{
    public static function scheduled(ScheduleSession $session): void
    {
        self::dispatch(
            $session,
            fn (): Mailable => new SessionScheduledMail($session),
            fn (): Mailable => new SessionActivityAdminNotification($session, 'scheduled'),
        );
    }

    public static function rescheduled(ScheduleSession $session, ?CarbonInterface $previousStart): void
    {
        self::dispatch(
            $session,
            fn (): Mailable => new SessionRescheduledMail($session, $previousStart),
            fn (): Mailable => new SessionActivityAdminNotification($session, 'rescheduled', $previousStart),
        );
    }

    public static function cancelled(ScheduleSession $session): void
    {
        self::dispatch(
            $session,
            fn (): Mailable => new SessionCancelledMail($session),
            fn (): Mailable => new SessionActivityAdminNotification($session, 'cancelled'),
        );
    }

    /**
     * The parent's inbox: the linked portal account if the intake has been
     * approved into one, otherwise the email captured on the intake. Mirrors
     * InvoiceController::invoiceRecipients() so a parent who receives
     * invoices also receives session mail.
     */
    private static function clientEmail(ScheduleSession $session): ?string
    {
        $client = $session->client;

        if ($client === null) {
            return null;
        }

        return optional($client->user)->email ?? $client->originalIntake?->primary_parent_email;
    }

    /**
     * Active admins who have not opted out via the `session_reminders`
     * preference already exposed on the account settings screen.
     *
     * @return Collection<int, string>
     */
    private static function adminEmails(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where('session_reminders', true)
            ->pluck('email');
    }

    /**
     * @param  callable(): Mailable  $clientMail
     * @param  callable(): Mailable  $adminMail
     */
    private static function dispatch(ScheduleSession $session, callable $clientMail, callable $adminMail): void
    {
        $session->loadMissing(['client.user', 'client.originalIntake', 'therapist', 'service']);

        $clientEmail = self::clientEmail($session);

        if ($clientEmail !== null) {
            self::send($session, $clientEmail, $clientMail());
        }

        $adminEmails = self::adminEmails();

        if ($adminEmails->isNotEmpty()) {
            self::send($session, $adminEmails, $adminMail());
        }
    }

    /**
     * @param  string|Collection<int, string>  $to
     */
    private static function send(ScheduleSession $session, string|Collection $to, Mailable $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $exception) {
            Log::error('Failed to queue a session notification.', [
                'session_id' => $session->id,
                'mailable' => $mailable::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
