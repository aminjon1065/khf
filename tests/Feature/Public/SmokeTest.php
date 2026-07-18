<?php

use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

it('renders critical public routes successfully', function (string $routeName, array $parameters) {
    $this->get(route($routeName, $parameters))
        ->assertOk();
})->with([
    'Homepage (tj)' => ['welcome', ['locale' => 'tj']],
    'Homepage (ru)' => ['welcome', ['locale' => 'ru']],
    'Incidents Archive' => ['incidents.index', ['locale' => 'tj']],
    'Map' => ['map.index', ['locale' => 'tj']],
    'Search' => ['search.index', ['locale' => 'tj', 'q' => 'test']],
    'Guides Index' => ['guides.index', ['locale' => 'tj']],
    'News Index' => ['news.index', ['locale' => 'tj']],
    'Tourism Form' => ['tourist-groups.create', ['locale' => 'tj']],
    'Appeals Form' => ['appeals.create', ['locale' => 'tj']],
    'Contacts' => ['contacts.index', ['locale' => 'tj']],
]);

it('renders CMS authentication routes successfully', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk();
})->with([
    'Login' => ['login'],
    'Forgot Password' => ['password.request'],
]);
