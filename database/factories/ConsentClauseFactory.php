<?php

namespace Database\Factories;

use App\Models\ConsentClause;
use App\Models\ConsentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentClause>
 */
class ConsentClauseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => ConsentDocument::factory(),
            'order' => fake()->unique()->numberBetween(0, 100),
            'text_template' => fake()->paragraph(),
        ];
    }
}
