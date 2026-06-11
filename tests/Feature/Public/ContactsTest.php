<?php

use Database\Seeders\LanguageSeeder;
use Database\Seeders\RegionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(RegionSeeder::class);
});

it('renders the contacts page with regional offices for the map', function () {
    $this->get(route('contacts.index', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/contacts')
            ->has('regions', 4)
            ->where('regions.0.name', 'Душанбе')
            ->has('regions.0.lat')
            ->has('regions.0.lng'));
});
