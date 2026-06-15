<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('shares the operational-situation summary on the homepage hero', function () {
    Incident::factory()->count(2)->create(['status' => IncidentStatus::Active]);
    Incident::factory()->create(['status' => IncidentStatus::Controlled]);
    Incident::factory()->resolved()->create();

    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where('operational.active', 2)
            ->where('operational.controlled', 1)
            ->where('operational.resolved', 1)
        );
});

it('reports a zero operational summary when there are no incidents', function () {
    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('operational.active', 0)
            ->where('operational.controlled', 0)
            ->where('operational.resolved', 0)
        );
});
