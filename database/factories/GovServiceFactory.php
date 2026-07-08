<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\ServiceCategory;
use App\Models\GovService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GovService>
 */
class GovServiceFactory extends Factory
{
    protected $model = GovService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => ServiceCategory::Information,
            'status' => ContentStatus::Published,
            'is_online' => false,
            'external_url' => null,
            'processing_time' => '5 рабочих дней',
            'fee' => 'Бесплатно',
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
