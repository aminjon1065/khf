<?php

namespace Database\Factories;

use App\Enums\AppealStatus;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacancyApplication>
 */
class VacancyApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => VacancyApplication::generateReference(),
            'vacancy_id' => Vacancy::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'cover_letter' => fake()->paragraph(),
            'status' => AppealStatus::New,
            'assigned_to' => null,
            'internal_note' => null,
        ];
    }
}
