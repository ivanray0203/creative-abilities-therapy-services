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

    /** An offer that has gone out and is waiting on the candidate. */
    public function offerSent(): static
    {
        return $this->state(fn (): array => [
            'application_status' => 'offer_sent',
            'hourly_rate' => 42.50,
            'offer_sent_at' => now(),
            'offer_expires_at' => now()->addDays((int) config('cats.offer.acceptance_days', 5))->endOfDay(),
            'offer_letter' => 'https://drive.example.test/offer-letter.pdf',
        ]);
    }

    /** An offer the candidate has signed and returned — ready to hire. */
    public function offerSigned(): static
    {
        return $this->offerSent()->state(fn (): array => [
            'offer_accepted_at' => now(),
            'signed_offer_letter' => 'https://drive.example.test/signed-offer-letter.pdf',
        ]);
    }

    /** An offer whose deadline has passed with no answer. */
    public function offerExpired(): static
    {
        return $this->offerSent()->state(fn (): array => [
            'offer_sent_at' => now()->subDays(20),
            'offer_expires_at' => now()->subDays(15),
        ]);
    }
}
