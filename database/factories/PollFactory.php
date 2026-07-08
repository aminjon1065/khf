<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\PollType;
use App\Models\Poll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poll>
 */
class PollFactory extends Factory
{
    protected $model = Poll::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PollType::General,
            'status' => ContentStatus::Published,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'show_results' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Draft]);
    }

    public function antiCorruption(): static
    {
        return $this->state(fn (): array => ['type' => PollType::AntiCorruptionExpertise]);
    }
}
