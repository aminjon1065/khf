<?php

namespace Database\Factories;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hazard_level' => HazardLevel::Danger,
            'status' => AlertStatus::Published,
            'region_id' => null,
            'is_dismissible' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => AlertStatus::Draft]);
    }

    public function critical(): static
    {
        return $this->state(fn (): array => ['hazard_level' => HazardLevel::Critical, 'is_dismissible' => false]);
    }
}
