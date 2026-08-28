<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The aide's time sheet, built from the hours they logged for one child
 * over a chosen period.
 *
 * Mirrors BillingItemInvoiceGenerator: the loose entries are locked, rolled
 * into one document, and stamped with its id so no two periods can claim
 * the same day. What differs is that nothing here is priced — a time sheet
 * carries hours, and the money side of the aide's work is settled off it by
 * the clinic rather than on it.
 *
 * The aide signs as they generate, so the form reaches the parent already
 * signed on one side.
 */
class TimesheetGenerator
{
    public function __construct(
        private ReferenceNumberGenerator $referenceNumbers,
        private TimesheetDocumentService $documents,
    ) {}

    /**
     * One timesheet for every hour this aide logged for this client between
     * the two dates, or null when nothing is waiting to be sheeted.
     *
     * @param  string  $aideSignature  A data: URI produced by the signature pad.
     */
    public function generate(
        User $aide,
        Client $client,
        CarbonInterface $from,
        CarbonInterface $to,
        string $aideSignature,
    ): ?Timesheet {
        $timesheet = DB::transaction(function () use ($aide, $client, $from, $to, $aideSignature): ?Timesheet {
            $entries = self::claimableFor($aide)
                ->where('client_id', $client->id)
                ->whereDate('entry_date', '>=', $from->toDateString())
                ->whereDate('entry_date', '<=', $to->toDateString())
                // Locked for the length of the transaction: generating two
                // overlapping periods at once must not sheet the same day
                // onto both forms.
                ->lockForUpdate()
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get();

            if ($entries->isEmpty()) {
                return null;
            }

            $timesheet = new Timesheet([
                'therapist_id' => $aide->id,
                'client_id' => $client->id,
                'issued_by_id' => $aide->id,
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'rows' => $this->rowsFrom($entries),
                ...$this->totalsFrom($entries),
                'aide_signature' => $aideSignature,
                'aide_signed_at' => now(),
                'status' => Timesheet::STATUS_AWAITING_CLIENT,
                'timeline' => [
                    $this->timelineEntry(
                        'Timesheet generated for '
                            .$from->format('M j, Y').' – '.$to->format('M j, Y'),
                    ),
                    $this->timelineEntry('Signed by the aide'),
                    $this->timelineEntry('Sent to the parent for signature'),
                ],
            ]);

            $timesheet->timesheet_number = $this->referenceNumbers->timesheet();
            $timesheet->save();

            TimesheetEntry::query()
                ->whereIn('id', $entries->pluck('id'))
                ->update(['timesheet_id' => $timesheet->id]);

            return $timesheet;
        });

        // Filed outside the transaction: the upload talks to Drive, and a
        // slow or failed call must not roll the timesheet back.
        if ($timesheet !== null) {
            $this->documents->storeUnsigned($timesheet);
        }

        return $timesheet;
    }

    /**
     * Entries this aide can still put on a timesheet: their own, not yet on
     * any form.
     *
     * @return Builder<TimesheetEntry>
     */
    public static function claimableFor(User $aide): Builder
    {
        return TimesheetEntry::query()
            ->whereNull('timesheet_id')
            ->where('therapist_id', $aide->id);
    }

    /**
     * The entries as printed rows, in the column order the form uses. This
     * is the copy the PDF renders from — correcting an entry afterwards must
     * not rewrite a form the parent has already signed.
     *
     * @param  Collection<int, TimesheetEntry>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function rowsFrom(Collection $entries): array
    {
        return $entries
            ->map(fn (TimesheetEntry $entry): array => [
                'date' => $entry->entry_date?->toDateString(),
                'hourly_respite' => (float) $entry->hourly_respite_hours,
                'community_support' => (float) $entry->community_support_hours,
                'bda_direct' => (float) $entry->bda_direct_hours,
                'bda_indirect' => (float) $entry->bda_indirect_hours,
                'notes' => $entry->notes,
            ])
            ->values()
            ->all();
    }

    /**
     * The four column totals and their sum, as the foot of the form reads.
     *
     * @param  Collection<int, TimesheetEntry>  $entries
     * @return array<string, float>
     */
    private function totalsFrom(Collection $entries): array
    {
        $totals = [
            'total_hourly_respite' => round($entries->sum(fn (TimesheetEntry $entry): float => (float) $entry->hourly_respite_hours), 2),
            'total_community_support' => round($entries->sum(fn (TimesheetEntry $entry): float => (float) $entry->community_support_hours), 2),
            'total_bda_direct' => round($entries->sum(fn (TimesheetEntry $entry): float => (float) $entry->bda_direct_hours), 2),
            'total_bda_indirect' => round($entries->sum(fn (TimesheetEntry $entry): float => (float) $entry->bda_indirect_hours), 2),
        ];

        return [...$totals, 'total_hours' => round(array_sum($totals), 2)];
    }

    /**
     * @return array{id: string, title: string, date: string, time: string}
     */
    private function timelineEntry(string $title): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'date' => now()->toDateString(),
            'time' => now()->format('g:i:s A'),
        ];
    }
}
