<?php

namespace Database\Factories;

use App\Models\Statistic;
use App\Models\StatisticTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatisticTranslation>
 */
class StatisticTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'statistic_id' => Statistic::factory(),
            'locale' => 'ru',
            'label' => ucfirst(fake()->words(3, true)),
            'unit' => fake()->randomElement(['человек', 'случаев', 'единиц', null]),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
