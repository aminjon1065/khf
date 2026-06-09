<?php

namespace Database\Factories;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(IncidentType::cases()),
            'hazard_level' => fake()->randomElement(HazardLevel::cases()),
            'status' => IncidentStatus::Active,
            'region_id' => null,
            'latitude' => fake()->latitude(36, 41),
            'longitude' => fake()->longitude(67, 75),
            'occurred_at' => now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => ['status' => IncidentStatus::Resolved]);
    }
}
