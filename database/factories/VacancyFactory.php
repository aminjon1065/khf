<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\EmploymentType;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
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
            'employment_type' => EmploymentType::FullTime,
            'positions_count' => fake()->numberBetween(1, 5),
            'published_at' => now(),
            'deadline_at' => now()->addDays(30),
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Draft, 'published_at' => null]);
    }

    /**
     * A published vacancy whose application deadline has already passed.
     */
    public function closed(): static
    {
        return $this->state(fn (): array => ['deadline_at' => now()->subDay()]);
    }
}
