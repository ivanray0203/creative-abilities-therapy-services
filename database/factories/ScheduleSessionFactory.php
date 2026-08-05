<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSession>
 */
class ScheduleSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'client_id' => Client::factory(),
            'therapist_id' => User::factory()->therapist(),
            'scheduled_start' => $start,
            'scheduled_end' => (clone $start)->modify('+1 hour'),
            'location' => fake()->randomElement(['Clinic', 'Home', 'Virtual']),
            'duration' => '60 minutes',
            'status' => 'scheduled',
        ];
    }
}
