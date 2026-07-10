<?php

use App\Enums\Role;
use App\Models\Faq;
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

function faqPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'sort_order' => 0,
        'translations' => [
            'tj' => ['question' => 'Чӣ тавр занг занам?', 'answer' => 'Ба 112 занг занед.'],
            'ru' => ['question' => 'Как позвонить в экстренную службу?', 'answer' => 'Звоните на номер 112.'],
            'en' => ['question' => '', 'answer' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.faqs.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.faqs.index'))
        ->assertForbidden();
});

it('renders the faq list and form', function () {
    $faq = Faq::factory()->create();
    $faq->upsertTranslations(['tj' => ['question' => 'Тест?', 'answer' => 'Ҷавоб']]);

    $this->actingAs($this->editor)->get(route('admin.faqs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/faq/index')->has('faqs.data', 1));

    $this->actingAs($this->editor)->get(route('admin.faqs.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/faq/form')
            ->has('blueprint')
            ->has('locales', 3)
            ->has('statuses', 4));
});

it('creates a faq with translations and sanitizes the answer', function () {
    $payload = faqPayload();
    $payload['translations']['tj']['answer'] = '<p>Звоните 112</p><script>alert(1)</script>';

    $this->actingAs($this->editor)
        ->post(route('admin.faqs.store'), $payload)
        ->assertRedirect(route('admin.faqs.index'));

    $faq = Faq::with('translations')->first();

    expect($faq->translations)->toHaveCount(2)
        ->and($faq->translation('ru')->question)->toBe('Как позвонить в экстренную службу?')
        ->and($faq->translation('tj')->answer)->toContain('Звоните 112')
        ->and($faq->translation('tj')->answer)->not->toContain('<script');
});

it('requires the default-locale question', function () {
    $payload = faqPayload();
    $payload['translations']['tj']['question'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.faqs.create'))
        ->post(route('admin.faqs.store'), $payload)
        ->assertSessionHasErrors('translations.tj.question');
});

it('updates and deletes a faq', function () {
    $faq = Faq::factory()->create();
    $faq->upsertTranslations(['tj' => ['question' => 'Старый?', 'answer' => 'Ответ']]);

    $this->actingAs($this->editor)
        ->put(route('admin.faqs.update', $faq), faqPayload(['sort_order' => 4]))
        ->assertRedirect(route('admin.faqs.index'));

    expect($faq->fresh()->sort_order)->toBe(4);

    $this->actingAs($this->editor)
        ->delete(route('admin.faqs.destroy', $faq))
        ->assertRedirect(route('admin.faqs.index'));

    expect(Faq::find($faq->id))->toBeNull();
});
