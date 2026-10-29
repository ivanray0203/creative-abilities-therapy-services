<?php

namespace Database\Factories;

use App\Models\PendingDriveUpload;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PendingDriveUpload>
 */
class PendingDriveUploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'path' => "pending-drive/{$uuid}.pdf",
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'target_type' => 'client',
            'target_name' => fake()->numberBetween(1, 500).'_'.fake()->name(),
        ];
    }

    public function synced(): static
    {
        return $this->state(fn (): array => [
            'synced_at' => now(),
            'drive_file_id' => Str::random(33),
            'drive_file_url' => 'https://drive.google.com/uc?id=abc&export=download',
            'drive_web_view' => 'https://drive.google.com/file/d/abc/view',
        ]);
    }
}
