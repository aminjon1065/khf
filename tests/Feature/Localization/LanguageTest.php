<?php

use App\Models\Language;
use Database\Seeders\LanguageSeeder;

it('seeds the three portal languages with Tajik as default', function () {
    $this->seed(LanguageSeeder::class);

    expect(Language::count())->toBe(3)
        ->and(Language::codes())->toBe(['tj', 'ru', 'en'])
        ->and(Language::defaultCode())->toBe('tj');
});

it('maps the internal Tajik code to a valid BCP-47 hreflang', function () {
    $this->seed(LanguageSeeder::class);

    $tajik = Language::where('code', 'tj')->firstOrFail();

    expect($tajik->hreflang)->toBe('tg')
        ->and($tajik->native_name)->toBe('Тоҷикӣ')
        ->and($tajik->is_default)->toBeTrue();
});

it('re-seeds idempotently without duplicating rows', function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(LanguageSeeder::class);

    expect(Language::count())->toBe(3);
});

it('excludes inactive languages and respects sort order', function () {
    Language::factory()->inactive()->create(['code' => 'fr', 'sort_order' => 0]);

    $this->seed(LanguageSeeder::class);

    expect(Language::codes())->toBe(['tj', 'ru', 'en'])
        ->and(Language::isSupported('fr'))->toBeFalse()
        ->and(Language::isSupported('ru'))->toBeTrue();
});

it('exposes the supported locales as a config fallback', function () {
    expect(config('app.locales'))->toBe(['tj', 'ru', 'en'])
        ->and(config('app.locale'))->toBe('tj');
});
