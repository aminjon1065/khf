<?php

use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Leader;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Statistic;
use App\Models\Subdivision;
use App\Models\Tag;
use App\Models\Tender;
use App\Models\Vacancy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a full public test dataset with grouped menus', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Post::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(Leader::query()->count())->toBe(3)
        ->and(Subdivision::query()->count())->toBe(4)
        ->and(Gallery::query()->count())->toBe(2)
        ->and(Faq::query()->count())->toBe(3)
        ->and(Poll::query()->count())->toBe(2)
        ->and(Vacancy::query()->count())->toBe(3)
        ->and(Tender::query()->count())->toBe(3)
        ->and(GovService::query()->count())->toBe(3)
        ->and(Statistic::query()->count())->toBe(3)
        ->and(Tag::query()->count())->toBe(3);

    $primary = Menu::query()->where('location', 'primary')->firstOrFail();

    expect(MenuItem::query()->where('menu_id', $primary->id)->whereNotNull('parent_id')->count())
        ->toBeGreaterThanOrEqual(10);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('menus.primary', 6)
            ->where('menus.primary.1.children.0.title', 'Руководство')
            ->where('menus.primary.2.children.0.title', 'Новости')
        );
});

it('is idempotent when seeders are run twice', function () {
    $this->seed(DatabaseSeeder::class);
    $counts = [
        'posts' => Post::query()->count(),
        'leaders' => Leader::query()->count(),
        'menu_items' => MenuItem::query()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect(Post::query()->count())->toBe($counts['posts'])
        ->and(Leader::query()->count())->toBe($counts['leaders'])
        ->and(MenuItem::query()->count())->toBe($counts['menu_items']);
});
