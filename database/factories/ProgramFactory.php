<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // The unique() first word keeps both the name and its slug distinct,
        // matching ServiceOfferingFactory.
        $name = ucwords(fake()->unique()->word().' '.fake()->word().' Program');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(['Social Skills', 'Camp', 'Parent Workshop', 'Early Years']),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'age_range' => fake()->randomElement(['3-5 years', '6-9 years', '10-12 years']),
            'schedule' => 'Saturdays, 10:00 AM - 12:00 PM',
            'location' => fake()->randomElement(['Main Clinic', 'Community Hall']),
            'highlights' => [fake()->sentence(6), fake()->sentence(6)],
            'capacity' => fake()->numberBetween(8, 20),
            'price' => fake()->randomFloat(2, 50, 400),
            'starts_on' => now()->addWeeks(4)->toDateString(),
            'ends_on' => now()->addWeeks(10)->toDateString(),
            'registration_closes_on' => now()->addWeeks(3)->toDateString(),
            'is_active' => true,
        ];
    }

    public function full(): static
    {
        return $this->state(fn (): array => ['capacity' => 1]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'registration_closes_on' => now()->subDay()->toDateString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
