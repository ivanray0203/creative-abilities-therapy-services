<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A video interview now comes with a Google Meet link, created through the
 * connected Google account's calendar when the interview is booked. The
 * calendar event id is kept so a reschedule moves the same event (and keeps
 * the same link) instead of leaving a stale one behind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('interview_meeting_link', 500)->nullable()->after('interview_platform');
            $table->string('interview_calendar_event_id')->nullable()->after('interview_meeting_link');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['interview_meeting_link', 'interview_calendar_event_id']);
        });
    }
};
