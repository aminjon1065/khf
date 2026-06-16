<?php

namespace Database\Factories;

use App\Models\Leader;
use App\Models\LeaderTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaderTranslation>
 */
class LeaderTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leader_id' => Leader::factory(),
            'locale' => 'ru',
            'full_name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'bio' => fake()->paragraphs(2, true),
            'reception' => 'Понедельник, среда: 9:00–12:00',
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
