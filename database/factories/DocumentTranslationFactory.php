<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTranslation>
 */
class DocumentTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'locale' => 'ru',
            'name' => fake()->sentence(4),
            'description' => fake()->sentence(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (): array => ['locale' => $locale]);
    }
}
