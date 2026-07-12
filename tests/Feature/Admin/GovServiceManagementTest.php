<?php

use App\Enums\Role;
use App\Enums\ServiceCategory;
use App\Models\GovService;
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

function govServicePayload(array $overrides = []): array
{
    return array_merge([
        'category' => ServiceCategory::Information->value,
        'status' => 'published',
        'is_online' => true,
        'external_url' => 'https://egov.tj/example',
        'processing_time' => '5 рабочих дней',
        'fee' => 'Бесплатно',
        'sort_order' => 0,
        'translations' => [
            'tj' => [
                'title' => 'Хизматрасонии санҷишӣ',
                'slug' => '',
                'summary' => 'Хулоса',
                'description' => '<p>Тавсиф</p>',
                'eligibility' => '',
                'required_documents' => '',
            ],
            'ru' => [
                'title' => 'Тестовая услуга',
                'slug' => '',
                'summary' => 'Краткое описание',
                'description' => '<p>Подробное описание</p>',
                'eligibility' => '<p>Граждане РТ</p>',
                'required_documents' => '<ul><li>Паспорт</li></ul>',
            ],
            'en' => ['title' => '', 'slug' => '', 'summary' => '', 'description' => '', 'eligibility' => '', 'required_documents' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.services.index'))->assertRedirect(route('login'));
});

it('renders the services list and form', function () {
    $service = GovService::factory()->create();
    $service->upsertTranslations([
        'ru' => [
            'title' => 'Услуга',
            'slug' => 'usluga-ru',
            'summary' => 'Описание',
            'description' => null,
            'eligibility' => null,
            'required_documents' => null,
        ],
    ]);

    $this->actingAs($this->editor)->get(route('admin.services.index'))
        ->assertRedirect(route('admin.content.index', 'gov_service'));

    $this->actingAs($this->editor)->get(route('admin.content.index', 'gov_service'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'gov_service')
            ->has('entries.data', 1));

    $this->actingAs($this->editor)->get(route('admin.services.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'gov_service')
            ->has('entry')
            ->has('urls.store')
            ->has('blueprint')
            ->has('fieldOptions.category', 6)
            ->has('statuses', 4));
});

it('creates a service with translations', function () {
    $payload = govServicePayload();
    $payload['translations']['ru']['description'] = '<p>Описание</p><script>alert(1)</script>';

    $this->actingAs($this->editor)
        ->post(route('admin.services.store'), $payload)
        ->assertRedirect(route('admin.content.index', 'gov_service'));

    $service = GovService::with('translations')->first();

    expect($service)->not->toBeNull()
        ->and($service->is_online)->toBeTrue()
        ->and($service->translation('ru')->title)->toBe('Тестовая услуга')
        ->and($service->translation('ru')->description)->toContain('Описание')
        ->and($service->translation('ru')->description)->not->toContain('<script');
});

it('updates and deletes a service', function () {
    $service = GovService::factory()->create();
    $service->upsertTranslations([
        'ru' => [
            'title' => 'Старая',
            'slug' => 'old-ru',
            'summary' => null,
            'description' => null,
            'eligibility' => null,
            'required_documents' => null,
        ],
    ]);

    $this->actingAs($this->editor)
        ->put(route('admin.services.update', $service), govServicePayload())
        ->assertRedirect(route('admin.content.index', 'gov_service'));

    expect($service->fresh()->translation('ru')->title)->toBe('Тестовая услуга');

    $this->actingAs($this->editor)
        ->delete(route('admin.services.destroy', $service))
        ->assertRedirect(route('admin.content.index', 'gov_service'));

    expect(GovService::find($service->id))->toBeNull();
});
