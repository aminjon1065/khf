<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Enums\TenderType;
use App\Models\Tender;
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

function tenderPayload(array $overrides = []): array
{
    return array_merge([
        'tender_number' => 'ТЕНДЕР-2026-001',
        'type' => 'goods',
        'status' => 'published',
        'budget' => 500000,
        'lots_count' => 2,
        'published_at' => '2026-06-16T10:00',
        'deadline_at' => '2026-12-31',
        'translations' => [
            'tj' => ['title' => 'Хариди таҷҳизот', 'slug' => 'xaridi-tachhizot', 'organizer' => 'Раёсат', 'summary' => 'Шарҳ', 'description' => 'Матн', 'requirements' => 'Шартҳо'],
            'ru' => ['title' => 'Закупка оборудования', 'slug' => 'zakupka-oborudovaniya', 'organizer' => 'Управление', 'summary' => 'Описание', 'description' => 'Текст', 'requirements' => 'Условия'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.tenders.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.tenders.index'))
        ->assertForbidden();
});

it('creates a tender and sets the creator', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.tenders.store'), tenderPayload())
        ->assertRedirect(route('admin.content.index', 'tender'));

    $tender = Tender::with('translations')->first();

    expect($tender->type)->toBe(TenderType::Goods)
        ->and($tender->status)->toBe(ContentStatus::Published)
        ->and($tender->lots_count)->toBe(2)
        ->and($tender->created_by)->toBe($this->editor->id)
        ->and($tender->translations)->toHaveCount(2)
        ->and($tender->deadline_at)->not->toBeNull();
});

it('validates type, lots count and the default-locale title', function () {
    $payload = tenderPayload(['type' => 'invalid', 'lots_count' => 0]);
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.tenders.create'))
        ->post(route('admin.tenders.store'), $payload)
        ->assertSessionHasErrors(['type', 'lots_count', 'translations.tj.title']);
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.tenders.store'), tenderPayload());

    $second = tenderPayload();
    $second['translations']['tj']['slug'] = 'another-slug';

    $this->actingAs($this->editor)
        ->from(route('admin.tenders.create'))
        ->post(route('admin.tenders.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('renders the list, create, edit and trash screens', function () {
    $tender = Tender::factory()->create(['created_by' => $this->editor->id]);
    $tender->upsertTranslations(['tj' => ['title' => 'Тест', 'slug' => 'test-tender']]);

    $this->actingAs($this->editor)->get(route('admin.tenders.index'))
        ->assertRedirect(route('admin.content.index', 'tender'));

    $this->actingAs($this->editor)->get(route('admin.content.index', 'tender'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'tender')
            ->has('entries.data', 1));

    $this->actingAs($this->editor)->get(route('admin.tenders.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/form')
            ->where('contentType.handle', 'tender')
            ->has('blueprint')
            ->has('fieldOptions.type', 4)
            ->has('statuses', 4));

    $this->actingAs($this->editor)->get(route('admin.tenders.edit', $tender))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/form')
            ->where('entry.id', $tender->id));

    $tender->delete();

    $this->actingAs($this->editor)->get(route('admin.tenders.trash'))
        ->assertRedirect(route('admin.content.index', ['type' => 'tender', 'trashed' => 1]));

    $this->actingAs($this->editor)->get(route('admin.content.index', ['type' => 'tender', 'trashed' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'tender')
            ->where('filters.trashed', true)
            ->has('entries.data', 1));
});

it('sanitizes the tender description html on save', function () {
    $payload = tenderPayload();
    $payload['translations']['tj']['description'] = '<p>Безопасный текст</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="hack()">ссылка</a>';

    $this->actingAs($this->editor)->post(route('admin.tenders.store'), $payload);

    $description = Tender::first()->translation('tj')->description;

    expect($description)
        ->toContain('Безопасный текст')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:');
});

it('soft deletes, restores and force deletes a tender', function () {
    $tender = Tender::factory()->create(['created_by' => $this->editor->id]);
    $tender->upsertTranslations(['tj' => ['title' => 'Т', 'slug' => 't-del']]);

    $this->actingAs($this->editor)->delete(route('admin.tenders.destroy', $tender));
    expect(Tender::count())->toBe(0)->and(Tender::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->editor)->patch(route('admin.tenders.restore', $tender));
    expect(Tender::count())->toBe(1);

    $this->actingAs($this->editor)->delete(route('admin.tenders.destroy', $tender));
    $this->actingAs($this->editor)->delete(route('admin.tenders.force-delete', $tender));
    expect(Tender::withTrashed()->count())->toBe(0);
});
