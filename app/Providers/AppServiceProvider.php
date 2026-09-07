<?php

namespace App\Providers;

use App\Services\GoogleDrive\DriveStorage;
use App\Services\GoogleDrive\GoogleAccountClient;
use App\Services\GoogleDrive\GoogleDriveService;
use App\Services\GoogleDrive\LocalDriveStorage;
use App\Services\Interviews\GoogleMeetLinkGenerator;
use App\Services\Interviews\MeetingLinkGenerator;
use App\Services\Interviews\NullMeetingLinkGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DriveStorage::class, function (): DriveStorage {
            $clientId = config('services.google_drive.client_id');
            $clientSecret = config('services.google_drive.client_secret');
            $tokenPath = config('services.google_drive.token_path');
            $rootFolderId = config('services.google_drive.shared_drive_id');

            if (blank($clientId) || blank($clientSecret) || blank($rootFolderId) || ! is_file($tokenPath)) {
                return new LocalDriveStorage;
            }

            return new GoogleDriveService(new GoogleAccountClient($clientId, $clientSecret, $tokenPath), $rootFolderId);
        });

        // Google Meet links come from the same connected Google account as
        // Drive uploads. Without that connection interviews are still booked,
        // just without a link.
        $this->app->bind(MeetingLinkGenerator::class, function (): MeetingLinkGenerator {
            $clientId = config('services.google_drive.client_id');
            $clientSecret = config('services.google_drive.client_secret');
            $tokenPath = config('services.google_drive.token_path');

            if (blank($clientId) || blank($clientSecret) || ! is_file($tokenPath)) {
                return new NullMeetingLinkGenerator;
            }

            return new GoogleMeetLinkGenerator(new GoogleAccountClient($clientId, $clientSecret, $tokenPath));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
