<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Subdivision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subdivision>
 */
class SubdivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => ContentStatus::Published,
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'staff_count' => fake()->numberBetween(5, 200),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Draft]);
    }
}
