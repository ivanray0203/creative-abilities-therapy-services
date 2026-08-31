<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The offer letter now sits between the interview and the hire: it is sent,
 * signed by the candidate through a signed link, and only then can an admin
 * hire — which is what creates their account.
 *
 * `signed_offer_letter` being null is what "still awaiting the candidate's
 * signature" means, mirroring `signed_invoice` on invoices.
 *
 * The status enum gains `offer_sent` and loses `shortlisted`, which was
 * unreachable: it appeared in no transition list, no filter, and no badge, so
 * no row can be holding it.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const STATUSES = ['pending', 'reviewing', 'interview_scheduled', 'offer_sent', 'hired', 'declined'];

    /** @var array<int, string> */
    private const PREVIOUS_STATUSES = ['pending', 'reviewing', 'interview_scheduled', 'shortlisted', 'hired', 'declined'];

    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->enum('application_status', self::STATUSES)->default('pending')->change();

            $table->timestamp('offer_sent_at')->nullable()->after('interview_platform');
            $table->timestamp('offer_expires_at')->nullable()->after('offer_sent_at');
            $table->string('offer_letter', 500)->nullable()->after('offer_expires_at');
            $table->string('offer_letter_drive_file_id')->nullable()->after('offer_letter');
            $table->string('signed_offer_letter', 500)->nullable()->after('offer_letter_drive_file_id');
            $table->string('signed_offer_letter_drive_file_id')->nullable()->after('signed_offer_letter');
            $table->timestamp('offer_accepted_at')->nullable()->after('signed_offer_letter_drive_file_id');
            $table->timestamp('offer_declined_at')->nullable()->after('offer_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn([
                'offer_sent_at',
                'offer_expires_at',
                'offer_letter',
                'offer_letter_drive_file_id',
                'signed_offer_letter',
                'signed_offer_letter_drive_file_id',
                'offer_accepted_at',
                'offer_declined_at',
            ]);

            $table->enum('application_status', self::PREVIOUS_STATUSES)->default('pending')->change();
        });
    }
};
