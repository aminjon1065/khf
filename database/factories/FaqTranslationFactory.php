<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqTranslation>
 */
class FaqTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faq_id' => Faq::factory(),
            'locale' => 'ru',
            'question' => rtrim(fake()->sentence(), '.').'?',
            'answer' => fake()->paragraph(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
