<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => 'AB',
            'zip_code' => fake()->postcode(),
            'position_applied' => fake()->jobTitle(),
            'profession_status' => fake()->randomElement(['Registered', 'Provisional', 'Student']),
            'application_status' => 'pending',
            'reference_number' => 'APP-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
        ];
    }
}
