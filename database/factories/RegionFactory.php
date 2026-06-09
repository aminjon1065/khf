<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'code' => Str::upper(fake()->unique()->bothify('REG-####')),
            'latitude' => fake()->latitude(36, 41),
            'longitude' => fake()->longitude(67, 75),
            'sort_order' => 0,
        ];
    }
}
