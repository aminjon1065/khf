<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\TenderType;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tender>
 */
class TenderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tender_number' => 'ТЕНДЕР-'.now()->year.'-'.fake()->unique()->numberBetween(100, 9999),
            'status' => ContentStatus::Published,
            'type' => fake()->randomElement(TenderType::cases()),
            'budget' => fake()->numberBetween(50000, 5000000),
            'lots_count' => fake()->numberBetween(1, 5),
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
     * A published tender whose bid deadline has already passed.
     */
    public function closed(): static
    {
        return $this->state(fn (): array => ['deadline_at' => now()->subDay()]);
    }
}
