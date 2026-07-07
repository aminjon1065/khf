<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\TagTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TagTranslation>
 */
class TagTranslationFactory extends Factory
{
    protected $model = TagTranslation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'tag_id' => Tag::factory(),
            'locale' => 'ru',
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
