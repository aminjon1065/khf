<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolePermissionSeeder::class]);
    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
});

it('shows the content hub for a super admin', function () {
    Post::factory()->count(2)->create();
    Page::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.content.hub'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/hub')
            ->has('types')
        );
});

it('lists entries in the unified browser for posts', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations([
        'ru' => ['title' => 'Тестовая новость', 'slug' => 'test-news'],
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.content.index', ['type' => 'post']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/index')
            ->where('contentType.handle', 'post')
            ->has('entries.data', 1)
            ->where('entries.data.0.title', 'Тестовая новость')
            ->has('types')
        );
});

it('filters entries by status in the unified browser', function () {
    Post::factory()->create(['status' => ContentStatus::Draft]);
    $published = Post::factory()->create(['status' => ContentStatus::Published]);
    $published->upsertTranslations([
        'ru' => ['title' => 'Опубликовано', 'slug' => 'published'],
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.content.index', ['type' => 'post', 'status' => 'published']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.title', 'Опубликовано')
        );
});

it('searches entries in the unified browser', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'ru' => ['title' => 'Уникальный заголовок', 'slug' => 'unique'],
    ]);
    Post::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.content.index', ['type' => 'post', 'search' => 'Уникальный']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.title', 'Уникальный заголовок')
        );
});

it('allows moderators to browse posts they can manage', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('admin.content.index', 'post'))
        ->assertOk();
});

it('bulk soft-deletes selected entries', function () {
    $posts = Post::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.content.bulk-destroy', 'post'), [
            'ids' => $posts->pluck('id')->all(),
        ])
        ->assertRedirect(route('admin.content.index', 'post'));

    expect(Post::query()->count())->toBe(0)
        ->and(Post::onlyTrashed()->count())->toBe(2);
});

it('rejects bulk destroy without ids', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.content.index', 'post'))
        ->post(route('admin.content.bulk-destroy', 'post'), [])
        ->assertSessionHasErrors('ids');
});

it('forbids bulk destroy for users without the content manage permission', function () {
    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole(Role::Moderator->value);
    // Moderator can manage posts, so use a plain authenticated CMS-ineligible path:
    // strip roles by using a verified 2FA user without CMS role (role middleware 403).
    $outsider = User::factory()->withTwoFactor()->create();

    $this->actingAs($outsider)
        ->post(route('admin.content.bulk-destroy', 'post'), ['ids' => [1]])
        ->assertForbidden();
});

it('lists documents, guides and alerts with corrected default sorts', function (string $type) {
    $this->actingAs($this->admin)
        ->get(route('admin.content.index', $type))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/index')
            ->where('contentType.handle', $type)
        );
})->with(['document', 'guide', 'alert']);

it('exposes incident status filters in the unified browser', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.content.index', 'incident'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/index')
            ->where('contentType.handle', 'incident')
            ->has('statuses')
        );
});

it('lists trashed entries in the unified browser with restore actions', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations([
        'ru' => ['title' => 'В корзине', 'slug' => 'in-trash'],
    ]);
    $post->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.content.index', ['type' => 'post', 'trashed' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/index')
            ->where('contentType.handle', 'post')
            ->where('filters.trashed', true)
            ->has('entries.data', 1)
            ->where('entries.data.0.title', 'В корзине')
            ->has('entries.data.0.restore_url')
            ->has('entries.data.0.force_delete_url')
        );
});

it('returns 404 for unknown content types', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.content.index', 'unknown'))
        ->assertNotFound();
});
