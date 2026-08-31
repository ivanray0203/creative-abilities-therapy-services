<?php

namespace App\Services;

use App\Mail\CareerApplicationDeclinedMail;
use App\Mail\CareerApplicationInterviewRescheduledMail;
use App\Mail\CareerApplicationInterviewScheduledMail;
use App\Mail\CareerApplicationUnderReviewMail;
use App\Models\Application;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Candidate-facing emails for the hiring pipeline.
 *
 * Before this, an applicant heard nothing between the submission receipt and
 * an offer letter — interviews were booked and applications declined in
 * silence. Every pipeline transition short of the hire now reaches the
 * candidate; the hire keeps its own offer letter and credentials mail in
 * ApplicationController.
 *
 * Mirrors SessionNotifier: each mailable is `ShouldQueue`, and each send is
 * wrapped so a queue or transport failure cannot turn a saved status change
 * into a failed request.
 */
class ApplicationNotifier
{
    public static function underReview(Application $application): void
    {
        self::send($application, new CareerApplicationUnderReviewMail($application));
    }

    public static function interviewScheduled(Application $application): void
    {
        self::send($application, new CareerApplicationInterviewScheduledMail($application));
    }

    public static function interviewRescheduled(Application $application, ?string $previousSchedule): void
    {
        self::send($application, new CareerApplicationInterviewRescheduledMail($application, $previousSchedule));
    }

    public static function declined(Application $application): void
    {
        self::send($application, new CareerApplicationDeclinedMail($application));
    }

    private static function send(Application $application, Mailable $mailable): void
    {
        if (blank($application->email)) {
            return;
        }

        try {
            Mail::to($application->email)->send($mailable);
        } catch (\Throwable $exception) {
            Log::error('Failed to queue a career application notification.', [
                'application_id' => $application->id,
                'mailable' => $mailable::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
