<?php

use App\Enums\AppealStatus;
use App\Enums\Role;
use App\Models\Appeal;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
});

function appealForm(array $overrides = []): array
{
    return array_merge([
        'category' => 'general',
        'name' => 'Иван Иванов',
        'email' => 'ivan@example.com',
        'phone' => '+992900000000',
        'subject' => 'Вопрос по безопасности',
        'message' => 'Текст обращения.',
        'website' => '',
    ], $overrides);
}

it('renders the public appeal form', function () {
    $this->get(route('appeals.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/appeals/create')->has('categories', 4));
});

it('accepts a citizen appeal and assigns a reference', function () {
    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm())
        ->assertRedirect(route('appeals.create', ['locale' => 'tj']))
        ->assertSessionHas('appeal_reference');

    $appeal = Appeal::first();

    expect($appeal)->not->toBeNull()
        ->and($appeal->status)->toBe(AppealStatus::New)
        ->and($appeal->reference)->toStartWith('OBR-');
});

it('rejects a submission with the honeypot filled', function () {
    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm(['website' => 'http://spam']))
        ->assertSessionHasErrors('website');

    expect(Appeal::count())->toBe(0);
});

it('validates required fields', function () {
    $this->post(route('appeals.store', ['locale' => 'tj']), appealForm(['email' => '', 'message' => '']))
        ->assertSessionHasErrors(['email', 'message']);
});

it('tracks an appeal by reference', function () {
    $appeal = Appeal::factory()->create(['subject' => 'Моё обращение']);

    $this->get(route('appeals.track', ['locale' => 'tj', 'reference' => $appeal->reference]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/appeals/track')
            ->where('result.found', true)
            ->where('result.subject', 'Моё обращение')
        );

    $this->get(route('appeals.track', ['locale' => 'tj', 'reference' => 'OBR-2026-NOPE00']))
        ->assertInertia(fn (Assert $page) => $page->where('result.found', false));
});

it('restricts the CMS appeals queue to staff with permission', function () {
    $this->get(route('admin.appeals.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.appeals.index'))
        ->assertForbidden();
});

it('lets a moderator view and update an appeal', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);
    $appeal = Appeal::factory()->create();

    $this->actingAs($moderator)->get(route('admin.appeals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/appeals/index')->has('appeals.data', 1));

    $this->actingAs($moderator)
        ->put(route('admin.appeals.update', $appeal), [
            'status' => 'in_progress',
            'assigned_to' => $moderator->id,
            'internal_note' => 'Взято в работу',
        ])
        ->assertRedirect(route('admin.appeals.show', $appeal));

    expect($appeal->fresh()->status)->toBe(AppealStatus::InProgress)
        ->and($appeal->fresh()->assigned_to)->toBe($moderator->id);
});
