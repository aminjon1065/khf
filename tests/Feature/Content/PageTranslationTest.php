<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\PageTranslation;

it('returns the translation for the requested locale', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->locale('tj')->create(['title' => 'Сарсаҳифа']);
    PageTranslation::factory()->for($page)->locale('ru')->create(['title' => 'Главная']);

    $page->load('translations');

    expect($page->translation('ru')->title)->toBe('Главная')
        ->and($page->translation('tj')->title)->toBe('Сарсаҳифа');
});

it('falls back to the fallback locale when a translation is missing', function () {
    config()->set('app.fallback_locale', 'tj');

    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->locale('tj')->create(['title' => 'Танҳо тоҷикӣ']);

    $page->load('translations');

    expect($page->translation('en')->title)->toBe('Танҳо тоҷикӣ');
});

it('reports translated locales and presence', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->locale('ru')->create();

    $page->load('translations');

    expect($page->hasTranslation('ru'))->toBeTrue()
        ->and($page->hasTranslation('en'))->toBeFalse()
        ->and($page->translatedLocales())->toBe(['ru']);
});

it('upserts translations idempotently', function () {
    $page = Page::factory()->create();

    $page->upsertTranslations([
        'ru' => ['title' => 'Заголовок', 'slug' => 'zagolovok'],
        'tj' => ['title' => 'Сарлавҳа', 'slug' => 'sarlavha'],
    ]);
    expect($page->translations()->count())->toBe(2);

    $page->upsertTranslations(['ru' => ['title' => 'Новый', 'slug' => 'novyj']]);

    expect($page->translations()->count())->toBe(2)
        ->and($page->fresh()->translation('ru')->title)->toBe('Новый');
});

it('casts status to the content status enum', function () {
    $page = Page::factory()->draft()->create();

    expect($page->status)->toBe(ContentStatus::Draft);
});

it('soft deletes pages and filters by the published scope', function () {
    Page::factory()->create(['status' => ContentStatus::Published]);
    Page::factory()->draft()->create();

    expect(Page::published()->count())->toBe(1);

    $page = Page::factory()->create();
    $page->delete();

    expect(Page::count())->toBe(2)
        ->and(Page::withTrashed()->count())->toBe(3);
});
