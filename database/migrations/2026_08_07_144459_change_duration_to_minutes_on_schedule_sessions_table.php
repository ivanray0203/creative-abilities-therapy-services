<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 18 — `duration` was a free-text string ("30 minutes") that
 * SessionController parsed with `preg_match('/\d+/')`, taking the first
 * number it found. The form's options all happened to start with the minute
 * count, but validation accepted any string, so a "1 hour" option would have
 * silently produced a one-minute session.
 *
 * Storing minutes directly removes the parsing step entirely.
 *
 * The value conversion runs in PHP rather than SQL: the suite runs on SQLite
 * in-memory while the app runs on MySQL, and the string-splitting functions
 * differ between them (SUBSTRING_INDEX/REGEXP don't exist in SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rewriteDurations(function (?string $duration): ?int {
            preg_match('/^\s*(\d+)/', (string) $duration, $matches);

            // Anything without a leading number has no minute count worth
            // keeping — better null than a silently wrong integer.
            return isset($matches[1]) ? (int) $matches[1] : null;
        });

        Schema::table('schedule_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_sessions', function (Blueprint $table) {
            $table->string('duration', 50)->nullable()->change();
        });

        $this->rewriteDurations(fn (?string $duration): ?string => $duration === null
            ? null
            : "{$duration} minutes");
    }

    /**
     * Apply a transform to every non-null duration, one row at a time.
     */
    private function rewriteDurations(callable $transform): void
    {
        DB::table('schedule_sessions')
            ->whereNotNull('duration')
            ->select('id', 'duration')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($transform): void {
                foreach ($rows as $row) {
                    DB::table('schedule_sessions')
                        ->where('id', $row->id)
                        ->update(['duration' => $transform($row->duration)]);
                }
            });
    }
};
