<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
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
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'open',
            'type' => 'complaints',
            'complained_by' => 'client',
            'consent_given' => true,
            'consent_at' => now(),
        ];
    }
}
