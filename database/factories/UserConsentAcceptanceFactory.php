<?php

namespace Database\Factories;

use App\Models\ConsentDocument;
use App\Models\User;
use App\Models\UserConsentAcceptance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConsentAcceptance>
 */
class UserConsentAcceptanceFactory extends Factory
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
            'user_id' => User::factory(),
            'accepted_at' => now(),
            'is_revoked' => false,
        ];
    }
}
