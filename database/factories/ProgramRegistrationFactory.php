<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\ProgramRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramRegistration>
 */
class ProgramRegistrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'PRG-'.now()->year.'-'.fake()->unique()->numerify('###'),
            'program_id' => Program::factory(),
            'participant_first_name' => fake()->firstName(),
            'participant_last_name' => fake()->lastName(),
            'participant_date_of_birth' => fake()->dateTimeBetween('-12 years', '-3 years')->format('Y-m-d'),
            'parent_name' => fake()->name(),
            'parent_email' => fake()->safeEmail(),
            'parent_phone' => fake()->numerify('###-###-####'),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => 'cancelled']);
    }
}
