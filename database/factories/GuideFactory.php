<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    protected $model = Guide::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hazard_type' => fake()->randomElement(IncidentType::cases()),
            'audience' => GuideAudience::General,
            'status' => ContentStatus::Published,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Draft]);
    }

    public function forChildren(): static
    {
        return $this->state(fn (): array => ['audience' => GuideAudience::Children]);
    }
}
