<?php

use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('renders the branded Inertia error page for a missing route', function () {
    $this->get('/ru/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/error')
            ->where('status', 404));
});

it('does not brand API errors as Inertia pages', function () {
    $this->getJson('/api/nope')->assertNotFound()->assertJson(fn ($json) => $json->etc());
});
