<?php

use App\Enums\Role;
use App\Models\Redirect;
use App\Models\User;
use App\Support\RedirectResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);

    RedirectResolver::clearCache();
    Cache::flush();
});

it('redirects guests to login', function () {
    $this->get(route('admin.redirects.index'))->assertRedirect(route('login'));
});

it('forbids a moderator from managing redirects', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('admin.redirects.index'))
        ->assertForbidden();
});

it('lists redirects for a super admin', function () {
    Redirect::factory()->create([
        'from_path' => 'legacy/about',
        'to_url' => '/tj/pages/about',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.redirects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/redirects/index')
            ->has('redirects.data', 1)
            ->where('redirects.data.0.from_path', 'legacy/about')
        );
});

it('creates a redirect', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.redirects.store'), [
            'from_path' => '/tj/node/999',
            'to_url' => '/tj/news/new-post',
            'status_code' => 301,
            'is_active' => true,
            'notes' => 'Legacy Drupal node',
        ])
        ->assertRedirect(route('admin.redirects.index'));

    expect(Redirect::where('from_path', 'tj/node/999')->exists())->toBeTrue();
});

it('validates a unique from path on create', function () {
    Redirect::factory()->create(['from_path' => 'tj/old-url']);

    $this->actingAs($this->admin)
        ->from(route('admin.redirects.index'))
        ->post(route('admin.redirects.store'), [
            'from_path' => 'tj/old-url',
            'to_url' => '/tj/news/other',
            'status_code' => 301,
        ])
        ->assertSessionHasErrors('from_path');
});

it('updates and deletes a redirect', function () {
    $redirect = Redirect::factory()->create([
        'from_path' => 'tj/legacy',
        'to_url' => '/tj/news/old',
        'status_code' => 301,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.redirects.update', $redirect), [
            'from_path' => 'tj/legacy',
            'to_url' => '/tj/news/updated',
            'status_code' => 302,
            'is_active' => false,
            'notes' => 'Updated',
        ])
        ->assertRedirect(route('admin.redirects.index'));

    $redirect->refresh();
    expect($redirect->to_url)->toBe('/tj/news/updated')
        ->and($redirect->status_code)->toBe(302)
        ->and($redirect->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->delete(route('admin.redirects.destroy', $redirect))
        ->assertRedirect(route('admin.redirects.index'));

    expect(Redirect::find($redirect->id))->toBeNull();
});

it('applies database redirects on the public site', function () {
    Redirect::factory()->create([
        'from_path' => 'tj/node/123',
        'to_url' => '/tj/news/old-post',
        'status_code' => 301,
    ]);

    RedirectResolver::clearCache();

    $this->get('/tj/node/123')
        ->assertRedirect('/tj/news/old-post')
        ->assertStatus(301);
});

it('ignores inactive database redirects', function () {
    Redirect::factory()->inactive()->create([
        'from_path' => 'tj/disabled',
        'to_url' => '/tj/news/hidden',
    ]);

    RedirectResolver::clearCache();

    $this->get('/tj/disabled')->assertNotFound();
});

it('lets database redirects override config entries', function () {
    config()->set('redirects', [
        'tj/conflict' => '/tj/news/from-config',
    ]);

    Redirect::factory()->create([
        'from_path' => 'tj/conflict',
        'to_url' => '/tj/news/from-database',
        'status_code' => 301,
    ]);

    RedirectResolver::clearCache();

    $this->get('/tj/conflict')
        ->assertRedirect('/tj/news/from-database')
        ->assertStatus(301);
});
