<?php

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\LazyLoadingViolationException;

it('resolves translations from an eager-loaded collection', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->locale('tj')->create(['title' => 'Сарсаҳифа']);
    PageTranslation::factory()->for($page)->locale('ru')->create(['title' => 'Главная']);

    $page->load('translations');

    expect($page->translation('ru')?->title)->toBe('Главная')
        ->and($page->hasTranslation('tj'))->toBeTrue()
        ->and($page->translatedLocales())->toEqualCanonicalizing(['tj', 'ru']);
});

it('resolves translations via query when the relation is not loaded', function () {
    config()->set('app.fallback_locale', 'tj');

    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->locale('tj')->create(['title' => 'Танҳо тоҷикӣ']);

    $fresh = Page::query()->findOrFail($page->id);

    expect($fresh->relationLoaded('translations'))->toBeFalse();

    expect(fn () => $fresh->translation('en'))
        ->not->toThrow(LazyLoadingViolationException::class);

    expect($fresh->translation('en')?->title)->toBe('Танҳо тоҷикӣ')
        ->and($fresh->hasTranslation('tj'))->toBeTrue()
        ->and($fresh->hasTranslation('en'))->toBeFalse()
        ->and($fresh->translatedLocales())->toBe(['tj']);
});
