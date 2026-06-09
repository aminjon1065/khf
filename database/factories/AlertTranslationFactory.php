<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\AlertTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertTranslation>
 */
class AlertTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'alert_id' => Alert::factory(),
            'locale' => 'ru',
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
