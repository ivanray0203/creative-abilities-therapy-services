<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Intake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_intake_id' => Intake::factory(),
            'status' => 'active',
            'approved_date' => now(),
        ];
    }
}
