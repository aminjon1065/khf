<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Leader;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
    Storage::fake('public');

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function leaderPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'sort_order' => 0,
        'email' => 'chief@example.com',
        'phone' => '+992900000000',
        'translations' => [
            'tj' => ['full_name' => 'Раиси кумита', 'position' => 'Раис', 'bio' => 'Тарҷумаи ҳол', 'reception' => 'Душанбе 9:00–12:00'],
            'ru' => ['full_name' => 'Председатель', 'position' => 'Председатель комитета', 'bio' => 'Биография', 'reception' => 'Понедельник 9:00–12:00'],
            'en' => ['full_name' => '', 'position' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.leadership.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.leadership.index'))
        ->assertForbidden();
});

it('renders the leadership list and form', function () {
    $leader = Leader::factory()->create();
    $leader->upsertTranslations(['tj' => ['full_name' => 'Тест', 'position' => 'Должность']]);

    $this->actingAs($this->editor)->get(route('admin.leadership.index'))
        ->assertRedirect(route('admin.content.index', 'leader'));

    $this->actingAs($this->editor)->get(route('admin.content.index', 'leader'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'leader')
            ->has('entries.data', 1));

    $this->actingAs($this->editor)->get(route('admin.leadership.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/form')
            ->where('contentType.handle', 'leader')
            ->has('locales', 3)
            ->has('statuses', 4)
            ->has('blueprint')
            ->has('photoUrl'));
});

it('creates a leader with translations and a photo', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.leadership.store'), leaderPayload([
            'photo' => UploadedFile::fake()->image('chief.jpg', 400, 400),
        ]))
        ->assertRedirect(route('admin.content.index', 'leader'));

    $leader = Leader::with('translations')->first();

    expect($leader->status)->toBe(ContentStatus::Published)
        ->and($leader->translations)->toHaveCount(2)
        ->and($leader->translation('ru')->position)->toBe('Председатель комитета')
        ->and($leader->getFirstMedia(Leader::PHOTO_COLLECTION))->not->toBeNull();
});

it('requires the default-locale full name and position', function () {
    $payload = leaderPayload();
    $payload['translations']['tj']['full_name'] = '';
    $payload['translations']['tj']['position'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.leadership.create'))
        ->post(route('admin.leadership.store'), $payload)
        ->assertSessionHasErrors(['translations.tj.full_name', 'translations.tj.position']);
});

it('updates and deletes a leader', function () {
    $leader = Leader::factory()->create();
    $leader->upsertTranslations(['tj' => ['full_name' => 'Старый', 'position' => 'Должность']]);

    $this->actingAs($this->editor)
        ->put(route('admin.leadership.update', $leader), leaderPayload(['sort_order' => 7]))
        ->assertRedirect(route('admin.content.index', 'leader'));

    expect($leader->fresh()->sort_order)->toBe(7);

    $this->actingAs($this->editor)
        ->delete(route('admin.leadership.destroy', $leader))
        ->assertRedirect(route('admin.content.index', 'leader'));

    expect(Leader::find($leader->id))->toBeNull();
});
