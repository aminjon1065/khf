<?php

namespace Database\Factories;

use App\Models\Guide;
use App\Models\GuideTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GuideTranslation>
 */
class GuideTranslationFactory extends Factory
{
    protected $model = GuideTranslation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'guide_id' => Guide::factory(),
            'locale' => 'ru',
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(2, true),
            'seo_title' => $title,
            'seo_description' => fake()->sentence(),
        ];
    }
}
