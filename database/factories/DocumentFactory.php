<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(DocumentType::cases()),
            'source' => 'КЧС',
            'document_date' => now()->subDays(fake()->numberBetween(1, 365)),
            'status' => ContentStatus::Published,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Draft]);
    }
}
