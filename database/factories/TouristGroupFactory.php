<?php

namespace Database\Factories;

use App\Enums\AppealStatus;
use App\Models\TouristGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TouristGroup>
 */
class TouristGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => TouristGroup::generateReference(),
            'leader_name' => fake()->name(),
            'leader_phone' => fake()->phoneNumber(),
            'leader_email' => fake()->safeEmail(),
            'participants_count' => fake()->numberBetween(1, 12),
            'route' => fake()->sentence(),
            'equipment' => fake()->sentence(),
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(7),
            'region_id' => null,
            'status' => AppealStatus::New,
            'assigned_to' => null,
            'internal_note' => null,
        ];
    }
}
