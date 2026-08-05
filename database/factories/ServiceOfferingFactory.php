<?php

namespace Database\Factories;

use App\Models\ServiceOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOffering>
 */
class ServiceOfferingFactory extends Factory
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
            'description' => fake()->sentence(),
            'is_active' => true,
            'type' => fake()->randomElement(['general_service', 'specific_service', 'non_direct_service']),
            'base_price' => fake()->randomFloat(2, 50, 250),
        ];
    }
}
