<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Statistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Statistic>
 */
class StatisticFactory extends Factory
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
            'value' => (string) fake()->numberBetween(10, 10000),
            'year' => fake()->numberBetween(2018, 2026),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Draft]);
    }
}
