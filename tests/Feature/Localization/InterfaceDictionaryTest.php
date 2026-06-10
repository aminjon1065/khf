<?php

use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('shares the active locale interface dictionary with the front end', function () {
    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'ru')
            ->where('translations.nav.news', 'Новости')
            ->where('translations.site.short_name', 'КЧС')
        );
});

it('localizes the dictionary per requested locale', function () {
    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->where('translations.nav.news', 'Хабарҳо'));

    $this->get(route('welcome', ['locale' => 'en']))
        ->assertInertia(fn (Assert $page) => $page->where('translations.nav.news', 'News'));
});

it('keeps the dictionary keys identical across all locales', function () {
    $flatten = function (array $messages, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($messages as $key => $value) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";
            $keys = is_array($value) ? [...$keys, ...$flatten($value, $path)] : [...$keys, $path];
        }

        return $keys;
    };

    $tj = $flatten(trans('ui', [], 'tj'));
    sort($tj);

    foreach (['ru', 'en'] as $locale) {
        $other = $flatten(trans('ui', [], $locale));
        sort($other);

        expect($other)->toBe($tj, "Locale [{$locale}] dictionary keys diverge from tj.");
    }
});
