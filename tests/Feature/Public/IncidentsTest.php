<?php

use App\Enums\HazardLevel;
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
