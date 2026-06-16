<?php

namespace Database\Factories;

use App\Models\Vacancy;
use App\Models\VacancyTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VacancyTranslation>
 */
class VacancyTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'vacancy_id' => Vacancy::factory(),
            'locale' => 'ru',
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'department' => fake()->company(),
            'location' => fake()->city(),
            'salary' => fake()->numberBetween(2000, 8000).' сомони',
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'requirements' => fake()->paragraph(),
            'responsibilities' => fake()->paragraph(),
            'seo_title' => $title,
            'seo_description' => fake()->sentence(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
