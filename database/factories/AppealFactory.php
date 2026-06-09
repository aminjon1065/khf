<?php

namespace Database\Factories;

use App\Enums\AppealCategory;
use App\Enums\AppealStatus;
use App\Models\Appeal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appeal>
 */
class AppealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Appeal::generateReference(),
            'category' => fake()->randomElement(AppealCategory::cases()),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => AppealStatus::New,
            'assigned_to' => null,
            'internal_note' => null,
        ];
    }
}
