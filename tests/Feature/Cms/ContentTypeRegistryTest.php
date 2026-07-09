<?php

use App\Cms\ContentTypeRegistry;
use App\Enums\Permission;
use App\Models\Page;
use App\Models\Post;

it('registers all configured content types', function () {
    $registry = app(ContentTypeRegistry::class);

    expect($registry->all())->not->toBeEmpty()
        ->and($registry->has('page'))->toBeTrue()
        ->and($registry->has('post'))->toBeTrue();
});

it('resolves a content type by handle', function () {
    $type = app(ContentTypeRegistry::class)->get('post');

    expect($type->handle)->toBe('post')
        ->and($type->label)->toBe('Новости')
        ->and($type->modelClass)->toBe(Post::class)
        ->and($type->blueprint)->toBe('post.default')
        ->and($type->routePrefix)->toBe('posts')
        ->and($type->managePermission)->toBe(Permission::ManagePosts->value)
        ->and($type->hasFeature('revisions'))->toBeTrue()
        ->and($type->hasFeature('schedulable'))->toBeTrue()
        ->and($type->indexRoute())->toBe('admin.posts.index');
});

it('throws for unknown content type handles', function () {
    app(ContentTypeRegistry::class)->get('unknown');
})->throws(InvalidArgumentException::class);

it('filters editorial content types', function () {
    $editorial = app(ContentTypeRegistry::class)->editorial();

    expect($editorial)->not->toBeEmpty()
        ->and(collect($editorial)->pluck('handle')->all())->toContain('page', 'post');
});

it('resolves content type from model instance', function () {
    $page = Page::factory()->create();
    $type = app(ContentTypeRegistry::class)->forModel($page);

    expect($type)->not->toBeNull()
        ->and($type->handle)->toBe('page');
});

it('resolves content type from model class', function () {
    $type = app(ContentTypeRegistry::class)->forModelClass(Post::class);

    expect($type)->not->toBeNull()
        ->and($type->handle)->toBe('post');
});
