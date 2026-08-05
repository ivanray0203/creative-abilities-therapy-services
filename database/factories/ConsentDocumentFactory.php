<?php

namespace Database\Factories;

use App\Models\ConsentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentDocument>
 */
class ConsentDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'is_active' => true,
            'purpose' => fake()->randomElement(['intake', 'application']),
            'version' => '1.0',
            'effective_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
