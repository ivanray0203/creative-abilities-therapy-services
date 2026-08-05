<?php

namespace Database\Factories;

use App\Models\Intake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intake>
 */
class IntakeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dob = fake()->dateTimeBetween('-12 years', '-2 years');

        return [
            'child_first_name' => fake()->firstName(),
            'child_last_name' => fake()->lastName(),
            'date_of_birth' => $dob,
            'age' => now()->diffInYears($dob, true),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'status' => 'pending',
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state_province' => 'AB',
            'postal_code' => fake()->postcode(),
            'primary_parent_name' => fake()->name(),
            'primary_parent_phone' => fake()->phoneNumber(),
            'primary_parent_email' => fake()->unique()->safeEmail(),
            'primary_relationship_to_child' => 'Parent',
            'primary_contact_method' => 'email',
            'reference_number' => 'INT-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
        ];
    }
}
