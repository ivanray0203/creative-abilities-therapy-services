<?php

namespace App\Http\Controllers;

use App\Services\HourTrackingReport;
use App\Services\PdfService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 21 — a therapist's own hour-tracking sheet.
 *
 * The clinic keeps this by hand in a spreadsheet: every contract a therapist
 * is authorized on, the hours delivered each month, and what is left. This is
 * the same sheet read straight off the contracts and the session ledger, so
 * it can no longer drift from what was actually booked.
 *
 * Read-only by design. Hours move when a session is booked, cancelled or
 * edited, and nowhere else.
 */
class HourTrackingController extends Controller
{
    public function __construct(private HourTrackingReport $report) {}

    public function index(Request $request): Response
    {
        [$from, $to] = $this->window($request);

        return Inertia::render('hour-tracking/index', [
            'report' => $this->report->build($request->user(), $from, $to),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    public function pdf(Request $request, PdfService $pdfService): HttpResponse
    {
        [$from, $to] = $this->window($request);
        $therapist = $request->user();

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
     * The window being reported on, defaulting to the current calendar year —
     * the twelve columns the paper sheet has always had.
     *
     * A reversed range is swapped rather than rejected: the two inputs sit
     * side by side and picking the end date first is the obvious slip.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function window(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->startOfYear();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfYear();

        return $from->greaterThan($to) ? [$to->copy()->startOfDay(), $from->copy()->endOfDay()] : [$from, $to];
    }
}
