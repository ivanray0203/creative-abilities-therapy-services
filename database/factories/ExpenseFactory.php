<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 20, 2500);

        return [
            'reference_number' => 'EXP-'.now()->year.'-'.$this->faker->unique()->numberBetween(1, 99999),
            'expense_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'category' => $this->faker->randomElement(Expense::CATEGORIES),
            'payee' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'amount' => $amount,
            // Alberta has no provincial sales tax, so GST alone at 5%.
            'tax_amount' => round($amount * 0.05, 2),
            'payment_method' => $this->faker->randomElement(Expense::PAYMENT_METHODS),
            'status' => 'paid',
            'recorded_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => 'pending']);
    }

    public function category(string $category): static
    {
        return $this->state(fn (): array => ['category' => $category]);
    }

    public function on(string $date): static
    {
        return $this->state(fn (): array => ['expense_date' => $date]);
    }
}
