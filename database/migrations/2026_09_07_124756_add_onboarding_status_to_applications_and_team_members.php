<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding now sits between the signed offer and the hire. Moving a
 * candidate to `onboarding` creates their portal account so they can log in
 * and upload the documents their position requires; an admin reviews those
 * and only then hires them.
 *
 * The team member is created at the same moment with an `onboarding`
 * employment status, which is what the portal reads to keep every menu but
 * Profile hidden until the hire goes through.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const STATUSES = ['pending', 'reviewing', 'interview_scheduled', 'offer_sent', 'onboarding', 'hired', 'declined'];

    /** @var array<int, string> */
    private const PREVIOUS_STATUSES = ['pending', 'reviewing', 'interview_scheduled', 'offer_sent', 'hired', 'declined'];

    /** @var array<int, string> */
    private const EMPLOYMENT_STATUSES = ['onboarding', 'active', 'inactive', 'on_leave', 'terminated', 'archived'];

    /** @var array<int, string> */
    private const PREVIOUS_EMPLOYMENT_STATUSES = ['active', 'inactive', 'on_leave', 'terminated', 'archived'];

    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->enum('application_status', self::STATUSES)->default('pending')->change();
            $table->timestamp('onboarding_started_at')->nullable()->after('offer_declined_at');
        });

        Schema::table('team_members', function (Blueprint $table): void {
            $table->enum('employment_status', self::EMPLOYMENT_STATUSES)->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('onboarding_started_at');
            $table->enum('application_status', self::PREVIOUS_STATUSES)->default('pending')->change();
        });

        Schema::table('team_members', function (Blueprint $table): void {
            $table->enum('employment_status', self::PREVIOUS_EMPLOYMENT_STATUSES)->default('active')->change();
        });
    }
};
