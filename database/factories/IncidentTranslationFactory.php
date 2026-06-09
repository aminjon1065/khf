<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentTranslation>
 */
class IncidentTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'locale' => 'ru',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
