<?php

use App\Models\Page;

use function Pest\Laravel\get;

it('falls back to available translation when requested locale is missing', function () {
    // Create a page that only has a Tajik translation
    $page = Page::factory()->create(['status' => 'published']);
    $page->translations()->create([
        'locale' => 'tj',
        'title' => 'Саҳифаи санҷишӣ',
        'slug' => 'sahifai-sanjishi',
        'content' => 'Матни санҷишӣ',
    ]);

    // Request the page in Russian (which doesn't exist)
    // The locale middleware sets app()->getLocale() to 'ru' based on the prefix.
    // We request the tj slug, but under the ru prefix.
    $response = get('/ru/pages/sahifai-sanjishi');

    $response->assertOk();

    // Verify the page data falls back to the tj translation
    $response->assertInertia(fn ($pageAssert) => $pageAssert
        ->component('public/pages/show')
        ->where('page.title', 'Саҳифаи санҷишӣ')
        ->where('page.locale', 'tj')
    );
});
