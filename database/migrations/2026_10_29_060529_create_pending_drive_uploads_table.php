<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uploads held on the private disk because Google Drive was not connected
     * (or failed) when they came in, waiting for `drive:sync-pending` to move
     * them into Drive. A synced row is kept so links handed out while the
     * file was pending keep resolving.
     */
    public function up(): void
    {
        Schema::create('pending_drive_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->string('target_type');
            $table->string('target_name');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->string('drive_file_id', 500)->nullable();
            $table->string('drive_file_url', 500)->nullable();
            $table->string('drive_web_view', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_drive_uploads');
    }
};
