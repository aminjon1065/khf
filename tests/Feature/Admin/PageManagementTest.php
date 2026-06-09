<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function pagePayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'parent_id' => null,
        'sort_order' => 0,
        'translations' => [
            'tj' => ['title' => 'Сарсаҳифа', 'slug' => 'sarsahifa', 'content' => 'Матн'],
            'ru' => ['title' => 'Главная', 'slug' => 'glavnaya', 'content' => 'Текст'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.pages.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.pages.index'))
        ->assertForbidden();
});

it('creates a page with translations, skipping empty locales', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.pages.store'), pagePayload())
        ->assertRedirect(route('admin.pages.index'));

    expect(Page::count())->toBe(1);

    $page = Page::with('translations')->first();

    expect($page->status)->toBe(ContentStatus::Published)
        ->and($page->translations)->toHaveCount(2)
        ->and($page->translation('ru')->title)->toBe('Главная');
});

it('requires the default-locale title', function () {
    $payload = pagePayload();
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.pages.create'))
        ->post(route('admin.pages.store'), $payload)
        ->assertSessionHasErrors('translations.tj.title');
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.pages.store'), pagePayload());

    $second = pagePayload();
    $second['translations']['tj']['slug'] = 'another-tj';

    $this->actingAs($this->editor)
        ->from(route('admin.pages.create'))
        ->post(route('admin.pages.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('allows the same slug across different locales', function () {
    $payload = pagePayload();
    $payload['translations']['tj']['slug'] = 'home';
    $payload['translations']['ru']['slug'] = 'home';

    $this->actingAs($this->editor)
        ->post(route('admin.pages.store'), $payload)
        ->assertRedirect(route('admin.pages.index'));

    expect(Page::first()->translations)->toHaveCount(2);
});

it('updates a page and adds a new translation', function () {
    $page = Page::factory()->draft()->create();
    $page->upsertTranslations(['tj' => ['title' => 'Танҳо', 'slug' => 'tanho']]);

    $payload = pagePayload(['status' => 'draft']);
    $payload['translations']['tj']['slug'] = 'tanho-updated';

    $this->actingAs($this->editor)
        ->put(route('admin.pages.update', $page), $payload)
        ->assertRedirect(route('admin.pages.index'));

    expect($page->fresh()->load('translations')->translations)->toHaveCount(2);
});

it('renders the list, create, edit and trash screens', function () {
    $page = Page::factory()->create();
    $page->upsertTranslations(['tj' => ['title' => 'Тест', 'slug' => 'test-render']]);

    $this->actingAs($this->editor)->get(route('admin.pages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/pages/index')->has('pages.data', 1));

    $this->actingAs($this->editor)->get(route('admin.pages.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/pages/form')->has('locales', 3));

    $this->actingAs($this->editor)->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/pages/form')->where('page.id', $page->id));

    $page->delete();

    $this->actingAs($this->editor)->get(route('admin.pages.trash'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/pages/trash')->has('pages.data', 1));
});

it('soft deletes, restores and force deletes a page', function () {
    $page = Page::factory()->create();
    $page->upsertTranslations(['tj' => ['title' => 'Т', 'slug' => 't-del']]);

    $this->actingAs($this->editor)->delete(route('admin.pages.destroy', $page));
    expect(Page::count())->toBe(0)->and(Page::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->editor)->patch(route('admin.pages.restore', $page));
    expect(Page::count())->toBe(1);

    $this->actingAs($this->editor)->delete(route('admin.pages.destroy', $page));
    $this->actingAs($this->editor)->delete(route('admin.pages.force-delete', $page));
    expect(Page::withTrashed()->count())->toBe(0);
});
