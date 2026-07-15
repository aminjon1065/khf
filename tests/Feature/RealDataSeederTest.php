<?php

use App\Models\Document;
use App\Models\Leader;
use App\Models\Post;
use App\Models\Subdivision;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('seeds the real leadership, structure, documents and news harvested from the legacy sites', function () {
    $this->seed(ProductionSeeder::class);

    expect(Leader::query()->count())->toBe(4)
        ->and(Subdivision::query()->count())->toBe(27)
        ->and(Document::query()->count())->toBe(67)
        ->and(Post::query()->count())->toBe(95)
        // Every post carries its Drupal node id for the 301 map.
        ->and(Post::query()->whereNull('legacy_node_id')->count())->toBe(0);

    // The chairman is the real, verbatim harvested person — not demo lorem.
    $chairman = Leader::query()->orderBy('sort_order')->first();
    expect($chairman->translation('tj')?->full_name)->toBe('Назарзода Рустам');
});

it('never fabricates the English layer that the legacy sites do not have', function () {
    $this->seed(ProductionSeeder::class);

    // No leader has a stored English translation row (legacy sites are tj/ru only); the resolver's
    // locale fallback is bypassed by inspecting the raw relation.
    $englishRows = Leader::query()->with('translations')->get()
        ->flatMap(fn (Leader $leader) => $leader->translations)
        ->where('locale', 'en');

    expect($englishRows)->toBeEmpty();
});

it('renders real leadership on the public page', function () {
    $this->seed(ProductionSeeder::class);

    $this->get(route('leadership.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/leadership/index')
            ->has('leaders', 4));
});

it('renders real structure on the public page', function () {
    $this->seed(ProductionSeeder::class);

    $this->get(route('structure.index', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/structure/index'));
});

it('lists real legal documents on the public registry', function () {
    $this->seed(ProductionSeeder::class);

    $this->get(route('documents.index', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/documents/index')
            ->has('documents.data'));
});

it('is idempotent — re-seeding does not duplicate real content', function () {
    $this->seed(ProductionSeeder::class);
    $this->seed(ProductionSeeder::class);

    expect(Leader::query()->count())->toBe(4)
        ->and(Post::query()->count())->toBe(95);
});
