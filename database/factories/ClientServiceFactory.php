<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ServiceOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientService>
 */
class ClientServiceFactory extends Factory
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
            'service_id' => ServiceOffering::factory(),
            'frequency' => fake()->randomElement(['Weekly', 'Bi-weekly', 'Monthly']),
            'duration' => '60 minutes',
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'no_sessions' => fake()->numberBetween(1, 20),
            'goals' => fake()->sentence(),
        ];
    }
}
