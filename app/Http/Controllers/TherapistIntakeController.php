<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\TeamMember;
use App\Support\ScheduleMatcher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Therapist-facing intake review/decision page (reference:
 * cats-frontend/src/pages/therapist/IntakeDetailPageTherapist.tsx).
 */
class TherapistIntakeController extends Controller
{
    public function show(Request $request, Intake $intake): Response
    {
        $therapistId = $request->user()->id;

        $intake->load(['therapistReviews', 'documents']);

        abort_unless(
            $intake->therapistReviews->contains('therapist_id', $therapistId)
                || $intake->assigned_therapist_id === $therapistId,
            404,
        );

        $teamMember = optional(TeamMember::query()->where('user_id', $therapistId)->first());

        $scheduleMatch = ScheduleMatcher::match(
            $intake->available_days ?? [],
            $intake->preferred_times ?? [],
            $teamMember->availability ?? [],
        );

        $specializations = $teamMember->specializations ?? [];

        $myReviews = $intake->therapistReviews->where('therapist_id', $therapistId);

        // A null-service review means the whole intake was assigned as one
        // (legacy/no-services-listed intakes) — otherwise, only the services
        // actually assigned to this therapist are relevant to them.
        $myServices = $myReviews->contains(fn (IntakeTherapistApproval $review): bool => $review->service === null)
            ? ($intake->services_needed ?? [])
            : $myReviews->pluck('service')->filter()->values()->all();

        $pendingReviews = $myReviews
            ->filter(fn (IntakeTherapistApproval $review): bool => in_array($review->status, ['pending', 'reassign'], true)
                && $review->latestHistoryStatus() === 'sent')
            ->map(fn (IntakeTherapistApproval $review): array => ['id' => $review->id, 'service' => $review->service])
            ->values();

        return Inertia::render('therapist/intake/show', [
            'intake' => $intake,
            'scheduleMatch' => $scheduleMatch,
            'capacity' => [
                'current' => Client::query()->where('primary_therapist_id', $therapistId)->count(),
                'maximum' => $teamMember->maximum_caseload ?? 0,
            ],
            'myServices' => $myServices,
            'specializationMatches' => collect($myServices)
                ->mapWithKeys(fn (string $service): array => [$service => in_array($service, $specializations, true)])
                ->all(),
            'pendingReviews' => $pendingReviews,
        ]);
    }
}
