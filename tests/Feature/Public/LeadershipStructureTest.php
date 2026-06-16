<?php

use App\Models\Leader;
use App\Models\Subdivision;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the public leadership page with published leaders', function () {
    $leader = Leader::factory()->create();
    $leader->upsertTranslations(['tj' => ['full_name' => 'Председатель', 'position' => 'Раис']]);

    $this->get(route('leadership.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/leadership/index')->has('leaders', 1));
});

it('does not show draft leaders publicly', function () {
    $leader = Leader::factory()->draft()->create();
    $leader->upsertTranslations(['tj' => ['full_name' => 'Черновик', 'position' => 'X']]);

    $this->get(route('leadership.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('leaders', 0));
});

it('renders the public structure page as a tree', function () {
    $parent = Subdivision::factory()->create();
    $parent->upsertTranslations(['tj' => ['name' => 'Главное управление']]);

    $child = Subdivision::factory()->create(['parent_id' => $parent->id]);
    $child->upsertTranslations(['tj' => ['name' => 'Отдел']]);

    $this->get(route('structure.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/structure/index')
            ->has('tree', 1)
            ->has('tree.0.children', 1)
        );
});

it('only includes published subdivisions in the tree', function () {
    $published = Subdivision::factory()->create();
    $published->upsertTranslations(['tj' => ['name' => 'Опубликовано']]);

    $draft = Subdivision::factory()->draft()->create();
    $draft->upsertTranslations(['tj' => ['name' => 'Черновик']]);

    $this->get(route('structure.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('tree', 1));
});
