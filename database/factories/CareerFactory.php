<?php

namespace Database\Factories;

use App\Models\Career;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position' => fake()->jobTitle(),
            'location' => 'Calgary, AB',
            'schedule' => 'Part-time',
            'contract' => 'Contract',
            'rate' => '$40-$60/hr',
            'short_description' => fake()->sentence(),
            'about_description' => fake()->paragraph(),
            'responsibilities' => [fake()->sentence(), fake()->sentence()],
            'qualifications' => [fake()->sentence(), fake()->sentence()],
            'skills' => [fake()->word(), fake()->word()],
            'benefits' => [fake()->sentence()],
            'is_active' => true,
            'due_date' => fake()->dateTimeBetween('now', '+2 months'),
        ];
    }
}
