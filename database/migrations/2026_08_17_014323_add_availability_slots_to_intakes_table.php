<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Availability is now captured as a day x time-of-day grid, which records
 * which time works on which day — something the independent `available_days`
 * and `preferred_times` lists cannot express.
 *
 * Those two columns stay, derived from the grid, because the therapist
 * dashboard, CSV export, session form and ScheduleMatcher all read them.
 */
return new class extends Migration
{
    /** Old label => new label, renamed when the afternoon slot widened to 12pm. */
    private const RENAMED_TIMES = [
        'Afternoon (1pm-3pm)' => 'Afternoons (12pm-3pm)',
    ];

    public function up(): void
    {
        Schema::table('intakes', function (Blueprint $table): void {
            $table->json('availability_slots')->nullable()->after('preferred_times');
        });

        $this->backfillSlotsFromExistingColumns();
    }

    public function down(): void
    {
        Schema::table('intakes', function (Blueprint $table): void {
            $table->dropColumn('availability_slots');
        });

        foreach (self::RENAMED_TIMES as $old => $new) {
            $this->replacePreferredTime($new, $old);
        }
    }

    /**
     * Existing intakes only know "these days" and "these times" separately,
     * so the best available reconstruction is the full cross-product: every
     * recorded time on every recorded day.
     */
    private function backfillSlotsFromExistingColumns(): void
    {
        foreach (self::RENAMED_TIMES as $old => $new) {
            $this->replacePreferredTime($old, $new);
        }

        DB::table('intakes')
            ->select('id', 'available_days', 'preferred_times')
            ->orderBy('id')
            ->chunk(200, function ($intakes): void {
                foreach ($intakes as $intake) {
                    $days = json_decode((string) $intake->available_days, true) ?: [];
                    $times = json_decode((string) $intake->preferred_times, true) ?: [];

                    if ($days === [] || $times === []) {
                        continue;
                    }

                    $slots = [];

                    foreach ($days as $day) {
                        $slots[$day] = array_values($times);
                    }

                    DB::table('intakes')
                        ->where('id', $intake->id)
                        ->update(['availability_slots' => json_encode($slots)]);
                }
            });
    }

    private function replacePreferredTime(string $from, string $to): void
    {
        DB::table('intakes')
            ->whereNotNull('preferred_times')
            ->where('preferred_times', 'like', '%'.$from.'%')
            ->update([
                'preferred_times' => DB::raw(
                    'REPLACE(preferred_times, '.DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')'
                ),
            ]);
    }
};
