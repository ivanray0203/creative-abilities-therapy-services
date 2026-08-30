<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HourTrackingReport;
use App\Services\PdfService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 21 — the clinic's hour-tracking sheet, across every therapist.
 *
 * The same report the therapist sees of their own contracts, unfiltered: this
 * is the sheet the office has always kept by hand, one section per service and
 * a row per child's contract. Admin can narrow it to one therapist, which
 * gives exactly what that therapist sees on their own page.
 *
 * Read-only, like the therapist's copy. Hours move when a session is booked,
 * cancelled or edited; the allotment moves when an admin edits the contract.
 */
class HourTrackingController extends Controller
{
    public function __construct(private HourTrackingReport $report) {}

    public function index(Request $request): Response
    {
        [$from, $to, $therapist] = $this->scope($request);

        return Inertia::render('admin/hour-tracking', [
            'report' => $this->report->build($therapist, $from, $to),
            'therapists' => $this->therapistOptions(),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'therapist_id' => $therapist?->id,
            ],
        ]);
    }

    public function pdf(Request $request, PdfService $pdfService): HttpResponse
    {
        [$from, $to, $therapist] = $this->scope($request);

        $contents = $pdfService->hourTracking(
            $therapist,
            $this->report->build($therapist, $from, $to),
            $from,
            $to,
        );

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'inline; filename="hour-tracking-%s-to-%s.pdf"',
                $from->toDateString(),
                $to->toDateString(),
            ),
        ]);
    }

    /**
     * The window and the therapist being reported on. A null therapist is the
     * whole clinic, which is what the page opens on.
     *
     * A reversed range is swapped rather than rejected: the two inputs sit
     * side by side and picking the end date first is the obvious slip.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: User|null}
     */
    private function scope(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'therapist_id' => ['nullable', 'integer'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->startOfYear();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfYear();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $therapist = isset($validated['therapist_id'])
            ? User::query()->whereKey($validated['therapist_id'])->where('role', 'therapist')->first()
            : null;

        return [$from, $to, $therapist];
    }

    /**
     * Every therapist who holds a contract, for the filter. Deliberately not
     * every therapist on staff: a name with nothing behind it only offers the
     * admin an empty sheet.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function therapistOptions(): array
    {
        return User::query()
            ->where('role', 'therapist')
            ->whereHas('serviceContracts')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $therapist): array => [
                'id' => $therapist->id,
                'name' => trim("{$therapist->first_name} {$therapist->last_name}") ?: "Therapist #{$therapist->id}",
            ])
            ->all();
    }
}
