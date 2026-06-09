<?php

use Database\Seeders\LanguageSeeder;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('applies the locale from the url prefix and persists it to the session', function () {
    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertSessionHas('locale', 'ru');
});

it('keeps the session locale on unprefixed routes', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('home'))
        ->assertRedirect(route('welcome', ['locale' => 'en']));
});

it('detects the locale from the browser when no session exists', function () {
    $this->get(route('home'), ['Accept-Language' => 'ru-RU,ru;q=0.9'])
        ->assertRedirect(route('welcome', ['locale' => 'ru']));
});

it('maps the Tajik bcp-47 tag (tg) to the internal tj code', function () {
    $this->get(route('home'), ['Accept-Language' => 'tg'])
        ->assertRedirect(route('welcome', ['locale' => 'tj']));
});

it('falls back to the default locale when nothing matches', function () {
    $this->get(route('home'), ['Accept-Language' => 'fr-FR,fr;q=0.9'])
        ->assertRedirect(route('welcome', ['locale' => 'tj']));
});

it('rejects an unsupported locale prefix', function () {
    $this->get('/de')->assertNotFound();
});
