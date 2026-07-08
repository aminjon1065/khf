<?php

namespace Database\Factories;

use App\Models\GovService;
use App\Models\GovServiceTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GovServiceTranslation>
 */
class GovServiceTranslationFactory extends Factory
{
    protected $model = GovServiceTranslation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'gov_service_id' => GovService::factory(),
            'locale' => 'ru',
            'title' => $title,
            'slug' => Str::slug($title).'-ru',
            'summary' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'eligibility' => null,
            'required_documents' => null,
        ];
    }
}
