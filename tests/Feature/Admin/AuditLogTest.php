<?php

use App\Enums\Role;
use App\Models\Alert;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();
});

it('renders the audit log for a user with audit.view', function () {
    $viewer = User::factory()->withTwoFactor()->create();
    $viewer->assignRole(Role::Moderator->value);

    activity('auth')->causedBy($viewer)->event('login')->log('Вход в систему');

    $this->actingAs($viewer)->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-logs/index')
            ->has('logs.data.0')
            ->has('events'));
});

it('filters the audit log by event', function () {
    $viewer = User::factory()->withTwoFactor()->create();
    $viewer->assignRole(Role::Moderator->value);

    activity('auth')->causedBy($viewer)->event('login')->log('Вход');
    activity('auth')->event('login_failed')->log('Неудачный вход');

    $this->actingAs($viewer)
        ->get(route('admin.audit-logs.index', ['event' => 'login_failed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.event', 'login_failed'));
});

it('records a login security event with the causer and request context', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->assertAuthenticated();

    $activity = Activity::query()
        ->where('log_name', 'auth')
        ->where('event', 'login')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->properties->get('ip'))->not->toBeNull();
});

it('records a failed login as a security event without a causer', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    $this->assertGuest();

    $activity = Activity::query()
        ->where('log_name', 'auth')
        ->where('event', 'login_failed')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties->get('email'))->toBe($user->email);
});

it('audits content changes on the alert and page models', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Moderator->value);

    $this->actingAs($editor);

    $alert = Alert::factory()->create();
    $page = Page::factory()->create();
    $page->update(['sort_order' => 7]);

    expect(Activity::where('subject_type', Alert::class)->where('subject_id', $alert->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(Activity::where('subject_type', Page::class)->where('subject_id', $page->id)->where('event', 'updated')->exists())->toBeTrue();

    $pageUpdate = Activity::where('subject_type', Page::class)->where('event', 'updated')->latest()->first();

    expect($pageUpdate->causer_id)->toBe($editor->id);
});
