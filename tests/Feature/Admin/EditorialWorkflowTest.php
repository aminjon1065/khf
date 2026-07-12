<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Post;
use App\Models\User;
use App\Services\Admin\ModerationQueueService;
use App\Services\Cms\EditorialWorkflow;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, LanguageSeeder::class]);
});

function editorialPostPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'news',
        'category_id' => null,
        'status' => 'moderation',
        'published_at' => '',
        'translations' => [
            'tj' => ['title' => 'Хабар', 'slug' => 'habar', 'excerpt' => 'Анонс', 'body' => 'Матн'],
            'ru' => ['title' => 'Новость', 'slug' => 'novost', 'excerpt' => 'Анонс', 'body' => 'Текст'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('limits editors to draft and moderation transitions', function () {
    $workflow = app(EditorialWorkflow::class);
    $editor = User::factory()->create();

    expect($workflow->allowedTransitions(ContentStatus::Draft, $editor))
        ->toBe([ContentStatus::Moderation])
        ->and($workflow->allowedTransitions(ContentStatus::Moderation, $editor))
        ->toBe([ContentStatus::Draft])
        ->and($workflow->canPublish($editor))->toBeFalse();
});

it('allows publishers full publication transitions', function () {
    $workflow = app(EditorialWorkflow::class);
    $publisher = User::factory()->create();
    $publisher->assignRole(Role::Publisher->value);

    expect($workflow->canPublish($publisher))->toBeTrue()
        ->and($workflow->allowedTransitions(ContentStatus::Moderation, $publisher))
        ->toContain(ContentStatus::Published);
});

it('forbids an editor from publishing a post directly', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->from(route('admin.posts.create'))
        ->post(route('admin.posts.store'), editorialPostPayload(['status' => 'published']))
        ->assertSessionHasErrors('status');

    expect(Post::count())->toBe(0);
});

it('lets an editor submit a post for moderation', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->post(route('admin.posts.store'), editorialPostPayload())
        ->assertRedirect(route('admin.content.index', 'post'));

    expect(Post::first()?->status)->toBe(ContentStatus::Moderation);
});

it('forbids an editor from opening the moderation queue', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.moderation.index'))
        ->assertForbidden();
});

it('lets a publisher access the moderation queue and publish from it', function () {
    $publisher = User::factory()->withTwoFactor()->create();
    $publisher->assignRole(Role::Publisher->value);

    $post = Post::factory()->create([
        'author_id' => $publisher->id,
        'status' => ContentStatus::Moderation,
    ]);
    $post->upsertTranslations(['ru' => ['title' => 'На проверке', 'slug' => 'na-proverke']]);

    $this->actingAs($publisher)
        ->get(route('admin.moderation.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canPublish', true)
            ->has('items', 1));

    $this->actingAs($publisher)
        ->post(route('admin.moderation.publish', ['type' => 'post', 'id' => $post->id]))
        ->assertRedirect(route('admin.moderation.index'));

    expect($post->fresh()->status)->toBe(ContentStatus::Published)
        ->and(app(ModerationQueueService::class)->count())->toBe(0);
});

it('exposes role-aware status transitions on the post edit form', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Editor->value);

    $post = Post::factory()->create(['status' => ContentStatus::Draft]);
    $post->upsertTranslations(['ru' => ['title' => 'Черновик', 'slug' => 'chernovik']]);

    $this->actingAs($editor)
        ->get(route('admin.posts.edit', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canPublish', false)
            ->where('statusTransitions.0.value', 'moderation'));
});

it('lets an editor access the cms admin panel', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
