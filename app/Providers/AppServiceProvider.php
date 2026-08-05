<?php

namespace App\Providers;

use App\Services\GoogleDrive\DriveStorage;
use App\Services\GoogleDrive\GoogleDriveService;
use App\Services\GoogleDrive\LocalDriveStorage;
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

            return new GoogleDriveService($clientId, $clientSecret, $tokenPath, $rootFolderId);
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
