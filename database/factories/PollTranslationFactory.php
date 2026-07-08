<?php

namespace Database\Factories;

use App\Models\Poll;
use App\Models\PollTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PollTranslation>
 */
class PollTranslationFactory extends Factory
{
    protected $model = PollTranslation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'poll_id' => Poll::factory(),
            'locale' => 'ru',
            'title' => $title,
            'description' => fake()->optional()->paragraph(),
            'slug' => Str::slug($title).'-ru',
        ];
    }
}
