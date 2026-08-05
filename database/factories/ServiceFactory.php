<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word().' '.fake()->word();

        return [
            'name' => ucwords($name),
            'code' => str($name)->slug(),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'base_price' => fake()->randomFloat(2, 50, 250),
            'is_active' => true,
        ];
    }
}
