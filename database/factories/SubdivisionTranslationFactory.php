<?php

namespace Database\Factories;

use App\Models\Subdivision;
use App\Models\SubdivisionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubdivisionTranslation>
 */
class SubdivisionTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subdivision_id' => Subdivision::factory(),
            'locale' => 'ru',
            'name' => 'Управление '.fake()->word(),
            'head' => fake()->name(),
            'functions' => fake()->paragraph(),
            'address' => fake()->address(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
