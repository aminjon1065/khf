<?php

namespace Database\Factories;

use App\Models\Tender;
use App\Models\TenderTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TenderTranslation>
 */
class TenderTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(3, true));

        return [
            'tender_id' => Tender::factory(),
            'locale' => 'ru',
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'organizer' => fake()->company(),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'requirements' => fake()->paragraph(),
            'terms' => fake()->paragraph(),
            'seo_title' => $title,
            'seo_description' => fake()->sentence(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
