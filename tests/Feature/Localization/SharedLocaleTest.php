<?php

use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('shares the current locale and active languages with the front end', function () {
    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where('locale', 'ru')
            ->has('locales', 3)
            ->where('locales.0.code', 'tj')
            ->where('locales.0.native_name', 'Тоҷикӣ')
            ->where('locales.0.hreflang', 'tg')
            ->where('locales.0.is_default', true)
        );
});

it('builds locale switch urls that preserve the current path', function () {
    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('localeSwitch.tj', url('/tj'))
            ->where('localeSwitch.ru', url('/ru'))
            ->where('localeSwitch.en', url('/en'))
        );
});
