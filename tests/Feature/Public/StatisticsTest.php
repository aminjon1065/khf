<?php

use App\Models\Statistic;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the public statistics page with published indicators', function () {
    $statistic = Statistic::factory()->create();
    $statistic->upsertTranslations(['tj' => ['label' => 'Спасательных операций', 'unit' => 'ед.']]);

    $this->get(route('statistics.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/statistics/index')->has('statistics', 1));
});

it('does not show draft indicators publicly', function () {
    $statistic = Statistic::factory()->draft()->create();
    $statistic->upsertTranslations(['tj' => ['label' => 'Черновик']]);

    $this->get(route('statistics.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('statistics', 0));
});
