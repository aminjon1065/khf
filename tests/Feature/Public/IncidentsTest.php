<?php

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\Incident;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('exposes the hazard level value so the accessible HazardBadge can render', function () {
    $incident = Incident::factory()->create(['hazard_level' => HazardLevel::Elevated]);
    $incident->upsertTranslations(['tj' => ['title' => 'Ҳодиса', 'description' => 'Тавсиф']]);

    $this->get(route('incidents.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/incidents/index')
            ->has('incidents.data', 1)
            ->where('incidents.data.0.hazard_level', 'elevated')
            ->where('incidents.data.0.hazard_label', 'Омодабоши баланд'));
});

it('exposes an operational-situation summary by status', function () {
    Incident::factory()->count(2)->create(['status' => IncidentStatus::Active])
        ->each->upsertTranslations(['tj' => ['title' => 'Фаъол', 'description' => 'd']]);
    Incident::factory()->create(['status' => IncidentStatus::Resolved])
        ->upsertTranslations(['tj' => ['title' => 'Анҷом', 'description' => 'd']]);

    $this->get(route('incidents.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.active', 2)
            ->where('summary.resolved', 1)
            ->where('summary.controlled', 0));
});

it('filters incidents archive by type and period', function () {
    $incident1 = Incident::factory()->create(['type' => IncidentType::Earthquake, 'occurred_at' => now()->subDays(2)]);
    $incident1->upsertTranslations(['tj' => ['title' => 'EQ']]);

    $incident2 = Incident::factory()->create(['type' => IncidentType::Flood, 'occurred_at' => now()->subMonths(2)]);
    $incident2->upsertTranslations(['tj' => ['title' => 'FL']]);

    $this->get(route('incidents.index', ['locale' => 'tj', 'type' => 'earthquake']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.title', 'EQ')
        );

    $this->get(route('incidents.index', ['locale' => 'tj', 'period' => 'week']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.title', 'EQ')
        );
});
