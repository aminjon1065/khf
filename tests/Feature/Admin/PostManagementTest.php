<?php

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function postPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'news',
        'category_id' => null,
        'status' => 'published',
        'published_at' => '2026-06-09T10:00',
        'translations' => [
            'tj' => ['title' => 'Хабари нав', 'slug' => 'habari-nav', 'excerpt' => 'Анонс', 'body' => 'Матн'],
            'ru' => ['title' => 'Новость', 'slug' => 'novost', 'excerpt' => 'Анонс', 'body' => 'Текст'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.posts.index'))
        ->assertForbidden();
});

it('creates a post with a category and sets the author', function () {
    $category = Category::factory()->create();
    $category->upsertTranslations(['tj' => ['name' => 'Рубрика', 'slug' => 'rubrika']]);

    $this->actingAs($this->editor)
        ->post(route('admin.posts.store'), postPayload(['category_id' => $category->id]))
        ->assertRedirect(route('admin.posts.index'));

    $post = Post::with('translations')->first();

    expect($post->type)->toBe(PostType::News)
        ->and($post->status)->toBe(ContentStatus::Published)
        ->and($post->category_id)->toBe($category->id)
        ->and($post->author_id)->toBe($this->editor->id)
        ->and($post->translations)->toHaveCount(2)
        ->and($post->published_at)->not->toBeNull();
});

it('validates type, default title and category existence', function () {
    $payload = postPayload(['type' => 'invalid', 'category_id' => 9999]);
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.posts.create'))
        ->post(route('admin.posts.store'), $payload)
        ->assertSessionHasErrors(['type', 'category_id', 'translations.tj.title']);
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.posts.store'), postPayload());

    $second = postPayload();
    $second['translations']['tj']['slug'] = 'habari-nav-2';

    $this->actingAs($this->editor)
        ->from(route('admin.posts.create'))
        ->post(route('admin.posts.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('renders the list, create, edit and trash screens', function () {
    $post = Post::factory()->create(['author_id' => $this->editor->id]);
    $post->upsertTranslations(['tj' => ['title' => 'Тест', 'slug' => 'test-post']]);

    $this->actingAs($this->editor)->get(route('admin.posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/posts/index')->has('posts.data', 1));

    $this->actingAs($this->editor)->get(route('admin.posts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/posts/form')->has('types', 4)->has('statuses', 4));

    $this->actingAs($this->editor)->get(route('admin.posts.edit', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/posts/form')->where('post.id', $post->id));

    $post->delete();

    $this->actingAs($this->editor)->get(route('admin.posts.trash'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/posts/trash')->has('posts.data', 1));
});

it('sanitizes the post body html on save', function () {
    $payload = postPayload();
    $payload['translations']['tj']['body'] = '<p>Безопасный текст</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="hack()">ссылка</a>';

    $this->actingAs($this->editor)->post(route('admin.posts.store'), $payload);

    $body = Post::first()->translation('tj')->body;

    expect($body)
        ->toContain('Безопасный текст')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:');
});

it('uploads and removes a post cover image', function () {
    Storage::fake('public');

    $this->actingAs($this->editor)->post(route('admin.posts.store'), postPayload([
        'cover' => UploadedFile::fake()->image('cover.jpg', 800, 600),
    ]));

    $post = Post::first();
    expect($post->getFirstMedia(Post::COVER_COLLECTION))->not->toBeNull();

    $this->actingAs($this->editor)->put(route('admin.posts.update', $post), postPayload([
        'remove_cover' => true,
    ]));

    expect($post->fresh()->getFirstMedia(Post::COVER_COLLECTION))->toBeNull();
});

it('soft deletes, restores and force deletes a post', function () {
    $post = Post::factory()->create(['author_id' => $this->editor->id]);
    $post->upsertTranslations(['tj' => ['title' => 'Т', 'slug' => 'p-del']]);

    $this->actingAs($this->editor)->delete(route('admin.posts.destroy', $post));
    expect(Post::count())->toBe(0)->and(Post::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->editor)->patch(route('admin.posts.restore', $post));
    expect(Post::count())->toBe(1);

    $this->actingAs($this->editor)->delete(route('admin.posts.destroy', $post));
    $this->actingAs($this->editor)->delete(route('admin.posts.force-delete', $post));
    expect(Post::withTrashed()->count())->toBe(0);
});
