<?php

namespace Database\Factories;

use App\Models\Intake;
use App\Models\IntakeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntakeDocument>
 */
class IntakeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'name' => fake()->word().'.pdf',
            'type' => fake()->randomElement(['assessment', 'referral', 'medical_report']),
            'uploaded_at' => now(),
        ];
    }
}
