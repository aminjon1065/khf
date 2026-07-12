<?php

use App\Enums\PollType;
use App\Enums\Role;
use App\Models\Poll;
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

function pollPayload(array $overrides = []): array
{
    return array_merge([
        'type' => PollType::General->value,
        'status' => 'published',
        'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        'show_results' => true,
        'sort_order' => 0,
        'translations' => [
            'tj' => [
                'title' => 'Опроси санҷишӣ',
                'description' => '<p>Тавсифи опрос</p>',
                'slug' => '',
            ],
            'ru' => [
                'title' => 'Тестовый опрос',
                'description' => '<p>Описание опроса</p>',
                'slug' => '',
            ],
            'en' => ['title' => '', 'description' => '', 'slug' => ''],
        ],
        'options' => [
            [
                'sort_order' => 0,
                'translations' => [
                    'tj' => ['label' => 'Бале'],
                    'ru' => ['label' => 'Да'],
                    'en' => ['label' => ''],
                ],
            ],
            [
                'sort_order' => 1,
                'translations' => [
                    'tj' => ['label' => 'Не'],
                    'ru' => ['label' => 'Нет'],
                    'en' => ['label' => ''],
                ],
            ],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.polls.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.polls.index'))
        ->assertForbidden();
});

it('renders the poll list and form', function () {
    $poll = Poll::factory()->create();
    $poll->upsertTranslations([
        'ru' => ['title' => 'Тест', 'description' => null, 'slug' => 'test-ru'],
    ]);

    $this->actingAs($this->editor)->get(route('admin.polls.index'))
        ->assertRedirect(route('admin.content.index', 'poll'));

    $this->actingAs($this->editor)->get(route('admin.content.index', 'poll'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'poll')
            ->has('entries.data', 1));

    $this->actingAs($this->editor)->get(route('admin.polls.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/form')
            ->where('contentType.handle', 'poll')
            ->has('locales', 3)
            ->has('blueprint')
            ->has('fieldOptions.type', 2));
});

it('creates a poll with translations and options', function () {
    $payload = pollPayload();
    $payload['translations']['ru']['description'] = '<p>Описание</p><script>alert(1)</script>';

    $this->actingAs($this->editor)
        ->post(route('admin.polls.store'), $payload)
        ->assertRedirect(route('admin.content.index', 'poll'));

    $poll = Poll::with(['translations', 'options.translations'])->first();

    expect($poll)->not->toBeNull()
        ->and($poll->type)->toBe(PollType::General)
        ->and($poll->translations)->toHaveCount(2)
        ->and($poll->options)->toHaveCount(2)
        ->and($poll->translation('ru')->title)->toBe('Тестовый опрос')
        ->and($poll->translation('ru')->description)->toContain('Описание')
        ->and($poll->translation('ru')->description)->not->toContain('<script')
        ->and($poll->options->first()->translation('ru')->label)->toBe('Да');
});

it('requires at least two options', function () {
    $payload = pollPayload();
    $payload['options'] = [pollPayload()['options'][0]];

    $this->actingAs($this->editor)
        ->from(route('admin.polls.create'))
        ->post(route('admin.polls.store'), $payload)
        ->assertSessionHasErrors('options');
});

it('updates and deletes a poll', function () {
    $poll = Poll::factory()->create();
    $poll->upsertTranslations([
        'ru' => ['title' => 'Старый', 'description' => null, 'slug' => 'old-ru'],
    ]);
    $option = $poll->options()->create(['sort_order' => 0]);
    $option->upsertTranslations(['ru' => ['label' => 'Вариант 1']]);
    $option2 = $poll->options()->create(['sort_order' => 1]);
    $option2->upsertTranslations(['ru' => ['label' => 'Вариант 2']]);

    $payload = pollPayload();
    $payload['options'][0]['id'] = $option->id;
    $payload['options'][1]['id'] = $option2->id;

    $this->actingAs($this->editor)
        ->put(route('admin.polls.update', $poll), $payload)
        ->assertRedirect(route('admin.content.index', 'poll'));

    expect($poll->fresh()->translation('ru')->title)->toBe('Тестовый опрос');

    $this->actingAs($this->editor)
        ->delete(route('admin.polls.destroy', $poll))
        ->assertRedirect(route('admin.content.index', 'poll'));

    expect(Poll::find($poll->id))->toBeNull();
});
