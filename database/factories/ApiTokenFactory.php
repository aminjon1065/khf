<?php

namespace Database\Factories;

use App\Models\ApiToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiToken>
 */
class ApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'token' => hash('sha256', Str::random(48)),
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
