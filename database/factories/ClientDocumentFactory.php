<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientDocument>
 */
class ClientDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'doc_type' => fake()->randomElement(['assessment', 'consent_form', 'progress_report']),
            'title' => fake()->words(3, true),
            'upload_origin' => 'admin',
            'uploaded_at' => now(),
        ];
    }
}
