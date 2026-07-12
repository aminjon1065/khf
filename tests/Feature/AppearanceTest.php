<?php

use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

test('dark appearance cookie applies the dark class on the document', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertSee('class="dark"', false);
});

test('light appearance cookie does not apply the dark class', function () {
    $response = $this->withUnencryptedCookie('appearance', 'light')
        ->get(route('welcome', ['locale' => 'tj']))
        ->assertOk();

    expect($response->getContent())->not->toContain('class="dark"');
});

test('theme translation keys are shared with the frontend', function () {
    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.theme.toggle', 'Переключить тему')
            ->where('translations.theme.light', 'светлая')
            ->where('translations.theme.dark', 'тёмная')
            ->where('translations.theme.system', 'системная')
        );
});
