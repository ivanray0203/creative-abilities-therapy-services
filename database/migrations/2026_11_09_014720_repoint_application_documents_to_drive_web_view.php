<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Resumes and cover letters were saved with Drive's download link, whose
     * endpoint answers 403 under the wrong signed-in Google account. The file
     * id is read from the link itself, so applications filed before the id
     * columns existed are repointed too.
     */
    public function up(): void
    {
        foreach (['resume', 'cover_letter'] as $column) {
            DB::table('applications')
                ->where($column, 'like', 'https://drive.google.com/uc?%')
                ->select(['id', $column])
                ->eachById(function (object $application) use ($column): void {
                    if (preg_match('~[?&]id=([A-Za-z0-9_-]+)~', $application->{$column}, $matches) !== 1) {
                        return;
                    }

                    DB::table('applications')
                        ->where('id', $application->id)
                        ->update([$column => "https://drive.google.com/file/d/{$matches[1]}/view"]);
                });
        }
    }

    /**
     * Intentionally a no-op: the viewer link opens the same file, and restoring
     * the download link would only bring the 403 back.
     */
    public function down(): void {}
};
