<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\RegionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegionTranslation>
 */
class RegionTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'locale' => 'ru',
            'name' => fake()->city(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
