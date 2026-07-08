<?php

namespace Database\Factories;

use App\Models\PollOption;
use App\Models\PollOptionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PollOptionTranslation>
 */
class PollOptionTranslationFactory extends Factory
{
    protected $model = PollOptionTranslation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'poll_option_id' => PollOption::factory(),
            'locale' => 'ru',
            'label' => fake()->words(3, true),
        ];
    }
}
