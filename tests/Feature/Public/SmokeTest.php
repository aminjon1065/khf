<?php

use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

it('loads all critical public routes without 500 errors', function (string $url) {
    // We expect a 200 or 302, but never 500
    $response = $this->get($url);

    expect($response->status())->toBeLessThan(500);
})->with([
    'Homepage (tj)' => ['/tj'],
    'Homepage (ru)' => ['/ru'],
    'Incidents Archive' => ['/tj/incidents'],
    'Map' => ['/tj/map'],
    'Search' => ['/tj/search?q=test'],
    'Guides Index' => ['/tj/guides'],
    'News Index' => ['/tj/news'],
    'Tourism Form' => ['/tj/tourism/create'],
    'Appeals Form' => ['/tj/appeals/create'],
    'Regional Offices' => ['/tj/regional-offices'],
]);

it('loads all CMS auth routes', function (string $url) {
    $response = $this->get($url);
    expect($response->status())->toBeLessThan(500);
})->with([
    'Login' => ['/admin/login'],
    'Forgot Password' => ['/admin/forgot-password'],
]);
