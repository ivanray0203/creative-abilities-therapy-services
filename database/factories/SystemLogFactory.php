<?php

namespace Database\Factories;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemLog>
 */
class SystemLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created_intake', 'updated_client', 'logged_in', 'deleted_document']),
            'details' => ['status' => 'success', 'user_email' => fake()->safeEmail()],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
