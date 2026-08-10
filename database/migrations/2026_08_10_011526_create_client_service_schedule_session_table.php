<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A session can cover several of a child's availed services at once — a
 * therapist holding three of them may deliver two in one visit — so the
 * single `linked_client_service_id` column becomes a pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_service_schedule_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_session_id')->constrained('schedule_sessions')->cascadeOnDelete();
            $table->foreignId('client_service_id')->constrained('client_services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['schedule_session_id', 'client_service_id'], 'session_client_service_unique');
        });

        DB::table('schedule_sessions')
            ->whereNotNull('linked_client_service_id')
            ->orderBy('id')
            ->chunkById(200, function ($sessions): void {
                DB::table('client_service_schedule_session')->insert(
                    collect($sessions)->map(fn ($session): array => [
                        'schedule_session_id' => $session->id,
                        'client_service_id' => $session->linked_client_service_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all(),
                );
            });

        Schema::table('schedule_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_client_service_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_sessions', function (Blueprint $table) {
            $table->foreignId('linked_client_service_id')->nullable()->after('service_name')
                ->constrained('client_services')->nullOnDelete();
        });

        // Only the earliest link per session survives the round trip — the
        // column can hold no more than one.
        DB::table('client_service_schedule_session')
            ->orderBy('schedule_session_id')
            ->orderBy('id')
            ->get()
            ->groupBy('schedule_session_id')
            ->each(function ($links, $sessionId): void {
                DB::table('schedule_sessions')
                    ->where('id', $sessionId)
                    ->update(['linked_client_service_id' => $links->first()->client_service_id]);
            });

        Schema::dropIfExists('client_service_schedule_session');
    }
};
